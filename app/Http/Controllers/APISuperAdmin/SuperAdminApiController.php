<?php
namespace App\Http\Controllers\APISuperAdmin;
use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Models\State;
use App\Models\UnitAdmin;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SuperAdminApiController extends Controller
{
    private function isSuperAdmin(): bool
{
    return auth()->user()?->role === 'super_admin';
}

    private function unauthorised()
    {
        return ApiResponse::error('Unauthorized. Super Admin access only.', 403);
    }

    // ─────────────────────────────────
    // SHARED MEMBER MAP (slim)
    // ─────────────────────────────────
    private function mapMemberSlim($p): array
{
    return [
        'id'            => (string) $p->id,
        'name'          => $p->user->name,
        'phone'         => $p->user->phone,
        'membership_id' => $p->membership_id,
        'unit_name'     => $p->unit->name ?? null,
        'status'        => $p->status,
        'photo'           => $p->photo ? asset('storage/' . $p->photo) : '',
'is_fumma_member' => (int) $p->is_fumma_member,   // ← add
'registered_on'   => $p->created_at->format('d M Y'),
    ];
}

    // ─────────────────────────────────
    // SHARED PAGINATED RESPONSE
    // ─────────────────────────────────
    private function paginatedResponse($members, Request $request, string $message): \Illuminate\Http\JsonResponse
    {
        return ApiResponse::success([
            'total'        => $members->total(),
            'current_page' => $members->currentPage(),
            'last_page'    => $members->lastPage(),
            'has_more'     => $members->hasMorePages(),
            'members'      => $members->getCollection()->map(fn($p) => $this->mapMemberSlim($p)),
        ], $message);
    }

    // ─────────────────────────────────
    // SHARED MEMBER QUERY BUILDER
    // keyword, state_id, district_id, unit_id from query string
    // ─────────────────────────────────
    private function buildMemberQuery(Request $request, ?string $status = null)
{
    $query = MemberProfile::with(['user', 'unit', 'district', 'state'])->latest();

    if ($status) {
        $query->where('status', $status);
    } elseif ($request->filled('status')) {
        $query->where('status', $request->input('status'));
    }

    if ($request->filled('district_id')) $query->where('district_id', $request->input('district_id'));
    if ($request->filled('state_id'))    $query->where('state_id',    $request->input('state_id'));
    if ($request->filled('unit_id'))     $query->where('unit_id',     $request->input('unit_id'));

    if ($request->filled('keyword')) {
        $kw = $request->input('keyword');   // reads from body (json) or query string
        $query->where(function ($q) use ($kw) {
            $q->whereHas('user', fn($uq) =>
                $uq->where('name',  'LIKE', "%$kw%")
                   ->orWhere('email', 'LIKE', "%$kw%")
                   ->orWhere('phone', 'LIKE', "%$kw%")
            )
            ->orWhere('organization',  'LIKE', "%$kw%")
            ->orWhere('membership_id', 'LIKE', "%$kw%");
        });
    }

    return $query;
}



// ═══════════════════════════════════════════
// ADD DISTRICT  (state is fixed to Kerala)
// POST /api/admin/locations/district/add
// Body: { name }
// ═══════════════════════════════════════════
public function addDistrict(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    // Force Kerala, whatever the client sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name'     => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\'\-]+$/'],
        'state_id' => 'required|exists:states,id',
    ], [
        'name.regex' => 'District name can only contain letters, spaces, hyphens and apostrophes.',
        'name.max'   => 'District name cannot exceed 100 characters.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // Prevent duplicate district name within the same state
    $exists = \App\Models\District::where('state_id', $request->state_id)
                ->where('name', trim($request->name))
                ->exists();

    if ($exists) {
        return ApiResponse::error('A district with this name already exists in the selected state.', 400);
    }

    $district = \App\Models\District::create([
        'name'      => trim($request->name),
        'state_id'  => $request->state_id,
        'is_active' => true,   // same as the web panel
    ]);

    $state = State::find($request->state_id);

    return ApiResponse::success([
        'id'         => (string) $district->id,
        'name'       => $district->name,
        'state_id'   => (string) $state->id,
        'state_name' => $state->name,
    ], 'District added successfully.', 201);
}

// ═══════════════════════════════════════════
// ADD UNIT  (state is fixed to Kerala)
// POST /api/admin/locations/unit/add
// Body: { name, district_id }
// ═══════════════════════════════════════════
public function addUnit(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    // Force Kerala, whatever the client sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name'        => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\'\-]+$/'],
        'state_id'    => 'required|exists:states,id',
        'district_id' => 'required|exists:districts,id',
    ], [
        'name.regex' => 'Unit name can only contain letters, spaces, hyphens and apostrophes.',
        'name.max'   => 'Unit name cannot exceed 100 characters.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // Validate district belongs to Kerala (the selected state)
    $district = \App\Models\District::find($request->district_id);
    if ($district->state_id != $request->state_id) {
        return ApiResponse::error('District does not belong to the selected state.', 400);
    }

    // Prevent duplicate unit name within the same district
    $exists = \App\Models\Unit::where('district_id', $request->district_id)
                ->where('name', trim($request->name))
                ->exists();

    if ($exists) {
        return ApiResponse::error('A unit with this name already exists in the selected district.', 400);
    }

    $unit = \App\Models\Unit::create([
        'name'        => trim($request->name),
        'district_id' => $request->district_id,
        'is_active'   => true,   // same as the web panel
    ]);

    $state = State::find($request->state_id);

    return ApiResponse::success([
        'id'            => (string) $unit->id,
        'name'          => $unit->name,
        'district_id'   => (string) $district->id,
        'district_name' => $district->name,
        'state_id'      => (string) $state->id,
        'state_name'    => $state->name,
    ], 'Unit added successfully.', 201);
}

// ═══════════════════════════════════════════
// GET ALL MEMBERS
// GET /api/admin/members/all?page=1&per_page=15
// ═══════════════════════════════════════════
public function getAllMembers(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $members = MemberProfile::with(['user', 'unit', 'district', 'state'])
                ->latest()
                ->paginate($request->query('per_page', 15));

    return $this->paginatedResponse($members, $request, 'Members fetched successfully.');
}

// ═══════════════════════════════════════════
// GET ALL UNIT ADMINS
// GET /api/admin/unit-admins/all?page=1&per_page=15
// ═══════════════════════════════════════════
public function getAllUnitAdmins(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $unitAdmins = UnitAdmin::with(['user', 'unit.district.state'])
                    ->latest()
                    ->paginate($request->query('per_page', 15));

    return ApiResponse::success([
        'total'        => $unitAdmins->total(),
        'current_page' => $unitAdmins->currentPage(),
        'last_page'    => $unitAdmins->lastPage(),
        'has_more'     => $unitAdmins->hasMorePages(),
        'unit_admins'  => $unitAdmins->getCollection()->map(fn($ua) => $this->mapUnitAdmin($ua)),
    ], 'Unit Admins fetched successfully.');
}

// ═══════════════════════════════════════════
// SEARCH UNIT ADMINS
// POST /api/admin/unit-admins/search
// Body: { keyword, state_id?, district_id?, per_page? }
// ═══════════════════════════════════════════
public function searchUnitAdmins(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $request->validate([
        'keyword' => 'nullable|string|min:2',
    ]);

    $query = UnitAdmin::with(['user', 'unit.district.state'])->latest();

    if ($request->filled('state_id')) {
        $query->whereHas('unit.district', fn($q) =>
            $q->where('state_id', $request->input('state_id'))
        );
    }

    if ($request->filled('district_id')) {
        $query->whereHas('unit', fn($q) =>
            $q->where('district_id', $request->input('district_id'))
        );
    }

    if ($request->filled('keyword')) {
        $kw = $request->input('keyword');
        $query->whereHas('user', fn($q) =>
            $q->where('name',  'LIKE', "%$kw%")
              ->orWhere('email', 'LIKE', "%$kw%")
              ->orWhere('phone', 'LIKE', "%$kw%")
        );
    }

    $unitAdmins = $query->paginate($request->input('per_page', 15));

    return ApiResponse::success([
        'total'        => $unitAdmins->total(),
        'current_page' => $unitAdmins->currentPage(),
        'last_page'    => $unitAdmins->lastPage(),
        'has_more'     => $unitAdmins->hasMorePages(),
        'unit_admins'  => $unitAdmins->getCollection()->map(fn($ua) => $this->mapUnitAdmin($ua)),
    ], $request->filled('keyword') ? 'Search results fetched.' : 'All unit admins fetched.');
}

public function deleteMember(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'id'       => 'required|integer',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    $admin = auth()->user();

    if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), $admin->password)) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => 'Incorrect password. Member was not deleted.',
            'data'        => (object) [],
        ], 422);
    }

    $profile = MemberProfile::find($request->input('id'));

    if (!$profile) {
        return ApiResponse::error('Member not found or already deleted.', 404);
    }

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, $admin->id, 'api_admin');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Member delete failed for profile #' . $profile->id . ': ' . $e->getMessage());
        return ApiResponse::error('Could not delete this member. They may still have linked records.', 409);
    }

    return ApiResponse::success(null, 'Member deleted successfully.');
}



