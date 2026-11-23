<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\State;
use App\Models\District;
use App\Models\DistrictAdmin;
use App\Models\UnitAdmin;
use App\Models\MemberProfile;
use App\Models\User;
use App\Models\News;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Mail\MemberStatusMail;
use App\Mail\UnitAdminWelcomeMail;
use App\Rules\MinimumAge;

class DistrictAdminController extends Controller
{
    // ─────────────────────────────────
    // HELPERS
    // ─────────────────────────────────
    private function myDistrictId()
    {
        return DistrictAdmin::where('user_id', Auth::id())->value('district_id');
    }

    private function myUnitIds()
    {
        return Unit::where('district_id', $this->myDistrictId())->pluck('id');
    }

    // ─────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────
    public function dashboard()
    {
        $districtId = $this->myDistrictId();
        $district   = District::with('state')->find($districtId);
        $base       = MemberProfile::where('district_id', $districtId);

        return view('district-admin.dashboard', [
            'district'        => $district,
            'totalUnits'      => Unit::where('district_id', $districtId)->count(),
            'totalUnitAdmins' => UnitAdmin::whereHas('unit', fn($q) => $q->where('district_id', $districtId))->count(),
            'totalMembers'    => (clone $base)->count(),
            'pendingMembers'  => (clone $base)->where('status', 'PENDING')->count(),
            'approvedMembers' => (clone $base)->where('status', 'APPROVED')->count(),
            'rejectedMembers' => (clone $base)->where('status', 'REJECTED')->count(),
            'recentMembers'   => (clone $base)->with(['user', 'unit'])->latest()->take(6)->get(),

            'totalRevenue' => Payment::whereIn('user_id', (clone $base)->pluck('user_id'))
                                ->where('status', 'SUCCESS')->sum('amount'),

            'topUnits' => Unit::where('district_id', $districtId)
                            ->withCount('memberProfiles')
                            ->orderByDesc('member_profiles_count')
                            ->take(5)->get(),

            'monthlyData' => (clone $base)
                                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                                ->where('created_at', '>=', now()->subMonths(6))
                                ->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get(),
        ]);
    }

    // ─────────────────────────────────
    // UNITS  (NEW — district admin can create/manage units)
    // ─────────────────────────────────
    public function units()
    {
        $districtId = $this->myDistrictId();

        return view('district-admin.units', [
            'units'    => Unit::where('district_id', $districtId)->withCount('memberProfiles')->get(),
            'district' => District::with('state')->find($districtId),
        ]);
    }

