<?php
namespace App\Http\Controllers\APIUnitAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResponse;
use App\Models\User;
use App\Models\UnitAdmin;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UnitAdminApiController extends Controller
{
    // ─────────────────────────────────
    // GUARDS
    // ─────────────────────────────────
    private function isUnitAdmin(): bool
    {
        return auth()->user()?->role === 'unit_admin';
    }

    private function unauthorised()
    {
        return ApiResponse::error('Unauthorized. Unit Admin access only.', 403);
    }

    private function getUnitAdmin(): ?UnitAdmin
    {
        return UnitAdmin::where('user_id', auth()->id())->first();
    }

    // ─────────────────────────────────
    // SHARED MEMBER MAP (slim) — for list views
    // ─────────────────────────────────
    private function mapMemberSlim($p): array
{
    return [
        'id'              => (string) $p->id,
        'name'            => $p->user->name,
        'phone'           => $p->user->phone,
        'membership_id'   => $p->membership_id,
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
            'unit_name'     => $p->unit->name ?? null,
            'state_name'    => $p->state->name ?? null,
            'joined_date'   => $p->joined_date,
            'registered_on' => $p->created_at->format('d M Y'),
            'is_fumma_member' => (int) $p->is_fumma_member,
        ];
    }

    // ─────────────────────────────────
    // SHARED PAGINATED RESPONSE
    // ─────────────────────────────────
    private function paginatedResponse($members, string $message): \Illuminate\Http\JsonResponse
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
    // Always scoped to unit admin's unit_id
    // keyword from BODY | page, per_page from URL
    // ─────────────────────────────────
    private function buildMemberQuery(Request $request, int $unitId, ?string $status = null)
    {
        $query = MemberProfile::with(['user', 'unit', 'state'])
            ->where('unit_id', $unitId)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        } elseif ($request->filled('status')) {
            $query->where('status', $request->input('status'));
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
// REGISTER MEMBER (Unit Admin, own unit only) — auto-approved
// POST /api/unit/members/register
// Member skips PENDING: Membership ID + joined_date are generated immediately.
// Login = Membership ID + phone number. Approval email is sent if an email was given.
// Body: { name, email?, phone, dob, organization, address,
//         is_fumma_member?, photo (base64, required) }
// ═══════════════════════════════════════════
public function registerMember(Request $request)
{
    if (!$this->isUnitAdmin()) return $this->unauthorised();

    $unitAdmin = $this->getUnitAdmin();
    if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

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

    // ── Get registration fee ──
    $fee = \App\Models\RegistrationFee::latest()->first();
    if (!$fee) {
        return ApiResponse::error('No registration fee configured. Contact admin.', 400);
    }

    // ── Resolve unit from unit admin's unit ──
    $unit = \App\Models\Unit::find($unitAdmin->unit_id);
    if (!$unit) {
        return ApiResponse::error('Unit configuration is incomplete. Contact super admin.', 500);
    }

    $state = \App\Models\State::fixed() ?? \App\Models\State::first();

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
        [$user, $profile] = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $fee, $email, $unit, $state, $photoPath) {
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
                'unit_id'         => $unit->id,
                'state_id'        => $state?->id,
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
        \Illuminate\Support\Facades\Log::error('Unit member registration failed: ' . $e->getMessage());
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
        'email'           => $user->email,            // null if not given
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
        'state_name'      => $state?->name,
        'email_sent'      => $emailSent,
    ], $message, 201);
}