// ═══════════════════════════════════════════
// REGISTER MEMBER (Super Admin) — auto-approved
// POST /api/admin/members/register
// Member skips PENDING: Membership ID + joined_date are generated immediately.
// Login = Membership ID + phone number. Approval email is sent if an email was given.
// Body: { name, email?, phone, dob, organization, address,
//         state_id, district_id, unit_id, is_fumma_member?, photo (base64, required) }
// ═══════════════════════════════════════════
public function registerMember(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();
    // Force Kerala, whatever the client sends
    if ($fixed = State::fixed()) {
    $request->merge(['state_id' => $fixed->id]);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name'         => 'required|string',
        'email'        => ['nullable', 'email', \Illuminate\Validation\Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'member'))],
        'phone'        => 'required|digits:10|unique:users,phone',
        'dob'          => [
            'required', 'date', 'before:today',
            function ($attribute, $value, $fail) {
                if (\Carbon\Carbon::parse($value)->age < 5) {
                    $fail('Member must be at least 5 years old.');
                }
            }
        ],
        'photo'           => 'required|string', // base64 (jpg/jpeg/png, max 2MB)
        'organization'    => 'required|string',
        'address'         => 'required|string|max:500',
        'state_id'        => 'required|exists:states,id',
        'district_id'     => 'required|exists:districts,id',
        'unit_id'         => 'required|exists:units,id',
        'is_fumma_member' => 'nullable|in:0,1',
    ], [
        'email.unique'     => 'Email is already taken.',
        'phone.unique'     => 'Phone number is already taken.',
        'address.required' => 'Address is required.',
        'address.max'      => 'Address cannot exceed 500 characters.',
        'photo.required'   => 'Member photo is required.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // Email is optional: always store NULL (never '') so the unique index stays safe
    $email = trim((string) $request->input('email')) !== '' ? trim($request->input('email')) : null;

    $fee = \App\Models\RegistrationFee::latest()->first();
    if (!$fee) {
        return ApiResponse::error('No registration fee configured. Contact admin.', 400);
    }

    $district = \App\Models\District::find($request->district_id);
    if ($district->state_id != $request->state_id) {
        return ApiResponse::error('District does not belong to the selected state.', 400);
    }

    $unit = \App\Models\Unit::find($request->unit_id);
    if ($unit->district_id != $request->district_id) {
        return ApiResponse::error('Unit does not belong to the selected district.', 400);
    }

    // ── Handle photo (base64, required) ──
    $raw   = preg_replace('#^data:image/\w+;base64,#i', '', $request->photo);
    $image = base64_decode($raw, true);
    $info  = ($image !== false) ? @getimagesizefromstring($image) : false;

    if ($info === false) {
        return response()->json([
            'status' => '0', 'status_code' => '422',
            'message' => 'Photo must be a valid image.', 'data' => (object) [],
        ], 422);
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

    if (!isset($allowed[$info['mime']])) {
        return response()->json([
            'status' => '0', 'status_code' => '422',
            'message' => 'Photo must be a jpg, jpeg or png file.', 'data' => (object) [],
        ], 422);
    }

    if (strlen($image) > 2 * 1024 * 1024) {
        return response()->json([
            'status' => '0', 'status_code' => '422',
            'message' => 'Photo cannot exceed 2MB.', 'data' => (object) [],
        ], 422);
    }

    $fileName = 'member_' . uniqid() . '.' . $allowed[$info['mime']];
    \Illuminate\Support\Facades\Storage::disk('public')->put('member_photos/' . $fileName, $image);
    $photoPath = 'member_photos/' . $fileName;

    // ── Create user + profile + approve, all atomically ──
    try {
        [$user, $profile] = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $fee, $email, $photoPath) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $email,
                'phone'     => $request->phone,
                'password'  => \Illuminate\Support\Facades\Hash::make($request->phone),
                'role'      => 'member',
                'is_active' => true,
            ]);

            $profile = MemberProfile::create([
                'user_id'         => $user->id,
                'fee_id'          => $fee->id,
                'plan_id'         => null,
                'unit_id'         => $request->unit_id,
                'district_id'     => $request->district_id,
                'state_id'        => $request->state_id,
                'status'          => 'PENDING',
                'dob'             => $request->dob,
                'organization'    => $request->organization,
                'address'         => $request->address,
                'photo'           => $photoPath,
                'is_fumma_member' => (int) $request->input('is_fumma_member', 0),
                'membership_id'   => null,
                'joined_date'     => null,
            ]);

            // Admin-created members skip the PENDING queue:
            // generates the Membership ID, sets APPROVED + joined_date
            $profile = app(\App\Services\MembershipService::class)->approve($profile);

            return [$user, $profile];
        });
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Member registration failed: ' . $e->getMessage());
        \Illuminate\Support\Facades\Storage::disk('public')->delete($photoPath);
        return ApiResponse::error('Something went wrong while registering the member. Please try again.', 500);
    }

    // ── Approval email (outside the transaction, so a mail failure can't roll anything back) ──
    $emailSent = false;
    if (!empty($user->email)) {
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\MemberApprovedMail(
                memberName:   $user->name,
                membershipId: $profile->membership_id,
                unitName:     $unit->name ?? 'ECA Unit',
                phone:        $user->phone,
            ));
            $emailSent = true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Approval email failed for member #' . $profile->id . ': ' . $e->getMessage());
        }
    }

    $state = \App\Models\State::find($request->state_id);

    $message = 'Member registered and approved successfully. Membership ID: ' . $profile->membership_id . '.';
    if (empty($user->email)) {
        $message .= ' No email given, share the login details manually.';
    } elseif ($emailSent) {
        $message .= ' Login details emailed to ' . $user->email . '.';
    } else {
        $message .= ' Could not send the email, share the login details manually.';
    }

    return ApiResponse::success([
        'id'              => (string) $profile->id,   // member profile id (used by detail/approve/reject/delete)
        'user_id'         => (string) $user->id,
        'name'            => $user->name,
        'email'           => $user->email,
        'phone'           => $user->phone,
        'dob'             => $request->dob,
        'organization'    => $profile->organization,
        'address'         => $profile->address,
        'is_fumma_member' => (int) $profile->is_fumma_member,
        'status'          => $profile->status,        // "APPROVED"
        'membership_id'   => $profile->membership_id,
        'joined_date'     => $profile->joined_date,
        'photo'           => asset('storage/' . $photoPath),
        'unit_name'       => $unit->name,
        'district_name'   => $district->name,
        'state_name'      => $state->name,
        'email_sent'      => $emailSent,
    ], $message, 201);
}