    public function storeUnit(Request $request)
    {
        $districtId = $this->myDistrictId();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'\-]+$/',
                Rule::unique('units')->where(fn($q) => $q->where('district_id', $districtId)),
            ],
        ], [
            'name.max'    => 'Unit name cannot exceed 100 characters.',
            'name.regex'  => 'Unit name can only contain letters, spaces, hyphens and apostrophes.',
            'name.unique' => 'This unit already exists in your district.',
        ]);

        Unit::create([
            'district_id' => $districtId, // forced — cannot create units outside own district
            'name'        => trim($request->name),
            'is_active'   => true,
        ]);

        return back()->with('success', 'Unit created successfully!');
    }

    public function updateUnit(Request $request)
    {
        $districtId = $this->myDistrictId();

        $request->validate([
            'id'   => [
                'required',
                Rule::exists('units', 'id')->where(fn($q) => $q->where('district_id', $districtId)),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'\-]+$/',
                Rule::unique('units')->where(fn($q) => $q->where('district_id', $districtId))->ignore($request->id),
            ],
        ], [
            'id.exists'   => 'This unit does not belong to your district.',
            'name.max'    => 'Unit name cannot exceed 100 characters.',
            'name.regex'  => 'Unit name can only contain letters, spaces, hyphens and apostrophes.',
            'name.unique' => 'This unit already exists in your district.',
        ]);

        Unit::where('district_id', $districtId)->findOrFail($request->id)
            ->update(['name' => trim($request->name)]);

        return back()->with('success', 'Unit updated successfully!');
    }

    public function deleteUnit(Request $request)
    {
        $districtId = $this->myDistrictId();

        $unit = Unit::where('district_id', $districtId)->find($request->id);
        if (!$unit) {
            return back()->with('error', 'This unit does not belong to your district.');
        }

        if ($unit->memberProfiles()->count() > 0) {
            return back()->with('error', 'Cannot delete! This unit still has members. Reassign or remove them first.');
        }

        $unit->delete();
        return back()->with('success', 'Unit deleted successfully!');
    }

    // ─────────────────────────────────
    // UNIT ADMINS  (scoped to units within this district)
    // ─────────────────────────────────
    public function unitAdmins()
    {
        $districtId = $this->myDistrictId();

        return view('district-admin.unit-admins', [
            'unitAdmins' => UnitAdmin::with(['user', 'unit'])
                ->whereHas('unit', fn($q) => $q->where('district_id', $districtId))
                ->latest('id')
                ->get(),
            'units' => Unit::where('district_id', $districtId)->doesntHave('unitAdmin')->get(),
        ]);
    }

    public function storeUnitAdmin(Request $request)
    {
        $inputEmail = trim(strtolower($request->email ?? ''));
        $inputPhone = trim($request->phone ?? '');

        if ($inputEmail) {
            $conflicting = User::withTrashed()->where('email', $inputEmail)->where('role', 'unit_admin')->first();
            if ($conflicting && $conflicting->trashed()) {
                $conflicting->forceDelete();
            }
        }

        if ($inputPhone) {
            $conflictingPhone = User::withTrashed()->where('phone', $inputPhone)->first();
            if ($conflictingPhone && ($conflictingPhone->trashed() || $conflictingPhone->role === 'district_admin')) {
                \Illuminate\Support\Facades\DB::table('district_admins')->where('user_id', $conflictingPhone->id)->delete();
                $conflictingPhone->forceDelete();
            }
        }

        $districtId = $this->myDistrictId();

        $request->validate([
            'name'    => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/'],
            'email'   => ['required', 'email:rfc,dns', 'max:150', Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'unit_admin'))],
            'phone'   => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'unit_id' => [
                'required',
                Rule::exists('units', 'id')->where(fn($q) => $q->where('district_id', $districtId)),
            ],
        ], [
            'name.regex'   => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'email.dns'    => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.unique' => 'This email is already registered.',
            'phone.regex'  => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique' => 'This phone number is already used.',
            'unit_id.exists' => 'Please select a valid unit within your district.',
        ]);

        if (UnitAdmin::where('unit_id', $request->unit_id)->exists()) {
            return back()->with('error', 'This unit already has an admin!');
        }

        $password = trim($request->phone);

        $user = User::create([
            'name'      => trim($request->name),
            'email'     => trim(strtolower($request->email)),
            'phone'     => trim($request->phone),
            'password'  => $password,
            'role'      => 'unit_admin',
            'is_active' => true,
        ]);

        UnitAdmin::create([
            'user_id'     => $user->id,
            'unit_id'     => $request->unit_id,
            'assigned_by' => Auth::id(),
        ]);

        try {
            $unit = Unit::with('district')->find($request->unit_id);
            $unitCode = str_pad($unit->id, 6, '0', STR_PAD_LEFT);

            Mail::to($user->email)->send(new UnitAdminWelcomeMail(
                adminName:    $user->name,
                email:        $user->email,
                password:     $password,
                unitName:     $unit->name,
                districtName: $unit->district->name,
                unitId:       $unitCode,
            ));

            return back()->with('success', 'Successfully created the account and sent the email ID and password.');
        } catch (\Exception $e) {
            Log::error('Unit Admin welcome email failed: ' . $e->getMessage());
            return back()->with('success', 'Successfully created the account, but could not send the credentials email to ' . $user->email . ' (Password: ' . $password . ' — please share manually).');
        }
    }

    public function updateUnitAdmin(Request $request)
    {
        $districtId = $this->myDistrictId();

        $unitAdmin = UnitAdmin::whereHas('unit', fn($q) => $q->where('district_id', $districtId))
            ->find($request->id);

        if (!$unitAdmin) {
            return back()->with('error', 'This unit admin does not belong to your district.');
        }

        $request->validate([
            'name'    => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/'],
            'email'   => ['required', 'email:rfc,dns', 'max:150', Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'unit_admin'))->ignore($unitAdmin->user_id)],
            'phone'   => ['required', 'regex:/^[6-9]\d{9}$/', Rule::unique('users', 'phone')->ignore($unitAdmin->user_id)],
            'unit_id' => [
                'required',
                Rule::exists('units', 'id')->where(fn($q) => $q->where('district_id', $districtId)),
            ],
            'resend_email' => 'nullable|boolean',
        ], [
            'name.regex'   => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'email.dns'    => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.unique' => 'This email is already registered to another account.',
            'phone.regex'  => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique' => 'This phone number is already used.',
            'unit_id.exists' => 'Please select a valid unit within your district.',
        ]);

        $conflict = UnitAdmin::where('unit_id', $request->unit_id)
            ->where('id', '!=', $unitAdmin->id)->first();

        if ($conflict) {
            return back()->with('error', 'This unit already has an admin assigned!');
        }

        $user         = User::find($unitAdmin->user_id);
        $emailChanged = strcasecmp($user->email, $request->email) !== 0;

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        $newPassword = null;
        if ($emailChanged || $request->boolean('resend_email')) {
            $newPassword = trim($request->phone);
            $user->password = $newPassword;
        }

        $user->save();

        $unitAdmin->unit_id = $request->unit_id;
        $unitAdmin->save();

        $message = 'Unit Admin updated successfully!';

        if ($newPassword) {
            try {
                $unit = Unit::with('district')->find($request->unit_id);
                $unitCode = str_pad($unit->id, 6, '0', STR_PAD_LEFT);
                Mail::to($user->email)->send(new UnitAdminWelcomeMail(
                    adminName:    $user->name,
                    email:        $user->email,
                    password:     $newPassword,
                    unitName:     $unit->name,
                    districtName: $unit->district->name,
                    unitId:       $unitCode,
                ));
                $message .= ' New login credentials sent to ' . $user->email . '. Password: ' . $newPassword;
            } catch (\Exception $e) {
                Log::error('Unit Admin welcome email failed: ' . $e->getMessage());
                $message .= ' However, the credentials email failed to send — new password: ' . $newPassword;
            }
        }

        return back()->with('success', $message);
    }

    public function resetUnitAdminPassword(Request $request)
    {
        $districtId = $this->myDistrictId();
        $unitAdmin  = UnitAdmin::whereHas('unit', fn($q) => $q->where('district_id', $districtId))
            ->find($request->id);

        if (!$unitAdmin) {
            return back()->with('error', 'This unit admin does not belong to your district.');
        }

        $user        = User::find($unitAdmin->user_id);
        $newPassword = $user->phone;
        $user->password = $newPassword;
        $user->save();

        try {
            $unit = Unit::with('district')->find($unitAdmin->unit_id);
            $unitCode = str_pad($unit->id, 6, '0', STR_PAD_LEFT);

            Mail::to($user->email)->send(new UnitAdminWelcomeMail(
                adminName:    $user->name,
                email:        $user->email,
                password:     $newPassword,
                unitName:     $unit->name,
                districtName: $unit->district->name,
                unitId:       $unitCode,
            ));
        } catch (\Exception $e) {
            Log::error('Unit Admin reset password email failed: ' . $e->getMessage());
        }

        return back()->with('success', 'Password reset! Login credentials sent to ' . $user->email . ' (Username: ' . str_pad($unitAdmin->unit_id, 6, '0', STR_PAD_LEFT) . ', Password: ' . $newPassword . ').');
    }

    public function toggleUnitAdminStatus(Request $request)
    {
        $districtId = $this->myDistrictId();
        $unitAdmin  = UnitAdmin::whereHas('unit', fn($q) => $q->where('district_id', $districtId))
            ->find($request->id);

        if (!$unitAdmin) {
            return back()->with('error', 'This unit admin does not belong to your district.');
        }

        $user = User::find($unitAdmin->user_id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', 'Unit Admin ' . $status . ' successfully!');
    }

    public function deleteUnitAdmin(Request $request)
    {
        $districtId = $this->myDistrictId();
        $unitAdmin  = UnitAdmin::whereHas('unit', fn($q) => $q->where('district_id', $districtId))
            ->find($request->id);

        if (!$unitAdmin) {
            return back()->with('error', 'This unit admin does not belong to your district.');
        }

        $memberCount = MemberProfile::where('unit_id', $unitAdmin->unit_id)->count();
        if ($memberCount > 0) {
            return back()->with('error', "Cannot delete! This unit admin's unit still has {$memberCount} member(s). Reassign or remove them first.");
        }

        $user = User::find($unitAdmin->user_id);
        $unitAdmin->delete();
        if ($user) {
            $user->tokens()->delete();
            $user->forceDelete();
        }

        return back()->with('success', 'Unit Admin deleted successfully!');
    }

    // ─────────────────────────────────
    // MEMBERS  (scoped to district)
    // ─────────────────────────────────
    public function members(Request $request)
    {
        $districtId = $this->myDistrictId();
        $query = MemberProfile::with(['user', 'unit'])->where('district_id', $districtId);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('membership_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('phone', 'LIKE', "%{$search}%")
                         ->orWhere('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status'))  $query->where('status', $request->status);
        if ($request->filled('unit_id')) $query->where('unit_id', $request->unit_id);

        return view('district-admin.members', [
            'members' => $query->latest()->paginate(20),
            'units'   => Unit::where('district_id', $districtId)->get(),
            'status'  => $request->status,
            'unit_id' => $request->unit_id,
            'search'  => $request->search,
        ]);
    }

    public function storeMember(Request $request)
    {
        $districtId = $this->myDistrictId();
        $district   = District::find($districtId);

        $request->validate([
            'name'     => ['required', 'string', 'max:100', 'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/'],
            'email'    => ['nullable', 'email:rfc,dns', 'max:150', Rule::unique('users', 'email')->where(fn($q) => $q->where('role', 'member'))],
            'phone'    => ['required', 'digits:10', 'regex:/^[6-9][0-9]{9}$/', 'unique:users,phone'],
            'dob'      => ['required', 'date', 'before:today', new MinimumAge(5)],
            'organization' => 'required|string|max:150',
            'address'      => 'required|string|max:500',
            'unit_id'      => ['required', Rule::exists('units', 'id')->where(fn($q) => $q->where('district_id', $districtId))],
            'photo'        => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'email.dns'        => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.unique'     => 'This email is already registered.',
            'phone.regex'      => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique'     => 'This phone number is already used.',
            'address.required' => 'Address is required.',
            'address.max'      => 'Address cannot exceed 500 characters.',
            'unit_id.exists'   => 'Please select a valid unit within your district.',
            'photo.required'   => 'Member photo is required.',
            'photo.image'      => 'The file must be an image (jpg, jpeg or png).',
            'photo.mimes'      => 'Photo must be a jpg, jpeg or png file.',
            'photo.max'        => 'Photo cannot exceed 2MB.',
        ]);

        $email = trim((string) $request->email) !== '' ? trim($request->email) : null;

        // ── Ask FUMMA whether this phone is an active FUMMA member ──
        // Done before the DB transaction so the HTTP call never holds it open.
        $isFummaMember = app(\App\Services\FummaMemberService::class)->isActive($request->phone) ? 1 : 0;

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo     = $request->file('photo');
            $photoPath = $photo->storeAs('member_photos', 'member_' . time() . '_' . $photo->getClientOriginalName(), 'public');
        }

        // ── Create user + profile + approve, all atomically ──
        try {
            $profile = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $photoPath, $districtId, $district, $email, $isFummaMember) {
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
                    'unit_id'         => $request->unit_id,
                    'district_id'     => $districtId,
                    'state_id'        => State::fixed()?->id ?? $district->state_id,
                    'status'          => 'PENDING',
                    'dob'             => $request->dob,
                    'organization'    => $request->organization,
                    'address'         => $request->address,
                    'photo'           => $photoPath,
                    'is_fumma_member' => $isFummaMember,
                ]);

                // Admin-created members skip the PENDING queue:
                // generates the Membership ID, sets APPROVED + joined_date
                return app(\App\Services\MembershipService::class)->approve($profile);
            });
        } catch (\Exception $e) {
            Log::error('Member creation failed: ' . $e->getMessage());
            if ($photoPath) Storage::disk('public')->delete($photoPath);
            return back()
                ->with('error', 'Something went wrong while creating the member. No account was created — please try again.')
                ->withInput();
        }

        // ── Approval email (outside the transaction, so a mail failure can't roll anything back) ──
        $profile->loadMissing(['user', 'unit']);

        if (empty($profile->user->email)) {
            $emailNote = ' (No email given — share the login details manually.)';
        } else {
            try {
                Mail::to($profile->user->email)->send(new \App\Mail\MemberApprovedMail(
                    memberName:   $profile->user->name,
                    membershipId: $profile->membership_id,
                    unitName:     $profile->unit->name ?? 'ECA Unit',
                    phone:        $profile->user->phone,
                ));
                $emailNote = ' Login details emailed to ' . $profile->user->email . '.';
            } catch (\Throwable $e) {
                Log::error('Approval email failed for member #' . $profile->id . ': ' . $e->getMessage());
                $emailNote = ' (Could not send the email to ' . $profile->user->email .
                             ' — please share the login details manually.)';
            }
        }

        $fummaNote = $isFummaMember ? ' Member Net: Yes.' : ' Member Net: No.';
        $successMsg = 'Member ' . $profile->user->name . ' created and approved! Membership ID: ' .
            $profile->membership_id . '.' . $fummaNote . $emailNote;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'member'  => [
                    'id'            => $profile->id,
                    'membership_id' => $profile->membership_id,
                    'name'          => $profile->user->name,
                ]
            ]);
        }

        return back()->with('success', $successMsg);
    }

    public function memberDetail($id)
    {
        $districtId = $this->myDistrictId();
        $member = MemberProfile::with(['user', 'unit', 'district', 'state'])
            ->where('district_id', $districtId)->findOrFail($id);

        return response()->json([
            'name'          => $member->user->name ?? '—',
            'email'         => $member->user->email ?? '—',
            'phone'         => $member->user->phone ?? '—',
            'dob'           => $member->dob,
            'organization'  => $member->organization,
            'address'       => $member->address,
            'photo'         => $member->photo ? asset('storage/' . $member->photo) : null,
            'unit_name'     => $member->unit->name ?? '—',
            'membership_id' => $member->membership_id ?? '—',
            'status'        => $member->status,
            'joined_date'   => $member->joined_date ?? '—',
            'expiry_date'   => $member->expiry_date ?? '—',
            'registered_on' => $member->created_at->format('d M Y'),
        ]);
    }

    public function approveMember(Request $request)
{
    $districtId = $this->myDistrictId();
    $profile = MemberProfile::with(['user', 'unit'])
        ->where('district_id', $districtId)->findOrFail($request->id);

    if (!in_array($profile->status, ['PENDING', 'REJECTED'])) {
        return back()->with('error', 'Only PENDING or REJECTED members can be approved!');
    }

    $profile = app(\App\Services\MembershipService::class)->approve($profile);

    if (empty($profile->user->email)) {
        $emailNote = ' (No email on file — share the login details manually.)';
    } else {
        try {
            Mail::to($profile->user->email)->send(new \App\Mail\MemberApprovedMail(
                memberName:   $profile->user->name,
                membershipId: $profile->membership_id,
                unitName:     $profile->unit->name ?? 'ECA Unit',
                phone:        $profile->user->phone,
            ));
            $emailNote = ' Email sent to ' . $profile->user->email;
        } catch (\Throwable $e) {
            Log::error('Approval email failed for member #' . $profile->id . ': ' . $e->getMessage());
            $emailNote = ' (Could not send email — the address on file looks invalid or undeliverable.)';
        }
    }

    return back()->with(
        'success',
        $profile->user->name . ' approved! Membership ID: ' . $profile->membership_id . '.' . $emailNote
    );
}

    public function rejectMember(Request $request)
{
    $districtId = $this->myDistrictId();
    $profile = MemberProfile::with(['user', 'unit'])
        ->where('district_id', $districtId)->findOrFail($request->id);

    if (!in_array($profile->status, ['PENDING', 'APPROVED'])) {
        return back()->with('error', 'Only PENDING or APPROVED members can be rejected!');
    }

    $profile->status = 'REJECTED';
    $profile->save();

    if (empty($profile->user->email)) {
        $emailNote = ' (No email on file — no notification sent.)';
    } else {
        try {
            $this->sendStatusMail($profile, 'rejected', $request->reason ?? null);
            $emailNote = ' Email sent to ' . $profile->user->email;
        } catch (\Exception $e) {
            Log::error('Status email (reject) failed for member #' . $profile->id . ': ' . $e->getMessage());
            $emailNote = ' (Could not send email — the address on file looks invalid or undeliverable.)';
        }
    }

    return back()->with('success', $profile->user->name . ' rejected.' . $emailNote);
}

    private function sendStatusMail(MemberProfile $profile, string $status, ?string $reason = null): void
{
    $profile->loadMissing(['user', 'unit']);

    if (empty($profile->user->email)) {
        return;
    }

    $unitAdminEmail = UnitAdmin::with('user')
        ->where('unit_id', $profile->unit_id)->first()?->user->email
        ?? config('mail.from.address');

    Mail::to($profile->user->email)->send(new MemberStatusMail(
        memberName:   $profile->user->name,
        unitName:     $profile->unit->name,
        status:       $status,
        reason:       $reason,
        contactEmail: $unitAdminEmail,
        loginUrl:     url('/login'),
    ));
}

    // ─────────────────────────────────
    // BULK IMPORT  (district-wide — unit_id per row must belong to this district)
    // ─────────────────────────────────
    public function bulkImport()
    {
        $districtId = $this->myDistrictId();
        return view('district-admin.bulk-import', [
            'district' => District::find($districtId),
            'units'    => Unit::where('district_id', $districtId)->get(),
        ]);
    }

    public function downloadTemplate()
{
    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="member_import_template.csv"',
    ];

    $columns = ['name', 'email', 'phone', 'dob', 'organization', 'unit', 'address'];

    $unitName = Unit::where('district_id', $this->myDistrictId())->value('name') ?? 'Vadakara';

    // Row 1 has an email, row 2 leaves the email blank (optional)
    $rows = [
        ['Fathima Beevi', 'fathima@gmail.com', '9845001001', '1990-05-15', 'Fathima Stores', $unitName, 'Main Road, Kozhikode'],
        ['Suresh Kumar',  '',                  '9845001002', '1985-08-20', 'Suresh Traders', $unitName, 'Market Street, Kozhikode'],
    ];

    $callback = function () use ($columns, $rows) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);
        foreach ($rows as $row) fputcsv($file, $row);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    public function processBulkImport(Request $request)
    {
        // Each valid row may call the FUMMA API (10s timeout max), so allow enough time.
        set_time_limit(300);

        $districtId = $this->myDistrictId();
        $district   = District::find($districtId);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'csv_file.mimes' => 'Only .csv files are accepted.',
            'csv_file.max'   => 'File size cannot exceed 2MB.',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        fgetcsv($handle); // skip header

        $results   = ['success' => [], 'errors' => [], 'total' => 0, 'created' => 0, 'failed' => 0];
        $rowNumber = 1;

        // normalise names: lowercase, trim, collapse spaces
        $norm = fn($v) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $v)));

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // skip fully blank rows
            if (count(array_filter($row, fn($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $results['total']++;

            $data = [
                'name'         => trim($row[0] ?? ''),
                'email'        => trim($row[1] ?? ''),
                'phone'        => trim($row[2] ?? ''),
                'dob'          => trim($row[3] ?? ''),
                'organization' => trim($row[4] ?? ''),
                'unit'         => trim($row[5] ?? ''),
                'address'      => trim($row[6] ?? ''),
            ];

            $errors = [];

            // ── Name ──
            if (empty($data['name'])) {
                $errors[] = 'Name is required';
            } elseif (strlen($data['name']) > 100) {
                $errors[] = 'Name cannot exceed 100 characters';
            } elseif (!preg_match('/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/', $data['name'])) {
                $errors[] = 'Name can only contain letters, spaces, hyphens and apostrophes';
            }

            // ── Email (OPTIONAL: only validated when provided) ──
            $email = $data['email'] !== '' ? $data['email'] : null;

            if ($email !== null) {
                $emailValid = true;

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email';
                    $emailValid = false;
                }
                if (strlen($email) > 150) {
                    $errors[] = 'Email cannot exceed 150 characters';
                    $emailValid = false;
                }
                if ($emailValid && !$this->emailDomainHasMailRecords($email)) {
                    $errors[] = 'Email domain does not appear to accept mail (check for typos)';
                }
                if (User::where('email', $email)->where('role', 'member')->exists()) {
                    $errors[] = 'Email already exists for another member';
                }
            }

            // ── Phone ──
            if (!preg_match('/^[6-9]\d{9}$/', $data['phone'])) {
                $errors[] = 'Phone must be 10 digits and start with 6-9';
            } elseif (User::where('phone', $data['phone'])->exists()) {
                $errors[] = 'Phone already exists';
            }

            // ── DOB ──
            $dob = null;
            if (empty($data['dob'])) {
                $errors[] = 'DOB is required';
            } else {
                try {
                    $parsedDob = Carbon::parse($data['dob']);
                    if ($parsedDob->isFuture()) {
                        $errors[] = 'DOB cannot be in the future';
                    } elseif ($parsedDob->age < 5) {
                        $errors[] = 'Member must be at least 5 years old';
                    } else {
                        $dob = $parsedDob->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $errors[] = 'DOB is not a valid date (use YYYY-MM-DD)';
                }
            }

            // ── Organization / address ──
            if (empty($data['organization']))        $errors[] = 'Organization is required';
            if (strlen($data['organization']) > 150) $errors[] = 'Organization cannot exceed 150 characters';
            if (empty($data['address']))             $errors[] = 'Address is required';
            if (strlen($data['address']) > 500)      $errors[] = 'Address cannot exceed 500 characters';

            // ── Unit (matched by NAME, only inside this admin's district) ──
            $unitModel = null;
            $unitInput = trim($data['unit'] ?? '');

            if (empty($unitInput)) {
                $errors[] = 'Unit is required';
            } else {
                $unitModel = Unit::where('district_id', $districtId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$norm($unitInput)])
                    ->first();

                if (!$unitModel) {
                    $errors[] = "Unit '{$unitInput}' not found in your district";
                }
            }

            if (!empty($errors)) {
                $results['errors'][] = ['row' => $rowNumber, 'name' => $data['name'], 'email' => $email, 'errors' => $errors];
                $results['failed']++;
                continue;
            }

            // ── Row is valid: ask FUMMA whether this phone is an active FUMMA member ──
            $isFummaMember = app(\App\Services\FummaMemberService::class)->isActive($data['phone']) ? 1 : 0;

            try {
                $profile = \Illuminate\Support\Facades\DB::transaction(function () use ($data, $email, $dob, $districtId, $district, $unitModel, $isFummaMember) {
                    $user = User::create([
                        'name'      => $data['name'],
                        'email'     => $email, // null when left blank
                        'phone'     => $data['phone'],
                        'password'  => Hash::make($data['phone']),
                        'role'      => 'member',
                        'is_active' => true,
                    ]);

                    $profile = MemberProfile::create([
                        'user_id'         => $user->id,
                        'unit_id'         => $unitModel->id,
                        'district_id'     => $districtId,
                        'state_id'        => State::fixed()?->id ?? $district->state_id,
                        'status'          => 'PENDING',
                        'dob'             => $dob,
                        'organization'    => $data['organization'],
                        'address'         => $data['address'],
                        'photo'           => null,
                        'is_fumma_member' => $isFummaMember,
                    ]);

                    return app(\App\Services\MembershipService::class)->approve($profile);
                });

                if (!empty($email)) {
                    try {
                        Mail::to($email)->send(new \App\Mail\MemberApprovedMail(
                            memberName:   $data['name'],
                            membershipId: $profile->membership_id,
                            unitName:     $unitModel->name ?? 'ECA Unit',
                            phone:        $data['phone'],
                        ));
                    } catch (\Throwable $e) {
                        Log::error('Bulk import approval email failed: ' . $e->getMessage());
                    }
                }

                $results['success'][] = [
                    'row'             => $rowNumber,
                    'name'            => $data['name'],
                    'email'           => $email,
                    'membership_id'   => $profile->membership_id,
                    'is_fumma_member' => $isFummaMember,
                ];
                $results['created']++;
            } catch (\Exception $e) {
                Log::error('Bulk import row ' . $rowNumber . ' failed: ' . $e->getMessage());
                $results['errors'][] = ['row' => $rowNumber, 'name' => $data['name'], 'email' => $email, 'errors' => ['Something went wrong while saving this row']];
                $results['failed']++;
            }
        }

        fclose($handle);
        return back()->with('import_results', $results);
    }

    private function emailDomainHasMailRecords(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);
        if (!$domain) return false;
        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    // ─────────────────────────────────
    // PAYMENTS  (district-wide, read-only)
    // ─────────────────────────────────
    public function payments(Request $request)
    {
        $districtId  = $this->myDistrictId();
        $districtUserIds = MemberProfile::where('district_id', $districtId)->pluck('user_id');

        $query = Payment::with(['user', 'memberProfile.unit'])
            ->whereIn('user_id', $districtUserIds)->latest();

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('payment_type')) $query->where('payment_type', $request->payment_type);

        return view('district-admin.payments', [
            'payments'     => $query->get(),
            'district'     => District::find($districtId),
            'totalRevenue' => Payment::whereIn('user_id', $districtUserIds)->where('status', 'SUCCESS')->sum('amount'),
            'pendingCount' => Payment::whereIn('user_id', $districtUserIds)->where('status', 'PENDING')->count(),
            'failedCount'  => Payment::whereIn('user_id', $districtUserIds)->where('status', 'FAILED')->count(),
        ]);
    }

    // ─────────────────────────────────
    // NEWS / EVENTS / COMMITTEES / GALLERY / FUNDRAISING / OTHER FEES /
    // SCHEMES / EXPENSES
    //
    // These all follow the EXACT same pattern as UnitAdminController —
    // scoped by `created_by = Auth::id()`, since they're the district
    // admin's own posts/records. Copy each method from
    // UnitAdminController as-is; the only two changes needed:
    //   1. `use Illuminate\Support\Facades\Auth;` already imported above
    //   2. Wherever committee/scheme members are pulled from
    //      `MemberProfile::where('unit_id', $unitId)`, change to
    //      `MemberProfile::where('district_id', $this->myDistrictId())`
    //      so they draw from the whole district instead of one unit.
    // ─────────────────────────────────

    // ─────────────────────────────────
    // CHANGE PASSWORD
    // ─────────────────────────────────
    public function showChangePassword()
    {
        return view('district-admin.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect!');
        }
        if (Hash::check($request->new_password, $user->password)) {
            return back()->with('error', 'New password must be different from current password!');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password changed successfully!');
    }

    // ─────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────
    public function logout(Request $request)
    {
        Auth::guard('district_admin')->logout();

        return redirect()->route('district-admin.login');
    }
    
    public function deleteMember(Request $request)
{
    $request->validate([
        'id'       => 'required|integer',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ]);

    // Verify the logged-in district admin's password
    $admin = Auth::guard('district_admin')->user() ?? Auth::user();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
        return back()->with('error', 'Incorrect password. Member was not deleted.');
    }

    // Only members inside this admin's own district
    $profile = MemberProfile::where('district_id', $this->myDistrictId())->find($request->id);

    if (!$profile) {
        return back()->with('error', 'This member does not belong to your district.');
    }

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, $admin->id, 'district_admin');
    } catch (\Throwable $e) {
        Log::error('Member delete failed for profile #' . $profile->id . ': ' . $e->getMessage());
        return back()->with('error', 'Could not delete this member. They may still have linked records (payments, registrations, etc.).');
    }

    return back()->with('success', 'Member deleted successfully!');
}
}