<?php

namespace App\Http\Controllers\APIDistrictAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Models\Unit;
use App\Models\UnitAdmin;
use App\Models\DistrictAdmin;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DistrictAdminApiController extends Controller
{
    // ─────────────────────────────────
    // STRING RESPONSE HELPERS
    // Every value in "data" is converted to a string.
    // null  -> ""   |   true/false -> "true"/"false"   |   numbers -> "3"
    // ─────────────────────────────────
    private function stringify($data)
    {
        if ($data instanceof \Illuminate\Support\Collection) {
            $data = $data->all();
        }

        if (is_array($data)) {
            return array_map(fn($v) => $this->stringify($v), $data);
        }

        if (is_null($data)) return '';
        if (is_bool($data)) return $data ? 'true' : 'false';

        if (is_object($data)) {
            return method_exists($data, '__toString') ? (string) $data : $data;
        }

        return (string) $data;
    }

    // Use this instead of ApiResponse::success()
    private function ok($data, string $message, int $code = 200)
    {
        return ApiResponse::success(
            $data === null ? null : $this->stringify($data),
            $message,
            $code
        );
    }

    // ─────────────────────────────────
    // GUARDS
    // ─────────────────────────────────
    private function isDistrictAdmin(): bool
    {
        return auth()->user()?->role === 'district_admin';
    }

    private function unauthorised()
    {
        return ApiResponse::error('Unauthorized. District Admin access only.', 403);
    }

    private function getDistrictAdmin(): ?DistrictAdmin
    {
        return DistrictAdmin::where('user_id', auth()->id())->first();
    }

    private function validationError($validator)
    {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    // ─────────────────────────────────
    // SLIM MEMBER MAP — for list views
    // ─────────────────────────────────
    private function mapMemberSlim($p): array
{
    return [
        'id'              => (string) $p->id,
        'name'            => $p->user->name,
        'phone'           => $p->user->phone,
        'membership_id'   => $p->membership_id,
        'unit_id'         => (string) $p->unit_id,
        'unit_name'       => $p->unit->name ?? null,
        'status'          => $p->status,
        'is_fumma_member' => (int) $p->is_fumma_member,
        'registered_on'   => $p->created_at->format('d M Y'),
        'photo'           => $p->photo ? asset('storage/' . $p->photo) : null,
    ];
}

    // ─────────────────────────────────
    // DETAILED MEMBER MAP — for detail view
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
            'unit_id'       => (string) $p->unit_id,
            'unit_name'     => $p->unit->name ?? null,
            'district_name' => $p->district->name ?? null,
            'state_name'    => $p->state->name ?? null,
            'joined_date'   => $p->joined_date,
            'expiry_date'   => $p->expiry_date,
            'registered_on' => $p->created_at->format('d M Y'),
            'is_fumma_member' => (int) $p->is_fumma_member,
        ];
    }

    // ─────────────────────────────────
    // PAGINATED RESPONSE
    // ─────────────────────────────────
    private function paginatedResponse($members, string $message): \Illuminate\Http\JsonResponse
    {
        return $this->ok([
            'total'        => $members->total(),
            'current_page' => $members->currentPage(),
            'last_page'    => $members->lastPage(),
            'has_more'     => $members->hasMorePages(),
            'members'      => $members->getCollection()->map(fn($p) => $this->mapMemberSlim($p)),
        ], $message);
    }

    // ─────────────────────────────────
    // MEMBER QUERY BUILDER
    // Always scoped to the district admin's district_id.
    // Optional filters: status, unit_id, keyword
    // ─────────────────────────────────
    private function buildMemberQuery(Request $request, int $districtId, ?string $status = null)
    {
        $query = MemberProfile::with(['user', 'unit', 'district', 'state'])
            ->where('district_id', $districtId)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->input('unit_id'));
        }

        if ($request->filled('keyword')) {
            $kw = $request->input('keyword');
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
    // MY DISTRICT INFO
    // GET /api/district/info
    // ═══════════════════════════════════════════
    public function myDistrictInfo(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = DistrictAdmin::with(['user', 'district.state'])
            ->where('user_id', auth()->id())->first();

        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $districtId = $districtAdmin->district_id;

        return $this->ok([   // outer [ ] makes "data" a list
            [                // inner [ ] is the object { }
                'district_admin' => [
                    'id'    => (string) $districtAdmin->id,
                    'name'  => $districtAdmin->user->name,
                    'email' => $districtAdmin->user->email,
                    'phone' => $districtAdmin->user->phone,
                ],
                'district' => [
                    'id'    => (string) $districtId,
                    'name'  => $districtAdmin->district->name ?? null,
                    'state' => $districtAdmin->district->state->name ?? null,
                ],
                'totals' => [
                    'units'       => Unit::where('district_id', $districtId)->count(),
                    'unit_admins' => UnitAdmin::whereHas('unit', fn($q) => $q->where('district_id', $districtId))->count(),
                ],
                'member_counts' => [
                    'total'    => MemberProfile::where('district_id', $districtId)->count(),
                    'pending'  => MemberProfile::where('district_id', $districtId)->where('status', 'PENDING')->count(),
                    'approved' => MemberProfile::where('district_id', $districtId)->where('status', 'APPROVED')->count(),
                    'rejected' => MemberProfile::where('district_id', $districtId)->where('status', 'REJECTED')->count(),
                ],
            ]
        ], 'District info fetched successfully.');
    }

    // ═══════════════════════════════════════════
    // UNITS IN MY DISTRICT (for dropdowns / filters)
    // GET /api/district/units
    // ═══════════════════════════════════════════
    public function units(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $units = Unit::where('district_id', $districtAdmin->district_id)
            ->withCount('memberProfiles')
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id'            => (string) $u->id,
                'name'          => $u->name,
                'members_count' => $u->member_profiles_count,
            ]);

        return $this->ok($units, 'Units fetched successfully.');
    }

    // ═══════════════════════════════════════════
    // ALL MEMBERS
    // POST /api/district/members?page=1&per_page=15
    // Body (all optional): { status, unit_id, keyword }
    // ═══════════════════════════════════════════
    public function members(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $districtAdmin->district_id)
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Members fetched successfully.');
    }

    // GET /api/district/members/pending?page=1&per_page=15&unit_id=1
    public function pendingMembers(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $districtAdmin->district_id, 'PENDING')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Pending members fetched.');
    }

    // GET /api/district/members/approved?page=1&per_page=15&unit_id=1
    public function approvedMembers(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $districtAdmin->district_id, 'APPROVED')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Approved members fetched.');
    }

    // GET /api/district/members/rejected?page=1&per_page=15&unit_id=1
    public function rejectedMembers(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $districtAdmin->district_id, 'REJECTED')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Rejected members fetched.');
    }

    // ═══════════════════════════════════════════
    // SEARCH MEMBERS
    // POST /api/district/members/search?page=1&per_page=15
    // Body: { "keyword": "john", "unit_id": 1 (optional) }
    // ═══════════════════════════════════════════
    public function searchMembers(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        if ($request->filled('keyword')) {
            $request->validate(['keyword' => 'string|min:2']);
        }

        $members = $this->buildMemberQuery($request, $districtAdmin->district_id)
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Search results fetched.');
    }

    // ═══════════════════════════════════════════
    // MEMBER DETAIL
    // POST /api/district/members/detail
    // Body: { id }
    // ═══════════════════════════════════════════
    public function memberDetail(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $request->validate(['id' => 'required|integer']);

$p = MemberProfile::with(['user', 'unit', 'district', 'state'])->find($request->id);

if (!$p) {
    return ApiResponse::error('Member not found.', 404);
}

        if ((int) $p->district_id !== (int) $districtAdmin->district_id) {
            return ApiResponse::error('Access denied. This member does not belong to your district.', 403);
        }

        return $this->ok($this->mapMemberDetail($p), 'Member detail fetched successfully.');
    }

        // ═══════════════════════════════════════════
    // APPROVE MEMBER
    // POST /api/district/members/approve
    // Body: { id }
    // ═══════════════════════════════════════════
    public function approveMember(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $request->validate(['id' => 'required|integer']);

        $profile = MemberProfile::with(['user', 'unit'])->find($request->id);

        if (!$profile) {
            return ApiResponse::error('Member not found.', 404);
        }

        if ((int) $profile->district_id !== (int) $districtAdmin->district_id) {
            return ApiResponse::error('Access denied. This member does not belong to your district.', 403);
        }

        if (!in_array($profile->status, ['PENDING', 'REJECTED'])) {
            return ApiResponse::error('Only PENDING or REJECTED members can be approved.', 400);
        }

        // ── Approve + generate Membership ID ──
        $profile = app(\App\Services\MembershipService::class)->approve($profile);

        if (!empty($profile->user->email)) {
    try {
        Mail::to($profile->user->email)->send(new \App\Mail\MemberApprovedMail(
            memberName:   $profile->user->name,
            membershipId: $profile->membership_id,
            unitName:     $profile->unit->name ?? 'ECA Unit',
            phone:        $profile->user->phone,
        ));
    } catch (\Throwable $e) {
        Log::error('Approval email failed: ' . $e->getMessage());
    }
}

        return $this->ok([
            'id'            => (string) $profile->id,
            'name'          => $profile->user->name,
            'status'        => $profile->status,
            'membership_id' => $profile->membership_id,
            'joined_date'   => $profile->joined_date,
        ], 'Member approved successfully. Membership ID: ' . $profile->membership_id);
    }

    // ═══════════════════════════════════════════
    // REJECT MEMBER
    // POST /api/district/members/reject
    // Body: { id, reason }
    // ═══════════════════════════════════════════
    public function rejectMember(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $request->validate([
    'id'     => 'required|integer',
    'reason' => 'nullable|string',
]);

$profile = MemberProfile::with(['user', 'unit'])->find($request->id);

if (!$profile) {
    return ApiResponse::error('Member not found.', 404);
}

        if ((int) $profile->district_id !== (int) $districtAdmin->district_id) {
            return ApiResponse::error('Access denied. This member does not belong to your district.', 403);
        }

        if (!in_array($profile->status, ['PENDING', 'APPROVED'])) {
            return ApiResponse::error('Only PENDING or APPROVED members can be rejected.', 400);
        }

        $profile->status = 'REJECTED';
        $profile->save();

        if (!empty($profile->user->email)) {
    try {
        Mail::to($profile->user->email)->send(new \App\Mail\MemberStatusMail(
            memberName:   $profile->user->name,
            unitName:     $profile->unit->name ?? 'ECA',
            status:       'rejected',
            reason:       $request->reason,
            contactEmail: $this->contactEmailFor($profile),
            loginUrl:     url('/login'),
        ));
    } catch (\Throwable $e) {
        Log::error('Rejection email failed: ' . $e->getMessage());
    }
}

        return $this->ok([
            'id'     => (string) $profile->id,
            'name'   => $profile->user->name,
            'status' => $profile->status,
            'reason' => $request->reason,
        ], 'Member rejected successfully.');
    }

    // Contact email shown in status mails = the member's unit admin, else the app default
    private function contactEmailFor($profile): string
    {
        return UnitAdmin::with('user')->where('unit_id', $profile->unit_id)
            ->first()?->user?->email
            ?? config('mail.from.address');
    }