public function registerUnitAdmin(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    // Force Kerala, whatever the client sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name'        => 'required|string',
        'email'       => ['required', 'email', \Illuminate\Validation\Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'unit_admin'))],
        'phone'       => 'required|digits:10|unique:users,phone',
        'state_id'    => 'required|exists:states,id',
        'district_id' => 'required|exists:districts,id',
        'unit_id'     => 'required|exists:units,id',
    ], [
        'email.unique' => 'Email is already taken.',
        'phone.unique' => 'Phone number is already taken.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // ── Validate district belongs to state ──
    $district = \App\Models\District::find($request->district_id);
    if (!$district || $district->state_id != $request->state_id) {
        return ApiResponse::error('District does not belong to the selected state.', 400);
    }

    // ── Validate unit belongs to district ──
    $unit = \App\Models\Unit::with('district.state')->find($request->unit_id);
    if (!$unit || $unit->district_id != $request->district_id) {
        return ApiResponse::error('Unit does not belong to the selected district.', 400);
    }

    // ── Check unit already has an admin ──
    if (UnitAdmin::where('unit_id', $request->unit_id)->exists()) {
        return ApiResponse::error('This unit already has an admin assigned.', 400);
    }

    // ── Auto-generate password: FirstName@randomNumber ──
    $firstName = explode(' ', trim($request->name))[0];
    $password  = $firstName . '@' . rand(1000, 9999);

    // ── Create user + unit admin record atomically ──
    try {
        [$user, $unitAdmin] = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $password) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => \Illuminate\Support\Facades\Hash::make($password),
                'role'      => 'unit_admin',
                'is_active' => true,
            ]);

            $unitAdmin = UnitAdmin::create([
                'user_id'     => $user->id,
                'unit_id'     => $request->unit_id,
                'assigned_by' => auth()->id(),
            ]);

            return [$user, $unitAdmin];
        });
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Unit Admin registration failed: ' . $e->getMessage());
        return ApiResponse::error('Something went wrong while registering the unit admin. No account was created — please try again.', 500);
    }

    // ── Send credentials email (failure is logged, never shown in the message) ──
    $emailSent = true;
    try {
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\UnitAdminWelcomeMail(
            adminName:    $user->name,
            email:        $user->email,
            password:     $password,
            unitName:     $unit->name,
            districtName: $district->name,
        ));
    } catch (\Exception $e) {
        $emailSent = false;
        \Illuminate\Support\Facades\Log::error('Unit Admin welcome email failed: ' . $e->getMessage());
    }

    return ApiResponse::success([
        'id'            => (string) $unitAdmin->id,
        'user_id'       => (string) $user->id,
        'name'          => $user->name,
        'email'         => $user->email,
        'phone'         => $user->phone,
        'password'      => $password,
        'unit_id'       => (string) $unit->id,
        'unit_name'     => $unit->name,
        'district_id'   => (string) $district->id,
        'district_name' => $district->name,
        'state_id'      => (string) $unit->district->state->id,
        'state_name'    => $unit->district->state->name,
        'email_sent'    => $emailSent,
    ], 'Unit Admin registered successfully.', 201);
}


    // ═══════════════════════════════════════════
    // ALL MEMBERS
    // POST /api/admin/members?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function members(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $members = $this->buildMemberQuery($request)
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, $request, 'Members fetched successfully.');
    }

    // ═══════════════════════════════════════════
    // PENDING MEMBERS
    // GET /api/admin/members/pending?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function pendingMembers(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $members = $this->buildMemberQuery($request, 'PENDING')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, $request, 'Pending members fetched.');
    }

    // ═══════════════════════════════════════════
    // APPROVED MEMBERS
    // GET /api/admin/members/approved?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function approvedMembers(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $members = $this->buildMemberQuery($request, 'APPROVED')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, $request, 'Approved members fetched.');
    }

    // ═══════════════════════════════════════════
    // REJECTED MEMBERS
    // GET /api/admin/members/rejected?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function rejectedMembers(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $members = $this->buildMemberQuery($request, 'REJECTED')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, $request, 'Rejected members fetched.');
    }

    // ═══════════════════════════════════════════
    // SEARCH MEMBERS
    // GET /api/admin/members/search?keyword=john&page=1&per_page=15
    // ═══════════════════════════════════════════
    public function searchMembers(Request $request)
{
    if (!$this->isSuperAdmin()) {
        return $this->unauthorised();
    }

    $request->validate([
        'keyword' => 'nullable|string|min:2',
    ]);

    $members = $this->buildMemberQuery($request)
                    ->paginate($request->query('per_page', 15));

    return $this->paginatedResponse(
        $members,
        $request,
        $request->filled('keyword')
            ? 'Search results fetched.'
            : 'All members fetched.'
    );
}

    // ─────────────────────────────────
    // DETAILED MEMBER MAP (for detail view)
    // ─────────────────────────────────
    private function mapMemberDetail($p): array
    {
        return [
            'id'            => (string) $p->id,
            'name'          => $p->user->name,
            'email'         => $p->user->email,
            'phone'         => $p->user->phone,
            'dob'           => (string) $p->dob,
            'organization'  => $p->organization,
            'address'       => $p->address,
            'membership_id' => $p->membership_id,
            'status'        => $p->status,
            'photo'         => $p->photo ? asset('storage/' . $p->photo) : null,
            'unit_name'     => $p->unit->name ?? null,
            'district_name' => $p->district->name ?? null,
            'state_name'    => $p->state->name ?? null,
            'joined_date'   => $p->joined_date,
            'registered_on' => $p->created_at->format('d M Y'),
            'is_fumma_member' => (int) $p->is_fumma_member,
        ];
    }