// ═══════════════════════════════════════════
// DELETE MEMBER (Unit Admin, own unit only, password confirmation required)
// POST /api/unit/members/delete
// Body: { id, password }
// ═══════════════════════════════════════════
public function deleteMember(Request $request)
{
    if (!$this->isUnitAdmin()) return $this->unauthorised();

    $unitAdmin = $this->getUnitAdmin();
    if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

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

    // Same status code as the district/super admin APIs (422, so the app doesn't treat it as a logout)
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

    if ((int) $profile->unit_id !== (int) $unitAdmin->unit_id) {
        return ApiResponse::error('Access denied. This member does not belong to your unit.', 403);
    }

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, $admin->id, 'unit_admin_api');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Member delete failed for profile #' . $profile->id . ': ' . $e->getMessage());
        return ApiResponse::error('Could not delete this member. They may still have linked records.', 409);
    }

    return ApiResponse::success(null, 'Member deleted successfully.');
}
    // ═══════════════════════════════════════════
    // ALL MEMBERS
    // POST /api/unit/members?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function members(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $unitAdmin->unit_id)
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Members fetched successfully.');
    }

    // ═══════════════════════════════════════════
    // PENDING MEMBERS
    // GET /api/unit/members/pending?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function pendingMembers(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $unitAdmin->unit_id, 'PENDING')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Pending members fetched.');
    }

    // ═══════════════════════════════════════════
    // APPROVED MEMBERS
    // GET /api/unit/members/approved?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function approvedMembers(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $unitAdmin->unit_id, 'APPROVED')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Approved members fetched.');
    }

    // ═══════════════════════════════════════════
    // REJECTED MEMBERS
    // GET /api/unit/members/rejected?page=1&per_page=15
    // ═══════════════════════════════════════════
    public function rejectedMembers(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $members = $this->buildMemberQuery($request, $unitAdmin->unit_id, 'REJECTED')
                        ->paginate($request->query('per_page', 15));

        return $this->paginatedResponse($members, 'Rejected members fetched.');
    }

    // ═══════════════════════════════════════════
    // SEARCH MEMBERS
    // POST /api/unit/members/search?page=1&per_page=15
    // Body: { "keyword": "john" }
    // ═══════════════════════════════════════════
    public function searchMembers(Request $request)
{
    if (!$this->isUnitAdmin()) return $this->unauthorised();

    $unitAdmin = $this->getUnitAdmin();
    if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

    // Removed required|min:2 — empty keyword now returns all members
    if ($request->filled('keyword')) {
        $request->validate(['keyword' => 'string|min:2']);
    }

    $members = $this->buildMemberQuery($request, $unitAdmin->unit_id)
                    ->paginate($request->query('per_page', 15));

    return $this->paginatedResponse($members, 'Search results fetched.');
}

    // ═══════════════════════════════════════════
    // MEMBER DETAIL
    // POST /api/unit/members/detail
    // Body: { id }
    // ═══════════════════════════════════════════
    public function memberDetail(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $request->validate(['id' => 'required|exists:member_profiles,id']);

        $p = MemberProfile::with(['user', 'unit', 'state'])->find($request->id);

        if ((int) $p->unit_id !== (int) $unitAdmin->unit_id) {
            return ApiResponse::error('Access denied. This member does not belong to your unit.', 403);
        }

        return ApiResponse::success($this->mapMemberDetail($p), 'Member detail fetched successfully.');
    }

    // ═══════════════════════════════════════════
    // APPROVE MEMBER
    // POST /api/unit/members/approve
    // Body: { id }
    // ═══════════════════════════════════════════
    public function approveMember(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $request->validate(['id' => 'required|exists:member_profiles,id']);

        $profile = MemberProfile::with(['user', 'unit'])->find($request->id);

        if ((int) $profile->unit_id !== (int) $unitAdmin->unit_id) {
            return ApiResponse::error('Access denied. This member does not belong to your unit.', 403);
        }

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
    // POST /api/unit/members/reject
    // Body: { id, reason }
    // ═══════════════════════════════════════════
    public function rejectMember(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = $this->getUnitAdmin();
        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $request->validate([
            'id'     => 'required|exists:member_profiles,id',
            'reason' => 'nullable|string',
        ]);

        $profile = MemberProfile::with(['user', 'unit'])->find($request->id);

        if ((int) $profile->unit_id !== (int) $unitAdmin->unit_id) {
            return ApiResponse::error('Access denied. This member does not belong to your unit.', 403);
        }

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
    // MY UNIT INFO
    // GET /api/unit/info
    // ═══════════════════════════════════════════
    public function myUnitInfo(Request $request)
    {
        if (!$this->isUnitAdmin()) return $this->unauthorised();

        $unitAdmin = UnitAdmin::with(['user', 'unit'])
                              ->where('user_id', auth()->id())
                              ->first();

        if (!$unitAdmin) return ApiResponse::error('Unit Admin record not found.', 404);

        $unitId = $unitAdmin->unit_id;

        return ApiResponse::success([
            'unit_admin' => [
                'id'    => (string) $unitAdmin->id,
                'name'  => $unitAdmin->user->name,
                'email' => $unitAdmin->user->email,
                'phone' => $unitAdmin->user->phone,
            ],
            'unit' => [
                'id'   => (string) $unitAdmin->unit_id,
                'name' => $unitAdmin->unit->name ?? null,
            ],
            'member_counts' => [
                'total'    => MemberProfile::where('unit_id', $unitId)->count(),
                'pending'  => MemberProfile::where('unit_id', $unitId)->where('status', 'PENDING')->count(),
                'approved' => MemberProfile::where('unit_id', $unitId)->where('status', 'APPROVED')->count(),
                'rejected' => MemberProfile::where('unit_id', $unitId)->where('status', 'REJECTED')->count(),
            ],
        ], 'Unit info fetched successfully.');
    }
}