// ═══════════════════════════════════════════
// REGISTER MEMBER (any unit inside own district) — auto-approved
// POST /api/district/members/register
// Member skips PENDING: Membership ID + joined_date are generated immediately.
// Login = Membership ID + phone number. Approval email is sent if an email was given.
// Body: { name, email?, phone, dob, organization, address,
//         unit_id, is_fumma_member?, photo (base64, required) }
// district_id / state_id auto-filled from the district admin
// ═══════════════════════════════════════════
public function registerMember(Request $request)
{
    if (!$this->isDistrictAdmin()) return $this->unauthorised();

    $districtAdmin = DistrictAdmin::with('district.state')->where('user_id', auth()->id())->first();
    if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

    $districtId = $districtAdmin->district_id;

    $validator = Validator::make($request->all(), [
        'name'         => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/'],
        'email'        => ['nullable', 'email:rfc,dns', 'max:150', Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'member'))],
        'phone'        => ['required', 'digits:10', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,phone'],
        'dob'          => [
            'required', 'date', 'before:today',
            function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->age < 5) {
                    $fail('Member must be at least 5 years old.');
                }
            },
        ],
        'photo'           => 'required|string', // base64 (jpg/jpeg/png, max 2MB)
        'organization'    => 'required|string|max:150',
        'address'         => 'required|string|max:500',
        'unit_id'         => [
            'required',
            Rule::exists('units', 'id')->where(fn($q) => $q->where('district_id', $districtId)),
        ],
        'is_fumma_member' => 'nullable|in:0,1',
    ], [
        'name.regex'       => 'Name can only contain letters, spaces, hyphens and apostrophes.',
        'email.dns'        => 'This email domain does not appear to accept mail.',
        'email.unique'     => 'Email is already taken.',
        'phone.unique'     => 'Phone number is already taken.',
        'address.required' => 'Address is required.',
        'address.max'      => 'Address cannot exceed 500 characters.',
        'photo.required'   => 'Member photo is required.',
        'unit_id.exists'   => 'Please select a valid unit within your district.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) return $this->validationError($validator);

    // Email is optional: always store NULL (never '') so the unique index stays safe
    $email = trim((string) $request->input('email')) !== '' ? trim($request->input('email')) : null;

    $fee      = \App\Models\RegistrationFee::latest()->first(); // optional
    $unit     = Unit::find($request->unit_id);
    $district = $districtAdmin->district;
    $state    = $district->state;

    // ── Photo (base64, required) ──
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
    Storage::disk('public')->put('member_photos/' . $fileName, $image);
    $photoPath = 'member_photos/' . $fileName;

    // ── Create user + profile + approve, all atomically ──
    try {
        [$user, $profile] = DB::transaction(function () use ($request, $fee, $email, $unit, $district, $state, $photoPath) {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $email,
                'phone'     => $request->phone,
                'password'  => Hash::make($request->phone),
                'role'      => 'member',
                'is_active' => true,
            ]);

            $profile = MemberProfile::create([
                'user_id'         => $user->id,
                'fee_id'          => $fee?->id,
                'plan_id'         => null,
                'unit_id'         => $unit->id,
                'district_id'     => $district->id,
                'state_id'        => $state->id,
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
        Log::error('District member registration failed: ' . $e->getMessage());
        Storage::disk('public')->delete($photoPath);
        return ApiResponse::error('Something went wrong while registering the member. Please try again.', 500);
    }

    // ── Approval email (outside the transaction, so a mail failure can't roll anything back) ──
    $emailSent = false;
    if (!empty($user->email)) {
        try {
            Mail::to($user->email)->send(new \App\Mail\MemberApprovedMail(
                memberName:   $user->name,
                membershipId: $profile->membership_id,
                unitName:     $unit->name ?? 'ECA Unit',
                phone:        $user->phone,
            ));
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::error('Approval email failed for member #' . $profile->id . ': ' . $e->getMessage());
        }
    }

    $message = 'Member registered and approved successfully. Membership ID: ' . $profile->membership_id . '.';
    if (empty($user->email)) {
        $message .= ' No email given, share the login details manually.';
    } elseif ($emailSent) {
        $message .= ' Login details emailed to ' . $user->email . '.';
    } else {
        $message .= ' Could not send the email, share the login details manually.';
    }

    return $this->ok([
        'id'              => (string) $profile->id,   // member profile id
        'user_id'         => (string) $user->id,
        'name'            => $user->name,
        'email'           => $user->email,            // "" if not given
        'phone'           => $user->phone,
        'dob'             => $request->dob,
        'organization'    => $profile->organization,
        'address'         => $profile->address,
        'is_fumma_member' => (int) $profile->is_fumma_member,
        'status'          => $profile->status,        // "APPROVED"
        'membership_id'   => $profile->membership_id,
        'joined_date'     => $profile->joined_date,
        'photo'           => asset('storage/' . $photoPath),
        'unit_id'         => (string) $unit->id,
        'unit_name'       => $unit->name,
        'district_name'   => $district->name,
        'state_name'      => $state->name,
        'email_sent'      => $emailSent,
    ], $message, 201);
}

    // ═══════════════════════════════════════════
    // DELETE MEMBER (own district only)
    // POST /api/district/members/delete
    // Body: { id }
    // ═══════════════════════════════════════════
    // ═══════════════════════════════════════════
// DELETE MEMBER (own district only, password confirmation required)
// POST /api/district/members/delete
// Body: { id, password }
// ═══════════════════════════════════════════
public function deleteMember(Request $request)
{
    if (!$this->isDistrictAdmin()) return $this->unauthorised();

    $districtAdmin = $this->getDistrictAdmin();
    if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

    $validator = Validator::make($request->all(), [
        'id'       => 'required|integer',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) return $this->validationError($validator);

    // Verify the logged-in district admin's password
    $admin = auth()->user();

    if (!Hash::check($request->input('password'), $admin->password)) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => 'Incorrect password. Member was not deleted.',
            'data'        => (object) [],
        ], 422);
    }

    $profile = MemberProfile::find($request->input('id'));

    if (!$profile) {
        return ApiResponse::error('Member not found.', 404);
    }

    if ((int) $profile->district_id !== (int) $districtAdmin->district_id) {
        return ApiResponse::error('Access denied. This member does not belong to your district.', 403);
    }

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, $admin->id, 'district_admin_api');
    } catch (\Throwable $e) {
        Log::error('Member delete failed for profile #' . $profile->id . ': ' . $e->getMessage());
        return ApiResponse::error('Could not delete this member. They may still have linked records.', 409);
    }

    return $this->ok(null, 'Member deleted successfully.');
}
    
    // ═══════════════════════════════════════════
    // UNIT ADMINS / UNIT DETAIL (own district only)
    // POST /api/district/unit-admins?page=1&per_page=10
    // Body: { unit_id?, status?, keyword? }
    // - with unit_id -> unit + unit admin + member counts + members
    // - without      -> paginated list of unit admins in my district
    // ═══════════════════════════════════════════
    public function unitAdmins(Request $request)
    {
        if (!$this->isDistrictAdmin()) return $this->unauthorised();

        $districtAdmin = $this->getDistrictAdmin();
        if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

        $districtId = (int) $districtAdmin->district_id;

        // ── unit_id provided -> unit detail ──
        if ($request->filled('unit_id')) {

            $request->validate([
                'unit_id' => 'required|integer',
                'status'  => 'nullable|in:PENDING,APPROVED,REJECTED',
            ]);

            $unitId = (int) $request->unit_id;

            // Unit must belong to this district admin's district
            $unit = Unit::with('district.state')
                ->where('id', $unitId)
                ->where('district_id', $districtId)
                ->first();

            if (!$unit) {
                return ApiResponse::error('Unit not found in your district.', 404);
            }

            // ── Unit Admin ──
            $ua        = UnitAdmin::with('user')->where('unit_id', $unitId)->first();
            $unitAdmin = $ua ? [
                'id'        => (string) $ua->id,
                'name'      => $ua->user->name ?? null,
                'phone'     => $ua->user->phone ?? null,
                'email'     => $ua->user->email ?? null,
                'is_active' => $ua->user->is_active ?? false,
            ] : null;

            // ── Member counts ──
            $counts = [
                'total'    => MemberProfile::where('unit_id', $unitId)->count(),
                'pending'  => MemberProfile::where('unit_id', $unitId)->where('status', 'PENDING')->count(),
                'approved' => MemberProfile::where('unit_id', $unitId)->where('status', 'APPROVED')->count(),
                'rejected' => MemberProfile::where('unit_id', $unitId)->where('status', 'REJECTED')->count(),
            ];

            // ── Members ──
            $membersQuery = MemberProfile::with('user')
                ->where('unit_id', $unitId)
                ->latest();

            if ($request->filled('status')) {
                $membersQuery->where('status', $request->status);
            }

            $members = $membersQuery->paginate($request->query('per_page', 10));

            $memberList = $members->getCollection()->map(function ($p) {

                $base = [
                    'id'    => (string) $p->id,
                    'name'  => $p->user->name ?? null,
                    'phone' => $p->user->phone ?? null,
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
                            ? Carbon::parse($p->joined_date)->format('d M Y')
                            : null,
                    ]);
                }

                return array_merge($base, [
                    'status'        => $p->status,
                    'registered_on' => $p->created_at->format('d M Y'),
                ]);
            });

            return $this->ok([
                'unit' => [
                    'id'       => (string) $unit->id,
                    'name'     => $unit->name,
                    'district' => $unit->district->name ?? null,
                    'state'    => $unit->district->state->name ?? null,
                ],
                'unit_admin'    => $unitAdmin,
                'member_counts' => $counts,
                'members'       => [
                    'total'         => $members->total(),
                    'current_page'  => $members->currentPage(),
                    'last_page'     => $members->lastPage(),
                    'has_more'      => $members->hasMorePages(),
                    'status_filter' => $request->status ?? 'ALL',
                    'data'          => $memberList,
                ],
            ], 'Unit detail fetched successfully.');
        }

        // ── no unit_id -> list of unit admins in my district ──
        $query = UnitAdmin::with(['user', 'unit.district.state'])
            ->whereHas('unit', fn($q) => $q->where('district_id', $districtId))
            ->latest();

        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $query->whereHas('user', fn($q) =>
                $q->where('name',  'LIKE', "%$kw%")
                  ->orWhere('email', 'LIKE', "%$kw%")
                  ->orWhere('phone', 'LIKE', "%$kw%")
            );
        }

        $unitAdmins = $query->paginate($request->query('per_page', 15));

        return $this->ok([
            'total'        => $unitAdmins->total(),
            'current_page' => $unitAdmins->currentPage(),
            'last_page'    => $unitAdmins->lastPage(),
            'has_more'     => $unitAdmins->hasMorePages(),
            'unit_admins'  => $unitAdmins->getCollection()->map(fn($ua) => [
                'id'        => (string) $ua->id,
                'name'      => $ua->user->name ?? null,
                'phone'     => $ua->user->phone ?? null,
                'is_active' => $ua->user->is_active ?? false,
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
        'user_id'   => (string) $ua->user_id,
        'name'      => $ua->user->name ?? null,
        'email'     => $ua->user->email ?? null,
        'phone'     => $ua->user->phone ?? null,
        'is_active' => $ua->user->is_active ?? false,
        'unit_id'   => (string) $ua->unit_id,
        'unit_name' => $ua->unit->name ?? null,
        'district'  => $ua->unit->district->name ?? null,
        'state'     => $ua->unit->district->state->name ?? null,
    ];
}

// ═══════════════════════════════════════════
// GET ALL UNIT ADMINS (own district only)
// GET /api/district/unit-admins/all?page=1&per_page=15
// Optional: keyword, unit_id, is_active (1/0)
// ═══════════════════════════════════════════
public function getAllUnitAdmins(Request $request)
{
    if (!$this->isDistrictAdmin()) return $this->unauthorised();

    $districtAdmin = $this->getDistrictAdmin();
    if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

    $validator = Validator::make($request->all(), [
        'keyword'   => 'nullable|string|min:2',
        'unit_id'   => 'nullable|integer',
        'is_active' => 'nullable|in:0,1',
        'per_page'  => 'nullable|integer|min:1|max:100',
    ])->stopOnFirstFailure();

    if ($validator->fails()) return $this->validationError($validator);

    $districtId = (int) $districtAdmin->district_id;

    // Always scoped to this district admin's district
    $query = UnitAdmin::with(['user', 'unit.district.state'])
        ->whereHas('unit', fn($q) => $q->where('district_id', $districtId))
        ->latest();

    if ($request->filled('unit_id')) {
        $query->where('unit_id', $request->input('unit_id'));
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
            ->orWhereHas('unit', fn($q) =>
                $q->where('name', 'LIKE', "%$kw%")
            );
        });
    }

    $unitAdmins = $query->paginate($request->input('per_page', 15));

    return $this->ok([
        'total'        => $unitAdmins->total(),
        'current_page' => $unitAdmins->currentPage(),
        'last_page'    => $unitAdmins->lastPage(),
        'has_more'     => $unitAdmins->hasMorePages(),
        'unit_admins'  => $unitAdmins->getCollection()->map(fn($ua) => $this->mapUnitAdmin($ua)),
    ], 'Unit Admins fetched successfully.');
}
    
    // ═══════════════════════════════════════════