// ═══════════════════════════════════════════
// MEMBER DETAIL
// POST /api/admin/members/detail
// Body: { id }
// ═══════════════════════════════════════════
public function memberDetail(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $request->validate(['id' => 'required|exists:member_profiles,id']);

    $p = MemberProfile::with(['user', 'unit', 'district', 'state'])->find($request->id);

    return ApiResponse::success($this->mapMemberDetail($p), 'Member detail fetched successfully.');
}

    // ═══════════════════════════════════════════
    // APPROVE MEMBER
    // POST /api/admin/members/approve
    // Body: { id }
    // ═══════════════════════════════════════════
        // ═══════════════════════════════════════════
    // APPROVE MEMBER
    // POST /api/admin/members/approve
    // Body: { id }
    // ═══════════════════════════════════════════
    public function approveMember(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $request->validate(['id' => 'required|exists:member_profiles,id']);

        $profile = MemberProfile::with(['user', 'unit'])->find($request->id);

        if (!in_array($profile->status, ['PENDING', 'REJECTED'])) {
            return ApiResponse::error('Only PENDING or REJECTED members can be approved.', 400);
        }

        // ── Approve + generate Membership ID ──
        $profile = app(\App\Services\MembershipService::class)->approve($profile);

        if (!empty($profile->user->email)) {
    try {
        \Illuminate\Support\Facades\Mail::to($profile->user->email)
            ->send(new \App\Mail\MemberApprovedMail(
                memberName:   $profile->user->name,
                membershipId: $profile->membership_id,
                unitName:     $profile->unit->name ?? 'ECA Unit',
                phone:        $profile->user->phone,
            ));
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Approval email failed: ' . $e->getMessage());
    }
}

        return ApiResponse::success([
            'id'            => (string) $profile->id,
            'name'          => $profile->user->name,
            'status'        => $profile->status,
            'membership_id' => $profile->membership_id,
            'joined_date'   => $profile->joined_date,
        ], 'Member approved successfully. Membership ID: ' . $profile->membership_id);
    }

    // ═══════════════════════════════════════════
    // REJECT MEMBER
    // POST /api/admin/members/reject
    // Body: { id, reason }
    // ═══════════════════════════════════════════
    public function rejectMember(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $request->validate([
            'id'     => 'required|exists:member_profiles,id',
            'reason' => 'nullable|string',
        ]);

        $profile = MemberProfile::with(['user', 'unit'])->find($request->id);

        if (!in_array($profile->status, ['PENDING', 'APPROVED'])) {
            return ApiResponse::error('Only PENDING or APPROVED members can be rejected.', 400);
        }

        $profile->status = 'REJECTED';
        $profile->save();

        if (!empty($profile->user->email)) {
    try {
        \Illuminate\Support\Facades\Mail::to($profile->user->email)
            ->send(new \App\Mail\MemberStatusMail(
                memberName:   $profile->user->name,
                unitName:     $profile->unit->name ?? 'ECA',
                status:       'rejected',
                reason:       $request->reason,
                contactEmail: config('mail.from.address'),
                loginUrl:     url('/login'),
            ));
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Rejection email failed: ' . $e->getMessage());
    }
}

        return ApiResponse::success([
            'id'     => (string) $profile->id,
            'name'   => $profile->user->name,
            'status' => $profile->status,
            'reason' => $request->reason,
        ], 'Member rejected successfully.');
    }

    // ═══════════════════════════════════════════
