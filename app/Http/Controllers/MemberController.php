<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\MemberProfile;
use App\Models\Unit;
use App\Models\District;
use App\Models\State;
use App\Models\User;
use App\Models\UnitAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use League\Csv\Reader;
use Illuminate\Support\Facades\Hash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;


class MemberController extends Controller
{
    // ─────────────────────────────────
    // REGISTER (Flutter app)
    // No password field: login = Membership ID (issued on approval) + phone number.
    // No email is sent here; the login email goes out when the admin approves.
    // Photo is MANDATORY (base64 image).
    // ─────────────────────────────────
    public function register(Request $request)
    {
                // Kerala is fixed: the app no longer needs to send state_id
        if ($fixed = State::fixed()) {
            $request->merge(['state_id' => $fixed->id]);
        }
        
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name'         => 'required|string',
            'email'        => 'required|email|unique:users,email',
            'phone'        => 'required|digits:10|unique:users,phone',
            'dob'          => [
                'required',
                'date',
                'before:today',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->age < 18) {
                        $fail('You must be at least 18 years old.');
                    }
                }
            ],
            'photo'           => 'required|string',
            'organization'    => 'required|string',
            'address'         => 'required|string|max:500',
            'state_id'        => 'nullable|exists:states,id',
            'district_id'     => 'nullable|exists:districts,id',
            'unit_id'         => 'required|exists:units,id',
            'is_fumma_member' => 'nullable|in:0,1',
        ], [
            'email.unique'     => 'Email is already taken.',
            'phone.unique'     => 'Phone number is already taken.',
            'address.required' => 'Address is required.',
            'photo.required'   => 'Photo is required.',
        ])->stopOnFirstFailure();

        if ($validator->fails()) {
            $firstKey   = $validator->errors()->keys()[0];
            $firstError = $validator->errors()->first($firstKey);

            return response()->json([
                'status'      => '0',
                'status_code' => '422',
                'message'     => $firstError,
                'data'        => (object) [],
            ], 422);
        }

        // ── Make sure the photo is a real image (not broken base64 / random text) ──
        $rawPhoto  = preg_replace('#^data:image/\w+;base64,#i', '', $request->photo);
        $imageData = base64_decode($rawPhoto, true);

        if ($imageData === false || @getimagesizefromstring($imageData) === false) {
            return response()->json([
                'status'      => '0',
                'status_code' => '422',
                'message'     => 'Photo must be a valid image.',
                'data'        => (object) [],
            ], 422);
        }

        $fee = \App\Models\RegistrationFee::latest()->first();
        if (!$fee) {
            return ApiResponse::error('No registration fee configured. Contact admin.', 400);
        }

        $unit = Unit::find($request->unit_id);
        if (!$unit) {
            return ApiResponse::error('Selected unit not found.', 404);
        }

        $district = $request->district_id ? District::find($request->district_id) : $unit->district;
        $state    = $request->state_id ? State::find($request->state_id) : $unit->district?->state;

        // ── Save photo (always present now) ──
        $fileName = 'member_' . uniqid() . '.jpg';
        Storage::disk('public')->put('member_photos/' . $fileName, $imageData);
        $photoPath = 'member_photos/' . $fileName;

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->phone),
            'role'      => 'member',
            'is_active' => true,
        ]);

        $profile = MemberProfile::create([
            'user_id'         => $user->id,
            'fee_id'          => $fee->id,
            'plan_id'         => null,
            'unit_id'         => $request->unit_id,
            'district_id'     => null,
            'state_id'        => $request->state_id ?? State::fixed()?->id ?? 1,
            'status'          => 'PENDING',
            'dob'             => $request->dob,
            'organization'    => $request->organization,
            'address'         => $request->address,
            'photo'           => $photoPath,
            'is_fumma_member' => (int) $request->input('is_fumma_member', 0),
            'membership_id'   => null,
            'joined_date'     => null,
        ]);

        return ApiResponse::success(
            [
                'id'              => (string) $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'dob'             => $request->dob,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'status'          => $profile->status,
                'photo'           => asset('storage/' . $photoPath),
                'unit_name'       => $unit->name,
                'district_name'   => $district?->name ?? '—',
                'state_name'      => $state?->name ?? '—',
            ],
            'Registration successful. Please wait for Unit Admin approval.',
            201
        );
    }

    // ─────────────────────────────────
    // GET MY UNIT MEMBERS (Member — view only)
    // POST /api/member/unit-members
    // ─────────────────────────────────
    public function getMyUnitMembers(Request $request)
    {
        $user    = auth()->user();
        $profile = MemberProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found.', 404);
        }

        // ── From URL query params ──
        $perPage = $request->query('per_page', 10);
        $page    = $request->query('page', 1);

        // ── From request body ──
        $keyword = $request->input('keyword');

        $query = MemberProfile::with(['user'])
                    ->where('unit_id', $profile->unit_id)
                    ->where('status', 'APPROVED')
                    ->where('user_id', '!=', $user->id);

        // ── Apply search only if keyword is present ──
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->whereHas('user', function ($uq) use ($keyword) {
                    $uq->where('name',  'LIKE', "%{$keyword}%")
                       ->orWhere('phone', 'LIKE', "%{$keyword}%");
                })
                ->orWhere('organization', 'LIKE', "%{$keyword}%");
            });
        }

        $members = $query->paginate($perPage, ['*'], 'page', $page);

        $data = $members->getCollection()->map(function ($member) {
            return [
                'id'              => (string) $member->id,
                'name'            => $member->user->name,
                'phone'           => $member->user->phone,
                'organization'    => $member->organization,
                'is_fumma_member' => (int) $member->is_fumma_member,
                'photo'           => $member->photo
                                        ? asset('storage/' . $member->photo)
                                        : null,
            ];
        });

        return ApiResponse::success(
            [
                'unit_id'       => (string) $profile->unit_id,
                'keyword'       => $keyword ?? null,
                'total_members' => $members->total(),
                'members'       => $data,
                'current_page'  => $members->currentPage(),
                'per_page'      => $members->perPage(),
                'last_page'     => $members->lastPage(),
                'has_more'      => $members->hasMorePages(),
            ],
            'Unit members fetched successfully'
        );
    }

    // ─────────────────────────────────
    // GET MEMBER PROFILE
    // GET /api/member/profile
    // ─────────────────────────────────
    public function getProfile()
    {
        $user    = auth()->user();
        $profile = MemberProfile::with(['unit', 'state'])
                    ->where('user_id', $user->id)
                    ->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found.', 404);
        }

        return ApiResponse::success(
            [
                'id'              => (string) $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'dob'             => (string) $profile->dob,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : '',
                'membership_id'   => $profile->membership_id,
                'status'          => $profile->status,
                'unit_id'         => (string) $profile->unit_id,
                'unit_name'       => $profile->unit?->name,
                'state_id'        => (string) $profile->state_id,
                'state_name'      => $profile->state?->name ?? 'Kerala',
                'joined_date'     => (string) $profile->joined_date,
                'expiry_date'     => (string) $profile->expiry_date,
            ],
            'Profile fetched successfully'
        );
    }

    // ─────────────────────────────────
    // UPDATE MEMBER PROFILE
    // POST /api/member/profile/update
    // Photo stays optional here: it is only replaced when a new one is sent.
    // ─────────────────────────────────
    public function updateProfile(Request $request)
    {
        $user    = auth()->user();
        $profile = MemberProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found.', 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name'         => 'sometimes|string',
            'email'        => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone'        => [
                'sometimes',
                'digits:10',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'dob'          => [
                'sometimes',
                'date',
                'before:today',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->age < 18) {
                        $fail('You must be at least 18 years old.');
                    }
                }
            ],
            'organization'    => 'sometimes|string',
            'address'         => 'sometimes|required|string|max:500',
            'is_fumma_member' => 'sometimes|in:0,1',
            'photo'           => 'nullable|string', // base64
        ], [
            'email.unique' => 'Email is already taken.',
            'phone.unique' => 'Phone number is already taken.',
            'address.required' => 'Address cannot be empty.',
        ])->stopOnFirstFailure();

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();
            return response()->json([
                'status'      => '0',
                'status_code' => '422',
                'message'     => $firstError,
                'data'        => (object) [],
            ], 422);
        }

        // ── Handle photo upload ──
        if ($request->filled('photo')) {
            $rawPhoto  = preg_replace('#^data:image/\w+;base64,#i', '', $request->photo);
            $imageData = base64_decode($rawPhoto, true);

            if ($imageData === false || @getimagesizefromstring($imageData) === false) {
                return response()->json([
                    'status'      => '0',
                    'status_code' => '422',
                    'message'     => 'Photo must be a valid image.',
                    'data'        => (object) [],
                ], 422);
            }

            if ($profile->photo) {
                Storage::disk('public')->delete($profile->photo);
            }
            $fileName = 'member_' . uniqid() . '.jpg';
            Storage::disk('public')->put('member_photos/' . $fileName, $imageData);
            $profile->photo = 'member_photos/' . $fileName;
        }

        // ── Update profile table ──
        if ($request->filled('dob'))          $profile->dob          = $request->dob;
        if ($request->filled('organization')) $profile->organization = $request->organization;
        if ($request->filled('address')) $profile->address = $request->address;
        if ($request->has('is_fumma_member')) $profile->is_fumma_member = (int) $request->is_fumma_member;
        $profile->save();

        // ── Update user table ──
        if ($request->filled('name'))  $user->name  = $request->name;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('phone')) $user->phone = $request->phone;
        $user->save();

        // ── Reload relations ──
        $profile->load(['unit', 'state']);

        return ApiResponse::success(
            [
                'id'              => (string) $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'phone'           => $user->phone,
                'dob'             => (string) $profile->dob,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
                'membership_type' => $profile->membership_type,
                'membership_id'   => $profile->membership_id,
                'status'          => $profile->status,
                'unit_id'         => (string) $profile->unit_id,
                'unit_name'       => $profile->unit?->name,
                'state_id'        => (string) $profile->state_id,
                'state_name'      => $profile->state?->name ?? 'Kerala',
                'joined_date'     => (string) $profile->joined_date,
                'expiry_date'     => (string) $profile->expiry_date,
            ],
            'Profile updated successfully'
        );
    }

    // ─────────────────────────────────
    // GET ALL MEMBERS (Super Admin)
    // GET /api/admin/members
    // ─────────────────────────────────
    public function getAllMembers()
    {
        $profiles = MemberProfile::with(['user', 'unit', 'district', 'state'])->get();

        $data = $profiles->map(function ($profile) {
            return [
                'id'              => (string) $profile->id,
                'user_id'         => (string) $profile->user_id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'status'          => $profile->status,
                'membership_type' => $profile->membership_type,
                'membership_id'   => $profile->membership_id,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
                'unit_name'       => $profile->unit->name ?? '—',
                'state_name'      => $profile->state->name,
                'joined_date'     => (string) $profile->joined_date,
                'expiry_date'     => (string) $profile->expiry_date,
                'registered_on'   => (string) $profile->created_at,
            ];
        });

        return ApiResponse::success($data, 'Members fetched successfully');
    }

    // ─────────────────────────────────
    // GET MEMBER DETAIL (Super Admin)
    // POST /api/admin/members/detail
    // ─────────────────────────────────
    public function getMemberDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:member_profiles,id'
        ]);

        $profile = MemberProfile::with(['user', 'unit', 'district', 'state'])
                    ->find($request->id);

        return ApiResponse::success(
            [
                'id'              => (string) $profile->id,
                'user_id'         => (string) $profile->user_id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'dob'             => (string) $profile->dob,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
                'membership_type' => $profile->membership_type,
                'membership_id'   => $profile->membership_id,
                'status'          => $profile->status,
                'unit_id'         => (string) $profile->unit_id,
                'unit_name'       => $profile->unit?->name ?? '—',
                'state_id'        => (string) $profile->state_id,
                'state_name'      => $profile->state?->name ?? '—',
                'joined_date'     => (string) $profile->joined_date,
                'expiry_date'     => (string) $profile->expiry_date,
                'registered_on'   => (string) $profile->created_at,
            ],
            'Member detail fetched successfully'
        );
    }

    // ─────────────────────────────────
    // DELETE MEMBER (Super Admin)
    // DELETE /api/admin/members
    // ─────────────────────────────────
    public function deleteMember(Request $request)
{
    $request->validate([
        'id' => 'required|exists:member_profiles,id'
    ]);

    $profile = MemberProfile::find($request->id);

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, auth()->id(), 'api_admin');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Member delete failed: ' . $e->getMessage());
        return ApiResponse::error('Could not delete this member. They may still have linked records.', 409);
    }

    return ApiResponse::success(null, 'Member deleted successfully');
}

    // ─────────────────────────────────
    // GET UNIT MEMBERS (Unit Admin)
    // GET /api/unit-admin/members
    // ─────────────────────────────────
    public function getUnitMembers()
    {
        $user      = auth()->user();
        $unitAdmin = $user->unitAdmin;

        if (!$unitAdmin) {
            return ApiResponse::error('Unit Admin profile not found.', 404);
        }

        $profiles = MemberProfile::with(['user'])
                    ->where('unit_id', $unitAdmin->unit_id)
                    ->get();

        $data = $profiles->map(function ($profile) {
            return [
                'id'              => (string) $profile->id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'status'          => $profile->status,
                'membership_type' => $profile->membership_type,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
                'registered_on'   => (string) $profile->created_at,
            ];
        });

        return ApiResponse::success($data, 'Unit members fetched successfully');
    }

    // ─────────────────────────────────
    // GET PENDING MEMBERS (Unit Admin)
    // GET /api/unit-admin/members/pending
    // ─────────────────────────────────
    public function getPendingMembers()
    {
        $user      = auth()->user();
        $unitAdmin = $user->unitAdmin;

        if (!$unitAdmin) {
            return ApiResponse::error('Unit Admin profile not found.', 404);
        }

        $profiles = MemberProfile::with(['user'])
                    ->where('unit_id', $unitAdmin->unit_id)
                    ->where('status', 'PENDING')
                    ->get();

        $data = $profiles->map(function ($profile) {
            return [
                'id'              => (string) $profile->id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'membership_type' => $profile->membership_type,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
                'registered_on'   => (string) $profile->created_at,
            ];
        });

        return ApiResponse::success($data, 'Pending members fetched successfully');
    }

    // ─────────────────────────────────
    // GET APPROVED MEMBERS (Unit Admin)
    // GET /api/unit-admin/members/approved
    // ─────────────────────────────────
    public function getApprovedMembers()
    {
        $user      = auth()->user();
        $unitAdmin = $user->unitAdmin;

        if (!$unitAdmin) {
            return ApiResponse::error('Unit Admin profile not found.', 404);
        }

        $profiles = MemberProfile::with(['user'])
                    ->where('unit_id', $unitAdmin->unit_id)
                    ->where('status', 'APPROVED')
                    ->get();

        $data = $profiles->map(function ($profile) {
            return [
                'id'              => (string) $profile->id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'membership_type' => $profile->membership_type,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
            ];
        });

        return ApiResponse::success($data, 'Approved members fetched successfully');
    }

    // ─────────────────────────────────
    // GET REJECTED MEMBERS (Unit Admin)
    // GET /api/unit-admin/members/rejected
    // ─────────────────────────────────
    public function getRejectedMembers()
    {
        $user      = auth()->user();
        $unitAdmin = $user->unitAdmin;

        if (!$unitAdmin) {
            return ApiResponse::error('Unit Admin profile not found.', 404);
        }

        $profiles = MemberProfile::with(['user'])
                    ->where('unit_id', $unitAdmin->unit_id)
                    ->where('status', 'REJECTED')
                    ->get();

        $data = $profiles->map(function ($profile) {
            return [
                'id'              => (string) $profile->id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'membership_type' => $profile->membership_type,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
            ];
        });

        return ApiResponse::success($data, 'Rejected members fetched successfully');
    }

    // ─────────────────────────────────
    // GET MEMBER DETAIL (Unit Admin)
    // POST /api/unit-admin/members/detail
    // ─────────────────────────────────
    public function getUnitMemberDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:member_profiles,id'
        ]);

        $user      = auth()->user();
        $unitAdmin = $user->unitAdmin;

        $profile = MemberProfile::with(['user', 'unit', 'district', 'state'])
                    ->find($request->id);

        if ($unitAdmin && $profile->unit_id != $unitAdmin->unit_id) {
            return ApiResponse::error('You can only view members of your unit.', 403);
        }

        return ApiResponse::success(
            [
                'id'              => (string) $profile->id,
                'name'            => $profile->user->name,
                'email'           => $profile->user->email,
                'phone'           => $profile->user->phone,
                'dob'             => (string) $profile->dob,
                'organization'    => $profile->organization,
                'address'         => $profile->address,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'photo'           => $profile->photo
                                        ? asset('storage/' . $profile->photo)
                                        : null,
                'membership_type' => $profile->membership_type,
                'status'          => $profile->status,
                'unit_name'       => $profile->unit->name ?? '—',
                'district_name'   => $profile->district?->name ?? '—',
                'state_name'      => $profile->state->name,
                'registered_on'   => (string) $profile->created_at,
            ],
            'Member detail fetched successfully'
        );
    }

    // ─────────────────────────────────
    // APPROVE MEMBER
    // ─────────────────────────────────
    public function approveMember(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:member_profiles,id'
        ]);

        $profile = MemberProfile::with(['user', 'unit'])->find($request->id);

        if ($profile->status !== 'PENDING') {
            return ApiResponse::error('Only pending members can be approved.', 400);
        }

        $unitAdmin = auth()->user()->unitAdmin;
        if ($unitAdmin && $profile->unit_id != $unitAdmin->unit_id) {
            return ApiResponse::error('You can only approve members of your unit.', 403);
        }

        // ── Approve + generate Membership ID ──
        $profile      = app(\App\Services\MembershipService::class)->approve($profile);
        $membershipId = $profile->membership_id;

        // ── Send approval email with login details (Membership ID + phone) ──
        try {
            \Illuminate\Support\Facades\Mail::to($profile->user->email)
                ->send(new \App\Mail\MemberApprovedMail(
                    memberName:   $profile->user->name,
                    membershipId: $membershipId,
                    unitName:     $profile->unit->name ?? 'ECA Unit',
                    phone:        $profile->user->phone,
                ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Approval email failed: ' . $e->getMessage());
        }

        return ApiResponse::success(
            [
                'id'            => (string) $profile->id,
                'name'          => $profile->user->name,
                'status'        => $profile->status,
                'membership_id' => $membershipId,
                'joined_date'   => (string) $profile->joined_date,
            ],
            'Member approved successfully. Membership ID: ' . $membershipId
        );
    }

    // ─────────────────────────────────
    // REJECT MEMBER (Unit Admin)
    // PUT /api/unit-admin/members/reject
    // ─────────────────────────────────
    public function rejectMember(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:member_profiles,id',
            'reason' => 'required|string',
        ]);

        $profile = MemberProfile::find($request->id);

        if ($profile->status !== 'PENDING') {
            return ApiResponse::error('Only pending members can be rejected.', 400);
        }

        $unitAdmin = auth()->user()->unitAdmin;
        if ($unitAdmin && $profile->unit_id != $unitAdmin->unit_id) {
            return ApiResponse::error('You can only reject members of your unit.', 403);
        }

        $profile->status = 'REJECTED';
        $profile->save();

        return ApiResponse::success(
            [
                'id'     => (string) $profile->id,
                'name'   => $profile->user->name,
                'status' => $profile->status,
                'reason' => $request->reason,
            ],
            'Member rejected successfully.'
        );
    }

    // ─────────────────────────────────
    // GET MEMBER STATUS (Quick check)
    // GET /api/member/status
    // ─────────────────────────────────
    public function getMemberStatus()
    {
        $user    = auth()->user();
        $profile = MemberProfile::with(['unit'])
                    ->where('user_id', $user->id)
                    ->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found.', 404);
        }

        if (
            in_array($profile->status, ['ACTIVE', 'APPROVED']) &&
            $profile->expiry_date &&
            Carbon::parse($profile->expiry_date)->isPast()
        ) {
            $profile->update(['status' => 'EXPIRED']);
        }

        return ApiResponse::success(
            [
                'status'          => $profile->status,
                'membership_id'   => $profile->membership_id,
                'membership_type' => $profile->membership_type,
                'is_fumma_member' => (int) $profile->is_fumma_member,
                'joined_date'     => (string) $profile->joined_date,
                'expiry_date'     => (string) $profile->expiry_date,
                'is_expired'      => $profile->expiry_date
                                        ? Carbon::parse($profile->expiry_date)->isPast()
                                        : false,
                'days_to_expiry'  => $profile->expiry_date
                                        ? max(0, Carbon::now()->diffInDays($profile->expiry_date, false))
                                        : null,
            ],
            'Status fetched successfully'
        );
    }

    // ─────────────────────────────────
    // DELETE MY ACCOUNT (Member — self)
    // DELETE /api/member/account/delete
    // No body needed — auth token only
    // ─────────────────────────────────
    public function deleteAccount(Request $request)
{
    $user    = auth()->user();
    $profile = MemberProfile::where('user_id', $user->id)->first();

    try {
        if ($profile) {
            app(\App\Services\MemberArchiveService::class)
                ->archiveAndDelete($profile, $user->id, 'self');
        } else {
            $user->tokens()->delete();
            $user->forceDelete();
        }
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Account delete failed: ' . $e->getMessage());
        return ApiResponse::error('Could not delete your account. Please contact your unit admin.', 409);
    }

    return ApiResponse::success(null, 'Your account has been deleted.');
}
// ─────────────────────────────────
// GET MEMBERSHIP CERTIFICATE (direct .pdf link)
// GET /api/member/certificate
// ─────────────────────────────────
public function certificateInfo()
{
    $user    = auth()->user();
    $profile = MemberProfile::with(['user', 'unit', 'district', 'state'])
                ->where('user_id', $user->id)
                ->first();

    if (
        !$profile ||
        !$profile->membership_id ||
        !in_array($profile->status, ['APPROVED', 'ACTIVE'])
    ) {
        return ApiResponse::error('Certificate is available only after approval.', 403);
    }

    $unit = $profile->unit ?? ($profile->unit_id ? Unit::find($profile->unit_id) : null);
    if ($unit) {
        $unit->refresh();
    }

    // Helper to resolve files safely whether stored in Storage disk 'public' or public_path
    $resolveFile = function (?string $path, string $fallbackAsset) {
        if (!$path) {
            return [
                'public_url'   => asset($fallbackAsset),
                'storage_path' => null,
                'is_custom'    => false,
            ];
        }

        $cleanPath = ltrim(preg_replace('#^/?storage/#', '', $path), '/');

        if (Storage::disk('public')->exists($cleanPath)) {
            return [
                'public_url'   => asset('storage/' . $cleanPath),
                'storage_path' => Storage::disk('public')->path($cleanPath),
                'is_custom'    => true,
            ];
        }

        if (file_exists(public_path('storage/' . $cleanPath))) {
            return [
                'public_url'   => asset('storage/' . $cleanPath),
                'storage_path' => public_path('storage/' . $cleanPath),
                'is_custom'    => true,
            ];
        }

        if (file_exists(storage_path('app/public/' . $cleanPath))) {
            return [
                'public_url'   => asset('storage/' . $cleanPath),
                'storage_path' => storage_path('app/public/' . $cleanPath),
                'is_custom'    => true,
            ];
        }

        return [
            'public_url'   => asset($fallbackAsset),
            'storage_path' => null,
            'is_custom'    => false,
        ];
    };

    // ── DYNAMIC UNIT-SPECIFIC CERTIFICATE LOGO ──
    $logoRes       = $resolveFile($unit?->certificate_logo, 'fumma-logo.png');
    $logoPublicUrl = $logoRes['public_url'];
    $isCustomLogo  = $logoRes['is_custom'];
    $logoDataUrl   = null;

    if ($logoRes['storage_path'] && file_exists($logoRes['storage_path'])) {
        $mime        = @mime_content_type($logoRes['storage_path']) ?: 'image/png';
        $logoDataUrl = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoRes['storage_path']));
    } else {
        $defaultLogoPath = public_path('fumma-logo.png');
        if (file_exists($defaultLogoPath)) {
            $logoDataUrl = 'data:image/png;base64,' . base64_encode(file_get_contents($defaultLogoPath));
        }
    }

    // ── SEAL TEXT & YEAR (replaces "Member Net 2026" with "[Unit Name] 2026") ──
    $joinedYear   = $profile->joined_date ? \Carbon\Carbon::parse($profile->joined_date)->format('Y') : date('Y');
    $baseSealName = ($unit && !empty($unit->certificate_seal_text) && trim($unit->certificate_seal_text) !== 'Member Net')
        ? trim($unit->certificate_seal_text)
        : ($unit?->name ?? 'Member Net');
    $sealFullText = preg_match('/\b' . $joinedYear . '\b/', $baseSealName)
        ? $baseSealName
        : ($baseSealName . ' ' . $joinedYear);

    // ── SIGNATORIES CONFIGURED IN UNIT ADMIN PANEL ──
    $s1Name  = ($unit && $unit->signatory_1_name) ? $unit->signatory_1_name : (($unit && $unit->certificate_president_name) ? $unit->certificate_president_name : 'Tony Pulickal');
    $s1Title = ($unit && $unit->signatory_1_title) ? $unit->signatory_1_title : (($unit && ($unit->signatory_1_name || $unit->certificate_president_name)) ? ($unit->name . ' Unit President') : 'State President');
    $sig1Res = $resolveFile($unit?->signatory_1_signature, 'sign-president.png');
    $s1Sig   = $sig1Res['public_url'];

    $s2Name  = ($unit && $unit->signatory_2_name) ? $unit->signatory_2_name : (($unit && $unit->certificate_secretary_name) ? $unit->certificate_secretary_name : 'Shaji Manhar');
    $s2Title = ($unit && $unit->signatory_2_title) ? $unit->signatory_2_title : (($unit && ($unit->signatory_2_name || $unit->certificate_secretary_name)) ? ($unit->name . ' Unit Gen. Secretary') : 'State General Secretary');
    $sig2Res = $resolveFile($unit?->signatory_2_signature, 'sign-secretary.png');
    $s2Sig   = $sig2Res['public_url'];

    $s3Name  = ($unit && $unit->signatory_3_name) ? $unit->signatory_3_name : 'Rafi Puthoor';
    $s3Title = ($unit && $unit->signatory_3_title) ? $unit->signatory_3_title : (($unit && $unit->signatory_3_name) ? ($unit->name . ' Unit Treasurer') : 'State Treasurer');
    $sig3Res = $resolveFile($unit?->signatory_3_signature, 'sign-treasurer.png');
    $s3Sig   = $sig3Res['public_url'];

    $signatory1 = [
        'name'                 => $s1Name,
        'title'                => $s1Title,
        'designation'          => $s1Title,
        'signature'            => $s1Sig,
        'signature_url'        => $s1Sig,
        'has_custom_signature' => $sig1Res['is_custom'],
    ];

    $signatory2 = [
        'name'                 => $s2Name,
        'title'                => $s2Title,
        'designation'          => $s2Title,
        'signature'            => $s2Sig,
        'signature_url'        => $s2Sig,
        'has_custom_signature' => $sig2Res['is_custom'],
    ];

    $signatory3 = [
        'name'                 => $s3Name,
        'title'                => $s3Title,
        'designation'          => $s3Title,
        'signature'            => $s3Sig,
        'signature_url'        => $s3Sig,
        'has_custom_signature' => $sig3Res['is_custom'],
    ];

    $signatories = [$signatory1, $signatory2, $signatory3];

    try {
        // Hard-to-guess file name (stable per member, so the link never changes)
        $token    = substr(hash_hmac('sha256', (string) $profile->id, config('app.key')), 0, 16);
        $relative = 'certificates/certificate_' . $profile->id . '_' . $token . '.pdf';

        $pdf = Pdf::loadView('certificates.membership', [
            'profile'  => $profile,
            'unit'     => $unit,
            'logo'     => $logoDataUrl,
            'sealText' => $baseSealName,
            'sealYear' => $joinedYear,
        ])->setPaper('a4', 'landscape');

        // Saved fresh each time, so unit logo/name/date changes always show immediately on the certificate
        Storage::disk('public')->put($relative, $pdf->output());
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Certificate generation failed: ' . $e->getMessage());
        return ApiResponse::error('Could not generate the certificate. Please try again.', 500);
    }

    $certificateSettings = [
        'id'                         => $unit->id ?? null,
        'unit_id'                    => $unit->id ?? null,
        'unit_name'                  => $unit->name ?? null,
        'name'                       => $unit->name ?? null,
        'certificate_logo'           => $logoPublicUrl,
        'logo'                       => $logoPublicUrl,
        'is_custom_certificate_logo' => $isCustomLogo,
        'certificate_seal_text'      => $sealFullText,
        'seal_text'                  => $sealFullText,
        'seal_name'                  => $baseSealName,
        'seal_year'                  => $joinedYear,
        'seal_full_text'             => $sealFullText,
        'signatory_1'                => $signatory1,
        'signatory_2'                => $signatory2,
        'signatory_3'                => $signatory3,
        'signatories'                => $signatories,
        'president_name'             => $s1Name,
        'president_signature'        => $s1Sig,
        'secretary_name'             => $s2Name,
        'secretary_signature'        => $s2Sig,
        'treasurer_name'             => $s3Name,
        'treasurer_signature'        => $s3Sig,
    ];

    return ApiResponse::success(
        [
            'membership_id'              => $profile->membership_id,
            'message'                    => 'Successfully! Now you are a Member Net member.',
            'certificate_url'            => asset('storage/' . $relative),
            'certificate_logo'           => $logoPublicUrl,
            'logo'                       => $logoPublicUrl,
            'is_custom_certificate_logo' => $isCustomLogo,
            'certificate_seal_text'      => $sealFullText,
            'seal_text'                  => $sealFullText,
            'seal_name'                  => $baseSealName,
            'seal_year'                  => $joinedYear,
            'seal_full_text'             => $sealFullText,
            'unit_id'                    => $unit->id ?? null,
            'unit_name'                  => $unit->name ?? null,
            'signatory_1'                => $signatory1,
            'signatory_2'                => $signatory2,
            'signatory_3'                => $signatory3,
            'signatories'                => $signatories,
            'president_name'             => $s1Name,
            'president_signature'        => $s1Sig,
            'secretary_name'             => $s2Name,
            'secretary_signature'        => $s2Sig,
            'treasurer_name'             => $s3Name,
            'treasurer_signature'        => $s3Sig,
            'unit'                       => $certificateSettings,
            'certificate'                => $certificateSettings,
            'certificate_settings'       => $certificateSettings,
        ],
        'Congratulations! You are now a Member Net member.'
    );
}


}