// ADD UNIT (own district only)
// POST /api/district/units/add
// Body: { name }
// district_id is taken from the logged-in district admin
// ═══════════════════════════════════════════
public function addUnit(Request $request)
{
    if (!$this->isDistrictAdmin()) return $this->unauthorised();

    $districtAdmin = DistrictAdmin::with('district.state')->where('user_id', auth()->id())->first();
    if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

    $district   = $districtAdmin->district;
    $districtId = (int) $districtAdmin->district_id;

    $validator = Validator::make($request->all(), [
        'name' => [
            'required', 'string', 'min:2', 'max:100',
            Rule::unique('units', 'name')->where(fn($q) => $q->where('district_id', $districtId)),
        ],
    ], [
        'name.unique' => 'A unit with this name already exists in your district.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) return $this->validationError($validator);

    $unit = Unit::create([
        'name'        => trim($request->name),
        'district_id' => $districtId,
        'is_active'   => true,
    ]);

    return $this->ok([
        'id'            => (string) $unit->id,
        'name'          => $unit->name,
        'district_id'   => (string) $district->id,
        'district_name' => $district->name,
        'state_id'      => (string) ($district->state->id ?? ''),
        'state_name'    => $district->state->name ?? null,
    ], 'Unit added successfully.', 201);
}

// ═══════════════════════════════════════════
// REGISTER UNIT ADMIN (unit inside own district only)
// POST /api/district/unit-admins/register
// Body: { name, email, phone, unit_id }
// Password is auto-generated and emailed
// ═══════════════════════════════════════════
public function registerUnitAdmin(Request $request)
{
    if (!$this->isDistrictAdmin()) return $this->unauthorised();

    $districtAdmin = $this->getDistrictAdmin();
    if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

    $districtId = (int) $districtAdmin->district_id;

    $validator = Validator::make($request->all(), [
        'name'    => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/'],
        'email'   => ['required', 'email:rfc,dns', 'max:150', Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'unit_admin'))],
        'phone'   => ['required', 'digits:10', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,phone'],
        'unit_id' => [
            'required',
            Rule::exists('units', 'id')->where(fn($q) => $q->where('district_id', $districtId)),
        ],
    ], [
        'name.regex'     => 'Name can only contain letters, spaces, hyphens and apostrophes.',
        'email.dns'      => 'This email domain does not appear to accept mail.',
        'email.unique'   => 'Email is already taken.',
        'phone.unique'   => 'Phone number is already taken.',
        'unit_id.exists' => 'Please select a valid unit within your district.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) return $this->validationError($validator);

    // ── One admin per unit ──
    if (UnitAdmin::where('unit_id', $request->unit_id)->exists()) {
        return ApiResponse::error('This unit already has an admin assigned.', 400);
    }

    $unit     = Unit::with('district.state')->find($request->unit_id);
    $district = $unit->district;

    // ── Auto-generate password: FirstName@randomNumber ──
    $firstName = explode(' ', trim($request->name))[0];
    $password  = $firstName . '@' . random_int(1000, 9999);

    try {
        [$user, $unitAdmin] = DB::transaction(function () use ($request, $password, $unit) {
            $user = User::create([
                'name'      => trim($request->name),
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => Hash::make($password),
                'role'      => 'unit_admin',
                'is_active' => true,
            ]);

            $unitAdmin = UnitAdmin::create([
                'user_id'     => $user->id,
                'unit_id'     => $unit->id,
                'assigned_by' => auth()->id(),
            ]);

            return [$user, $unitAdmin];
        });
    } catch (\Exception $e) {
        Log::error('District: Unit Admin registration failed: ' . $e->getMessage());
        return ApiResponse::error('Something went wrong while registering the unit admin. No account was created, please try again.', 500);
    }

    // ── Credentials email ──
    $emailSent = true;
    try {
        Mail::to($user->email)->send(new \App\Mail\UnitAdminWelcomeMail(
            adminName:    $user->name,
            email:        $user->email,
            password:     $password,
            unitName:     $unit->name,
            districtName: $district->name,
        ));
    } catch (\Exception $e) {
        $emailSent = false;
        Log::error('Unit Admin welcome email failed: ' . $e->getMessage());
    }

    return $this->ok([
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
        'state_id'      => (string) ($district->state->id ?? ''),
        'state_name'    => $district->state->name ?? null,
        'email_sent'    => $emailSent,
    ], $emailSent
        ? 'Unit Admin registered successfully. Credentials emailed.'
        : 'Unit Admin registered successfully, but the credentials email failed to send. Share the password shown below manually.',
        201);
}
// ═══════════════════════════════════════════
// DELETE UNIT ADMIN (unit inside own district only, password confirmation required)
// POST /api/district/unit-admins/delete
// Body: { id, password }   (id = unit admin id, from the unit-admins list)
// ═══════════════════════════════════════════
public function deleteUnitAdmin(Request $request)
{
    if (!$this->isDistrictAdmin()) return $this->unauthorised();

    $districtAdmin = $this->getDistrictAdmin();
    if (!$districtAdmin) return ApiResponse::error('District Admin record not found.', 404);

    $validator = Validator::make($request->all(), [
        'id'       => 'required|integer',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ])->stopOnFirstFailure();

    if ($validator->fails()) return $this->validationError($validator);

    // Verify the logged-in district admin's password
    if (!Hash::check($request->input('password'), auth()->user()->password)) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => 'Incorrect password. Unit Admin was not deleted.',
            'data'        => (object) [],
        ], 422);
    }

    $unitAdmin = UnitAdmin::with(['user', 'unit'])->find($request->input('id'));

    if (!$unitAdmin) {
        return ApiResponse::error('Unit Admin not found.', 404);
    }

    // ── Scope check: the unit must be inside this district admin's district ──
    if ((int) ($unitAdmin->unit->district_id ?? 0) !== (int) $districtAdmin->district_id) {
        return ApiResponse::error('Access denied. This unit admin does not belong to your district.', 403);
    }

    $user = $unitAdmin->user;

    try {
        DB::transaction(function () use ($unitAdmin, $user) {
            $unitAdmin->delete();

            if ($user) {
                $user->tokens()->delete(); // log the unit admin out of every device
                $user->delete();
            }
        });
    } catch (\Throwable $e) {
        Log::error('District: Unit Admin delete failed: ' . $e->getMessage());
        return ApiResponse::error('Could not delete the unit admin. Please try again.', 500);
    }

    return $this->ok(null, 'Unit Admin deleted successfully.');
}
}