// ALL UNIT ADMINS / UNIT DETAIL
// POST /api/admin/unit-admins?page=1&per_page=15
// Body: { keyword?, state_id?, district_id?, unit_id? }
// When unit_id is provided → returns unit admin + their members
// ═══════════════════════════════════════════
public function unitAdmins(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    // ── If unit_id provided → return unit admin + members ──
    if ($request->filled('unit_id')) {

        $request->validate([
            'unit_id' => 'required|exists:units,id',
            'status'  => 'nullable|in:PENDING,APPROVED,REJECTED',
        ]);

        $unitId  = $request->unit_id;
        $perPage = $request->query('per_page', 10);

        // ── Unit info ──
        $unit = \App\Models\Unit::with('district.state')->find($unitId);

        // ── Unit Admin ──
        $ua        = UnitAdmin::with('user')->where('unit_id', $unitId)->first();
        $unitAdmin = $ua ? [
            'id'        => (string) $ua->id,
            'name'      => $ua->user->name,
            'phone'     => $ua->user->phone,
            'email'     => $ua->user->email,
            'is_active' => $ua->user->is_active,
        ] : null;

        // ── Member counts ──
        $counts = [
            'total'    => MemberProfile::where('unit_id', $unitId)->count(),
            'pending'  => MemberProfile::where('unit_id', $unitId)->where('status', 'PENDING')->count(),
            'approved' => MemberProfile::where('unit_id', $unitId)->where('status', 'APPROVED')->count(),
            'rejected' => MemberProfile::where('unit_id', $unitId)->where('status', 'REJECTED')->count(),
        ];

        // ── Members query ──
        $membersQuery = MemberProfile::with(['user'])
            ->where('unit_id', $unitId)
            ->latest();

        if ($request->filled('status')) {
            $membersQuery->where('status', $request->status);
        }

        $members = $membersQuery->paginate($perPage);

        // ── Slim member map per status ──
        $memberList = $members->getCollection()->map(function ($p) {

            $base = [
                'id'    => (string) $p->id,
                'name'  => $p->user->name,
                'phone' => $p->user->phone,
            ];

            if ($p->status === 'PENDING') {
                return array_merge($base, [
                    'status'        => $p->status,
                    'organization'  => $p->organization,
                    'registered_on' => $p->created_at->format('d M Y'),
                ]);
            }

            if ($p->status === 'APPROVED') {
                return array_merge($base, [
                    'status'        => $p->status,
                    'membership_id' => $p->membership_id,
                    'joined_date'   => $p->joined_date
                        ? \Carbon\Carbon::parse($p->joined_date)->format('d M Y')
                        : null,
                ]);
            }

            if ($p->status === 'REJECTED') {
                return array_merge($base, [
                    'status'        => $p->status,
                    'registered_on' => $p->created_at->format('d M Y'),
                ]);
            }

            // mixed (no status filter)
            return array_merge($base, [
                'status'        => $p->status,
                'membership_id' => $p->membership_id,
                'registered_on' => $p->created_at->format('d M Y'),
            ]);
        });

        return ApiResponse::success([
            'unit' => [
                'id'       => (string) $unit->id,
                'name'     => $unit->name,
                'district' => $unit->district->name,
                'state'    => $unit->district->state->name,
            ],
            'unit_admin'     => $unitAdmin,
            'member_counts'  => $counts,
            'members' => [
                'total'         => $members->total(),
                'current_page'  => $members->currentPage(),
                'last_page'     => $members->lastPage(),
                'has_more'      => $members->hasMorePages(),
                'status_filter' => $request->status ?? 'ALL',
                'data'          => $memberList,
            ],
        ], 'Unit detail fetched successfully.');
    }

    // ── No unit_id → return paginated unit admin list ──
    $query = UnitAdmin::with(['user', 'unit.district.state'])->latest();

    if ($request->filled('state_id')) {
        $query->whereHas('unit.district', fn($q) =>
            $q->where('state_id', $request->state_id)
        );
    }

    if ($request->filled('district_id')) {
        $query->whereHas('unit', fn($q) =>
            $q->where('district_id', $request->district_id)
        );
    }

    if ($request->filled('keyword')) {
        $kw = $request->keyword;
        $query->whereHas('user', fn($q) =>
            $q->where('name',  'LIKE', "%$kw%")
              ->orWhere('email', 'LIKE', "%$kw%")
              ->orWhere('phone', 'LIKE', "%$kw%")
        );
    }

    $unitAdmins = $query->paginate($request->query('per_page', 15));

    return ApiResponse::success([
        'total'        => $unitAdmins->total(),
        'current_page' => $unitAdmins->currentPage(),
        'last_page'    => $unitAdmins->lastPage(),
        'has_more'     => $unitAdmins->hasMorePages(),
        'unit_admins'  => $unitAdmins->getCollection()->map(fn($ua) => [
            'id'        => (string) $ua->id,
            'name'      => $ua->user->name,
            'phone'     => $ua->user->phone,
            'is_active' => $ua->user->is_active,
            'unit_id'   => (string) $ua->unit_id,
            'unit_name' => $ua->unit->name ?? null,
            'district'  => $ua->unit->district->name ?? null,
            'state'     => $ua->unit->district->state->name ?? null,
        ]),
    ], 'Unit Admins fetched successfully.');
}

    // ─────────────────────────────────
    // SHARED UNIT ADMIN MAP
    // ─────────────────────────────────
    private function mapUnitAdmin($ua): array
    {
        return [
            'id'        => (string) $ua->id,
            'name'      => $ua->user->name,
            'email'     => $ua->user->email,
            'phone'     => $ua->user->phone,
            'is_active' => $ua->user->is_active,
            'unit_id'   => (string) $ua->unit_id,
            'unit_name' => $ua->unit->name ?? null,
            'district'  => $ua->unit->district->name ?? null,
            'state'     => $ua->unit->district->state->name ?? null,
        ];
    }

    // ═══════════════════════════════════════════
    // UNIT ADMIN DETAIL
    // POST /api/admin/unit-admins/detail
    // Body: { id }
    // ═══════════════════════════════════════════
    public function unitAdminDetail(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $request->validate(['id' => 'required|exists:unit_admins,id']);

        $ua = UnitAdmin::with(['user', 'unit.district.state'])->find($request->id);

        return ApiResponse::success($this->mapUnitAdmin($ua), 'Unit Admin detail fetched successfully.');
    }

    // ═══════════════════════════════════════════
    // TOGGLE UNIT ADMIN STATUS
    // POST /api/admin/unit-admins/toggle-status
    // Body: { id }
    // ═══════════════════════════════════════════
    public function toggleUnitAdminStatus(Request $request)
    {
        if (!$this->isSuperAdmin()) return $this->unauthorised();

        $request->validate(['id' => 'required|exists:unit_admins,id']);

        $unitAdmin       = UnitAdmin::find($request->id);
        $user            = User::find($unitAdmin->user_id);
        $user->is_active = !$user->is_active;
        $user->save();

        return ApiResponse::success([
            'id'        => (string) $unitAdmin->id,
            'name'      => $user->name,
            'is_active' => $user->is_active,
        ], 'Unit Admin ' . ($user->is_active ? 'activated' : 'deactivated') . ' successfully.');
    }

    // ═══════════════════════════════════════════
// DELETE UNIT ADMIN (password confirmation required)
// POST /api/admin/unit-admins/delete
// Body: { id, password }
// ═══════════════════════════════════════════
public function deleteUnitAdmin(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'id'       => 'required|exists:unit_admins,id',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // Verify the logged-in super admin's password
    if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), auth()->user()->password)) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => 'Incorrect password. Unit Admin was not deleted.',
            'data'        => (object) [],
        ], 422);
    }

    $unitAdmin = UnitAdmin::find($request->id);
    $user      = User::find($unitAdmin->user_id);

    try {
        \Illuminate\Support\Facades\DB::transaction(function () use ($unitAdmin, $user) {
            $unitAdmin->delete();

            if ($user) {
                $user->tokens()->delete(); // log the admin out of every device
                $user->delete();
            }
        });
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Unit Admin delete failed: ' . $e->getMessage());
        return ApiResponse::error('Could not delete the unit admin. Please try again.', 500);
    }

    return ApiResponse::success(null, 'Unit Admin deleted successfully.');
}
    
public function registerDistrictAdmin(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    // Force Kerala, whatever the client sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'name' => [
            'required', 'string', 'max:100',
            'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/',
        ],
        'email' => [
            'required',
            'email:rfc,dns',
            'max:150',
            'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
            'unique:users,email',
        ],
        'phone'       => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
        'state_id'    => 'required|exists:states,id',
        'district_id' => 'required|exists:districts,id',
    ], [
        'name.regex'   => 'Name can only contain letters, spaces, hyphens and apostrophes.',
        'name.max'     => 'Name cannot exceed 100 characters.',
        'email.email'  => 'Enter a valid email address.',
        'email.dns'    => 'This email domain does not appear to accept mail. Please check for typos.',
        'email.max'    => 'Email cannot exceed 150 characters.',
        'email.regex'  => 'Email must include a valid domain (e.g. name@example.com).',
        'email.unique' => 'Email is already taken.',
        'phone.regex'  => 'Enter a valid 10-digit Indian phone number.',
        'phone.unique' => 'Phone number is already taken.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // ── Validate district belongs to state ──
    $district = \App\Models\District::with('state')->find($request->district_id);
    if (!$district || $district->state_id != $request->state_id) {
        return ApiResponse::error('District does not belong to the selected state.', 400);
    }

    // ── Check district already has an admin ──
    if (\App\Models\DistrictAdmin::where('district_id', $request->district_id)->exists()) {
        return ApiResponse::error('This district already has an admin assigned.', 400);
    }

    // ── Auto-generate password: FirstName@randomNumber ──
    $firstName = explode(' ', trim($request->name))[0];
    $password  = $firstName . '@' . rand(1000, 9999);

    // ── Create user + district admin record atomically ──
    try {
        [$user, $districtAdmin] = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $password) {
            $user = User::create([
                'name'      => trim($request->name),
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => $password,   // hashed by the User model, same as the web admin flow
                'role'      => 'district_admin',
                'is_active' => true,
            ]);

            $districtAdmin = \App\Models\DistrictAdmin::create([
                'user_id'     => $user->id,
                'district_id' => $request->district_id,
                'assigned_by' => auth()->id(),
            ]);

            return [$user, $districtAdmin];
        });
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('District Admin registration failed: ' . $e->getMessage());
        return ApiResponse::error('Something went wrong while registering the district admin. No account was created — please try again.', 500);
    }

    // ── Send credentials email (failure is logged, never shown in the message) ──
    $emailSent = true;
    try {
        \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\DistrictAdminWelcomeMail(
            adminName:    $user->name,
            email:        $user->email,
            password:     $password,
            districtName: $district->name,
            stateName:    $district->state->name,
        ));
    } catch (\Exception $e) {
        $emailSent = false;
        \Illuminate\Support\Facades\Log::error('District Admin welcome email failed: ' . $e->getMessage());
    }

    return ApiResponse::success([
        'id'            => (string) $districtAdmin->id,
        'user_id'       => (string) $user->id,
        'name'          => $user->name,
        'email'         => $user->email,
        'phone'         => $user->phone,
        'password'      => $password,   // added so the super admin can share it if the email fails
        'district_id'   => (string) $district->id,
        'district_name' => $district->name,
        'state_id'      => (string) $district->state->id,
        'state_name'    => $district->state->name,
        'email_sent'    => $emailSent,
    ], 'District Admin registered successfully.', 201);
}

// ─────────────────────────────────
// SHARED DISTRICT ADMIN MAP
// ─────────────────────────────────
private function mapDistrictAdmin($da): array
{
    return [
        'id'            => (string) $da->id,
        'user_id'       => (string) $da->user_id,
        'name'          => $da->user->name,
        'email'         => $da->user->email,
        'phone'         => $da->user->phone,
        'district_id'   => (string) $da->district_id,
        'district_name' => $da->district->name ?? null,
        'state_id'      => isset($da->district->state) ? (string) $da->district->state->id : null,
        'state_name'    => $da->district->state->name ?? null,
    ];
}

// ═══════════════════════════════════════════
// GET ALL DISTRICT ADMINS
// GET /api/admin/district-admins/all?page=1&per_page=15
// Optional: state_id, district_id, keyword, is_active (1/0)
// ═══════════════════════════════════════════
public function getAllDistrictAdmins(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $request->validate([
        'keyword'     => 'nullable|string|min:2',
        'state_id'    => 'nullable|integer',
        'district_id' => 'nullable|integer',
        'is_active'   => 'nullable|in:0,1',
        'per_page'    => 'nullable|integer|min:1|max:100',
    ]);

    $query = \App\Models\DistrictAdmin::with(['user', 'district.state'])->latest();

    if ($request->filled('state_id')) {
        $query->whereHas('district', fn($q) =>
            $q->where('state_id', $request->query('state_id'))
        );
    }

    if ($request->filled('district_id')) {
        $query->where('district_id', $request->query('district_id'));
    }

    if ($request->filled('is_active')) {
        $query->whereHas('user', fn($q) =>
            $q->where('is_active', (bool) $request->query('is_active'))
        );
    }

    if ($request->filled('keyword')) {
        $kw = $request->query('keyword');
        $query->whereHas('user', fn($q) =>
            $q->where(function ($u) use ($kw) {
                $u->where('name',  'LIKE', "%$kw%")
                  ->orWhere('email', 'LIKE', "%$kw%")
                  ->orWhere('phone', 'LIKE', "%$kw%");
            })
        );
    }

    $districtAdmins = $query->paginate($request->query('per_page', 15));

    return ApiResponse::success([
        'total'           => $districtAdmins->total(),
        'current_page'    => $districtAdmins->currentPage(),
        'last_page'       => $districtAdmins->lastPage(),
        'has_more'        => $districtAdmins->hasMorePages(),
        'district_admins' => $districtAdmins->getCollection()->map(fn($da) => $this->mapDistrictAdmin($da)),
    ], 'District Admins fetched successfully.');
}

// ═══════════════════════════════════════════
// SEARCH DISTRICT ADMINS
// POST /api/admin/district-admins/search
// Body: { keyword?, state_id?, district_id?, is_active?, per_page? }
// ═══════════════════════════════════════════
public function searchDistrictAdmins(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $request->validate([
        'keyword'     => 'nullable|string|min:2',
        'state_id'    => 'nullable|integer',
        'district_id' => 'nullable|integer',
        'is_active'   => 'nullable|in:0,1',
        'per_page'    => 'nullable|integer|min:1|max:100',
    ]);

    $query = \App\Models\DistrictAdmin::with(['user', 'district.state'])->latest();

    if ($request->filled('state_id')) {
        $query->whereHas('district', fn($q) =>
            $q->where('state_id', $request->input('state_id'))
        );
    }

    if ($request->filled('district_id')) {
        $query->where('district_id', $request->input('district_id'));
    }

    if ($request->filled('is_active')) {
        $query->whereHas('user', fn($q) =>
            $q->where('is_active', (bool) $request->input('is_active'))
        );
    }

    if ($request->filled('keyword')) {
        $kw = $request->input('keyword');
        $query->where(function ($outer) use ($kw) {
            $outer->whereHas('user', fn($q) =>
                $q->where(function ($u) use ($kw) {
                    $u->where('name',  'LIKE', "%$kw%")
                      ->orWhere('email', 'LIKE', "%$kw%")
                      ->orWhere('phone', 'LIKE', "%$kw%");
                })
            )
            ->orWhereHas('district', fn($q) =>
                $q->where('name', 'LIKE', "%$kw%")
            );
        });
    }

    $districtAdmins = $query->paginate($request->input('per_page', 15));

    return ApiResponse::success([
        'total'           => $districtAdmins->total(),
        'current_page'    => $districtAdmins->currentPage(),
        'last_page'       => $districtAdmins->lastPage(),
        'has_more'        => $districtAdmins->hasMorePages(),
        'district_admins' => $districtAdmins->getCollection()->map(fn($da) => $this->mapDistrictAdmin($da)),
    ], $request->filled('keyword') ? 'Search results fetched.' : 'All district admins fetched.');
}

// ═══════════════════════════════════════════
// DELETE DISTRICT ADMIN (password confirmation required)
// POST /api/admin/district-admins/delete
// Body: { id, password }
// ═══════════════════════════════════════════
public function deleteDistrictAdmin(Request $request)
{
    if (!$this->isSuperAdmin()) return $this->unauthorised();

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'id'       => 'required|exists:district_admins,id',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // Verify the logged-in super admin's password
    if (!\Illuminate\Support\Facades\Hash::check($request->input('password'), auth()->user()->password)) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => 'Incorrect password. District Admin was not deleted.',
            'data'        => (object) [],
        ], 422);
    }

    $districtAdmin = \App\Models\DistrictAdmin::with('user')->find($request->id);
    $user          = $districtAdmin->user;

    try {
        \Illuminate\Support\Facades\DB::transaction(function () use ($districtAdmin, $user) {
            $districtAdmin->delete();

            if ($user) {
                $user->tokens()->delete();
                $user->delete();
            }
        });
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('District Admin delete failed: ' . $e->getMessage());
        return ApiResponse::error('Could not delete the district admin. Please try again.', 500);
    }

    return ApiResponse::success(null, 'District Admin deleted successfully.');
}

}