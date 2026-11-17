<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\District;
use App\Models\State;
use App\Models\UnitAdmin;
use App\Models\MemberProfile;
use App\Models\News;
use App\Models\User;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Expense;
use App\Models\DistrictAdmin;
use App\Mail\DistrictAdminWelcomeMail;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Mail\MemberStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Rules\MinimumAge;

class AdminController extends Controller
{
    // ─────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────
    public function dashboard()
    {
        return view('admin.dashboard', [
            'totalStates'     => State::count(),
            'totalDistricts'  => District::when(State::fixed(), fn($q, $s) => $q->where('state_id', $s->id))->count(),
            'totalUnits'      => Unit::when(State::fixed(), fn($q, $s) => $q->whereHas('district', fn($d) => $d->where('state_id', $s->id)))->count(),
            'totalUnitAdmins' => UnitAdmin::count(),
            'totalMembers'    => MemberProfile::count(),
            'pendingMembers'  => MemberProfile::where('status', 'PENDING')->count(),
            'approvedMembers' => MemberProfile::where('status', 'APPROVED')->count(),
            'rejectedMembers' => MemberProfile::where('status', 'REJECTED')->count(),
            'recentMembers'   => MemberProfile::with(['user', 'unit'])->latest()->take(5)->get(),

            // Chart data — members registered per month (last 6 months)
            'monthlyData' => MemberProfile::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('year', 'month')
                ->orderBy('year')->orderBy('month')
                ->get(),

            // Membership type split
            'lifeCount'   => MemberProfile::where('membership_type', 'LIFE')->count(),
            'annualCount' => MemberProfile::where('membership_type', 'ANNUAL')->count(),

            // Top units by member count
            'topUnits' => \App\Models\Unit::withCount('memberProfiles')
                ->orderByDesc('member_profiles_count')
                ->take(5)->get(),
        ]);
    }

    // ─────────────────────────────────
    // STATES
    // ─────────────────────────────────
    public function states()
    {
        return view('admin.states', [
            'states' => State::withCount([
                'districts',
                'units'
            ])->get()
        ]);
    }

    public function storeState(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'\-]+$/',
                'unique:states,name',
            ],
        ], [
            'name.max'    => 'State name cannot exceed 100 characters.',
            'name.regex'  => 'State name can only contain letters, spaces, hyphens and apostrophes.',
            'name.unique' => 'This state already exists.',
        ]);

        State::create([
            'name' => trim($request->name),
            'is_active' => true
        ]);
        return back()->with('success', 'State created successfully!');
    }

    public function updateState(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:states,id',
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'\-]+$/',
                Rule::unique('states', 'name')->ignore($request->id)
            ]
        ], [
            'name.max'    => 'State name cannot exceed 100 characters.',
            'name.regex'  => 'State name can only contain letters, spaces, hyphens and apostrophes.',
            'name.unique' => 'This state already exists.',
        ]);

        State::find($request->id)->update([
            'name' => trim($request->name)
        ]);
        return back()->with('success', 'State updated successfully!');
    }

    public function deleteState(Request $request)
    {
        $state = State::find($request->id);
        if ($state->districts()->count() > 0) {
            return back()->with(
                'error',
                'Cannot delete! Delete districts first.'
            );
        }
        $state->delete();
        return back()->with('success', 'State deleted successfully!');
    }

    public function districts()
{
    $fixed = State::fixed(); // Kerala, or null in multi-state mode

    return view('admin.districts', [
        'fixedState' => $fixed,
        'districts'  => District::with('state')
            ->when($fixed, fn($q) => $q->where('state_id', $fixed->id))
            ->withCount('units')
            ->get(),
        'states'     => State::where('is_active', true)->get(),
    ]);
}

public function storeDistrict(Request $request)
{
    // Force Kerala, whatever the form sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $request->validate([
        'state_id' => 'required|exists:states,id',
        'name' => [
            'required',
            'string',
            'max:100',
            'regex:/^[a-zA-Z\s\'\-]+$/',
            Rule::unique('districts')
                ->where(function ($q) use ($request) {
                    return $q->where('state_id', $request->state_id);
                })
        ]
    ], [
        'name.max'    => 'District name cannot exceed 100 characters.',
        'name.regex'  => 'District name can only contain letters, spaces, hyphens and apostrophes.',
        'name.unique' => 'This district already exists in the selected state.',
    ]);

    District::create([
        'state_id'  => $request->state_id,
        'name'      => trim($request->name),
        'is_active' => true
    ]);
    return back()->with('success', 'District created successfully!');
}

public function updateDistrict(Request $request)
{
    // Force Kerala, whatever the form sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $request->validate([
        'id' => 'required|exists:districts,id',
        'state_id' => 'required|exists:states,id',
        'name' => [
            'required',
            'string',
            'max:100',
            'regex:/^[a-zA-Z\s\'\-]+$/',
            Rule::unique('districts')
                ->where(fn($q) => $q->where('state_id', $request->state_id))
                ->ignore($request->id)
        ],
    ], [
        'name.max'    => 'District name cannot exceed 100 characters.',
        'name.regex'  => 'District name can only contain letters, spaces, hyphens and apostrophes.',
        'name.unique' => 'This district already exists in the selected state.',
    ]);

    District::find($request->id)->update([
        'name'     => trim($request->name),
        'state_id' => $request->state_id
    ]);
    return back()->with('success', 'District updated successfully!');
}

    public function deleteDistrict(Request $request)
    {
        $district = District::find($request->id);
        if ($district->units()->count() > 0) {
            return back()->with(
                'error',
                'Cannot delete! Delete units first.'
            );
        }
        $district->delete();
        return back()->with('success', 'District deleted successfully!');
    }

    // ─────────────────────────────────
    // AJAX: DISTRICTS BY STATE (for Add Unit dropdown)
    // ─────────────────────────────────
    public function getDistrictsByState(Request $request)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id'
        ]);

        $districts = District::where('state_id', $request->state_id)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $districts
        ]);
    }

    public function units()
{
    $fixed = State::fixed();

    return view('admin.units', [
        'fixedState' => $fixed,
        'units'      => Unit::with('district.state')
            ->when($fixed, fn($q) => $q->whereHas('district', fn($d) => $d->where('state_id', $fixed->id)))
            ->get(),
        'states'     => State::where('is_active', true)->get(),
        // Kerala districts, used to fill the dropdown when Kerala is fixed
        'districts'  => $fixed
            ? District::where('state_id', $fixed->id)->where('is_active', true)->orderBy('name')->get()
            : collect(),
    ]);
}

public function storeUnit(Request $request)
{
    $fixed = State::fixed();

    // When Kerala is fixed, the district must belong to Kerala
    $districtRule = Rule::exists('districts', 'id');
    if ($fixed) {
        $districtRule = $districtRule->where('state_id', $fixed->id);
    }

    $request->validate([
        'district_id' => ['required', $districtRule],
        'name' => [
            'required',
            'string',
            'max:100',
            'regex:/^[a-zA-Z\s\'\-]+$/',
            Rule::unique('units')
                ->where(fn($q) => $q->where('district_id', $request->district_id))
        ],
    ], [
        'name.max'    => 'Unit name cannot exceed 100 characters.',
        'name.regex'  => 'Unit name can only contain letters, spaces, hyphens and apostrophes.',
        'name.unique' => 'This unit already exists in the selected district.',
    ]);

    Unit::create([
        'district_id' => $request->district_id,
        'name'        => trim($request->name),
        'is_active'   => true
    ]);
    return back()->with('success', 'Unit created successfully!');
}

    public function updateUnit(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:units,id',
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z\s\'\-]+$/',
            ],
        ], [
            'name.max'   => 'Unit name cannot exceed 100 characters.',
            'name.regex' => 'Unit name can only contain letters, spaces, hyphens and apostrophes.',
        ]);

        Unit::find($request->id)->update([
            'name' => trim($request->name)
        ]);
        return back()->with('success', 'Unit updated successfully!');
    }

    public function deleteUnit(Request $request)
    {
        Unit::find($request->id)->delete();
        return back()->with('success', 'Unit deleted successfully!');
    }

    // ─────────────────────────────────
    // UNIT ADMINS
    // ─────────────────────────────────
    public function unitAdmins()
    {
        return view('admin.unit-admins', [
            'unitAdmins' => UnitAdmin::with([
                'user',
                'unit.district.state'
            ])->get(),
            'units' => Unit::with('district.state')
                ->doesntHave('unitAdmin')
                ->get(),
        ]);
    }

    public function storeUnitAdmin(Request $request)
    {
        // ── 'dns' rule blocks junk domains (e.g. "gmailsdnl") at entry time,
        //    the same protection applied to member emails below. ──
        $request->validate([
            'name'    => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/',
            ],
            'email'   => [
                'required',
                'email:rfc,dns',
                'max:150',
                'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
                'unique:users,email',
            ],
            'phone'   => ['required', 'regex:/^[6-9]\d{9}$/', 'unique:users,phone'],
            'unit_id' => 'required|exists:units,id',
        ], [
            'name.regex'    => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'name.max'      => 'Name cannot exceed 100 characters.',
            'email.email'   => 'Enter a valid email address.',
            'email.dns'     => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.max'     => 'Email cannot exceed 150 characters.',
            'email.regex'   => 'Email must include a valid domain (e.g. name@example.com).',
            'email.unique'  => 'This email is already registered.',
            'phone.regex'   => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique'  => 'This phone number is already used.',
        ]);

        $exists = UnitAdmin::where('unit_id', $request->unit_id)->first();

        if ($exists) {
            return back()->with('error', 'This unit already has an admin!');
        }

        $firstName = preg_replace('/[^A-Za-z0-9]/', '', explode(' ', trim($request->name))[0]) ?: 'Admin';
        $password  = $firstName . '@' . rand(1000, 9999);

        $user = User::create([
            'name'      => trim($request->name),
            'email'     => trim(strtolower($request->email)),
            'phone'     => trim($request->phone),
            'password'  => $password,
            'role'      => 'unit_admin',
            'is_active' => true,
        ]);

        UnitAdmin::create([
            'user_id' => $user->id,
            'unit_id' => $request->unit_id,
            'assigned_by' => auth()->id(),
        ]);

        // ── Send credentials email (wrapped so a bad address can't 500 this action) ──
        try {
            $unit = Unit::with('district')->find($request->unit_id);

            Mail::to($user->email)->send(new \App\Mail\UnitAdminWelcomeMail(
                adminName:    $user->name,
                email:        $user->email,
                password:     $password,
                unitName:     $unit->name,
                districtName: $unit->district->name,
            ));

            return back()->with(
                'success',
                'Unit Admin created! Password: ' . $password . ' (also emailed to ' . $user->email . ')'
            );
        } catch (\Exception $e) {
            Log::error('Unit Admin welcome email failed: ' . $e->getMessage());

            return back()->with(
                'success',
                'Unit Admin created! Password: ' . $password .
                ' (Could not send the credentials email to ' . $user->email . ' — please share the password manually.)'
            );
        }
    }

    public function updateUnitAdmin(Request $request)
    {
        $request->validate([
            'id'      => 'required|exists:unit_admins,id',
            'name'    => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/',
            ],
            'email'   => [
                'required',
                'email:rfc,dns',
                'max:150',
                'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
                Rule::unique('users', 'email')->ignore(
                    UnitAdmin::find($request->id)?->user_id
                ),
            ],
            'phone'   => [
                'required',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('users', 'phone')->ignore(
                    UnitAdmin::find($request->id)?->user_id
                )
            ],
            'unit_id'      => 'required|exists:units,id',
            'resend_email' => 'nullable|boolean',
        ], [
            'name.regex'      => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'name.max'        => 'Name cannot exceed 100 characters.',
            'email.email'     => 'Enter a valid email address.',
            'email.dns'       => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.max'       => 'Email cannot exceed 150 characters.',
            'email.regex'     => 'Email must include a valid domain (e.g. name@example.com).',
            'email.unique'    => 'This email is already registered to another account.',
            'phone.regex'     => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique'    => 'This phone number is already used.',
            'unit_id.exists'  => 'Please select a valid unit.',
        ]);

        $unitAdmin = UnitAdmin::find($request->id);

        // ── Check if new unit is already taken by another admin ──
        $conflict = UnitAdmin::where('unit_id', $request->unit_id)
            ->where('id', '!=', $unitAdmin->id)
            ->first();

        if ($conflict) {
            return back()->with('error', 'This unit already has an admin assigned!');
        }

        $user = User::find($unitAdmin->user_id);
        $emailChanged = strcasecmp($user->email, $request->email) !== 0;

        // ── Update basic info ──
        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        // We never have the old plaintext password on hand, so any time the
        // email changes (old credentials mail is now unreachable at the new
        // address) — or the admin explicitly asks to resend — we issue a
        // fresh password, same as the "Reset Password" action.
        $newPassword = null;
        if ($emailChanged || $request->boolean('resend_email')) {
            $firstName   = explode(' ', $request->name)[0];
            $newPassword = $firstName . '@' . rand(1000, 9999);
            $user->password = $newPassword;
        }

        $user->save();

        $unitAdmin->unit_id = $request->unit_id;
        $unitAdmin->save();

        $message = 'Unit Admin updated successfully!';

        if ($newPassword) {
            try {
                $unit = Unit::with('district')->find($request->unit_id);

                Mail::to($user->email)->send(new \App\Mail\UnitAdminWelcomeMail(
                    adminName:    $user->name,
                    email:        $user->email,
                    password:     $newPassword,
                    unitName:     $unit->name,
                    districtName: $unit->district->name,
                ));

                $message .= ' New login credentials sent to ' . $user->email
                    . '. Password: ' . $newPassword;
            } catch (\Exception $e) {
                Log::error('Unit Admin welcome email failed: ' . $e->getMessage());
                $message .= ' However, the credentials email failed to send — new password: ' . $newPassword;
            }
        }

        return back()->with('success', $message);
    }

    public function resetUnitAdminPassword(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:unit_admins,id'
        ]);

        $unitAdmin = UnitAdmin::find($request->id);
        $user = User::find($unitAdmin->user_id);
        $firstName = explode(' ', $user->name)[0];
        $newPassword = $firstName . '@' . rand(1000, 9999);
        $user->password = $newPassword;
        $user->save();

        return back()->with(
            'success',
            'Password reset! New password: ' . $newPassword
        );
    }

    public function toggleUnitAdminStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:unit_admins,id'
        ]);

        $unitAdmin = UnitAdmin::find($request->id);
        $user = User::find($unitAdmin->user_id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with(
            'success',
            'Unit Admin ' . $status . ' successfully!'
        );
    }

    public function deleteUnitAdmin(Request $request)
{
    $request->validate([
        'id' => 'required|exists:unit_admins,id',
    ]);

    // Verify the logged-in super admin's password
    $admin = Auth::guard('super_admin')->user();

    if (!$admin || !\Illuminate\Support\Facades\Hash::check((string) $request->password, $admin->password)) {
        return back()->with('error', 'Incorrect password. Unit Admin was not deleted.');
    }

    $unitAdmin = UnitAdmin::find($request->id);

    // ── Block deletion if members exist under this admin's unit ──
    $memberCount = MemberProfile::where('unit_id', $unitAdmin->unit_id)->count();

    if ($memberCount > 0) {
        return back()->with(
            'error',
            "Cannot delete! This unit admin's unit still has {$memberCount} member(s). Reassign or remove them first."
        );
    }

    $user = User::find($unitAdmin->user_id);

    \Illuminate\Support\Facades\DB::transaction(function () use ($unitAdmin, $user) {
        $unitAdmin->delete();
        if ($user) {
            $user->tokens()->delete(); // log them out of every device
            $user->delete();
        }
    });

    return back()->with('success', 'Unit Admin deleted successfully!');
}

    // ─────────────────────────────────
    // DISTRICT ADMINS
    // ─────────────────────────────────
    public function districtAdmins()
    {
        return view('admin.district-admins', [
            'districtAdmins' => \App\Models\DistrictAdmin::with(['user', 'district.state'])->get(),
            'districts' => District::whereDoesntHave('districtAdmin')->with('state')->get(),
        ]);
    }

    public function storeDistrictAdmin(Request $request)
    {
        // ── 'dns' rule blocks junk domains (e.g. "gmailsdnl") at entry time,
        //    same protection used for unit admins / members. ──
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
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
            'district_id' => 'required|exists:districts,id',
        ], [
            'name.regex'         => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'name.max'           => 'Name cannot exceed 100 characters.',
            'email.email'        => 'Enter a valid email address.',
            'email.dns'          => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.max'          => 'Email cannot exceed 150 characters.',
            'email.regex'        => 'Email must include a valid domain (e.g. name@example.com).',
            'email.unique'       => 'This email is already registered.',
            'phone.regex'        => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique'       => 'This phone number is already used.',
            'district_id.exists' => 'Please select a valid district.',
        ]);

        $exists = \App\Models\DistrictAdmin::where('district_id', $request->district_id)->first();

        if ($exists) {
            return back()->with('error', 'This district already has an admin!');
        }

        $firstName = explode(' ', $request->name)[0];
        $password  = $firstName . '@' . rand(1000, 9999);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => $password,
            'role'      => 'district_admin',
            'is_active' => true,
        ]);

        \App\Models\DistrictAdmin::create([
            'user_id'     => $user->id,
            'district_id' => $request->district_id,
            'assigned_by' => auth()->id(),
        ]);

        // ── Send credentials email (wrapped so a bad address can't 500 this action) ──
        try {
            $district = District::with('state')->find($request->district_id);

            Mail::to($user->email)->send(new \App\Mail\DistrictAdminWelcomeMail(
                adminName:    $user->name,
                email:        $user->email,
                password:     $password,
                districtName: $district->name,
                stateName:    $district->state->name,
            ));

            return back()->with(
                'success',
                'District Admin created! Password: ' . $password . ' (also emailed to ' . $user->email . ')'
            );
        } catch (\Exception $e) {
            Log::error('District Admin welcome email failed: ' . $e->getMessage());

            return back()->with(
                'success',
                'District Admin created! Password: ' . $password .
                ' (Could not send the credentials email to ' . $user->email . ' — please share the password manually.)'
            );
        }
    }

    public function updateDistrictAdmin(Request $request)
    {
        $request->validate([
            'id'   => 'required|exists:district_admins,id',
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:150',
                'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
                Rule::unique('users', 'email')->ignore(
                    \App\Models\DistrictAdmin::find($request->id)?->user_id
                ),
            ],
            'phone' => [
                'required',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('users', 'phone')->ignore(
                    \App\Models\DistrictAdmin::find($request->id)?->user_id
                ),
            ],
            'district_id'  => 'required|exists:districts,id',
            'resend_email' => 'nullable|boolean',
        ], [
            'name.regex'         => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'name.max'           => 'Name cannot exceed 100 characters.',
            'email.email'        => 'Enter a valid email address.',
            'email.dns'          => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.max'          => 'Email cannot exceed 150 characters.',
            'email.regex'        => 'Email must include a valid domain (e.g. name@example.com).',
            'email.unique'       => 'This email is already registered to another account.',
            'phone.regex'        => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique'       => 'This phone number is already used.',
            'district_id.exists' => 'Please select a valid district.',
        ]);

        $districtAdmin = \App\Models\DistrictAdmin::find($request->id);

        // ── Check if new district is already taken by another admin ──
        $conflict = \App\Models\DistrictAdmin::where('district_id', $request->district_id)
            ->where('id', '!=', $districtAdmin->id)
            ->first();

        if ($conflict) {
            return back()->with('error', 'This district already has an admin assigned!');
        }

        $user = User::find($districtAdmin->user_id);
        $emailChanged = strcasecmp($user->email, $request->email) !== 0;

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;

        // Same rule as Unit Admins: email change or explicit resend request
        // forces a fresh password, since we never keep the old plaintext one.
        $newPassword = null;
        if ($emailChanged || $request->boolean('resend_email')) {
            $firstName   = explode(' ', $request->name)[0];
            $newPassword = $firstName . '@' . rand(1000, 9999);
            $user->password = $newPassword;
        }

        $user->save();

        $districtAdmin->district_id = $request->district_id;
        $districtAdmin->save();

        $message = 'District Admin updated successfully!';

        if ($newPassword) {
            try {
                $district = District::with('state')->find($request->district_id);

                Mail::to($user->email)->send(new \App\Mail\DistrictAdminWelcomeMail(
                    adminName:    $user->name,
                    email:        $user->email,
                    password:     $newPassword,
                    districtName: $district->name,
                    stateName:    $district->state->name,
                ));

                $message .= ' New login credentials sent to ' . $user->email
                    . '. Password: ' . $newPassword;
            } catch (\Exception $e) {
                Log::error('District Admin welcome email failed: ' . $e->getMessage());
                $message .= ' However, the credentials email failed to send — new password: ' . $newPassword;
            }
        }

        return back()->with('success', $message);
    }

    public function resetDistrictAdminPassword(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:district_admins,id'
        ]);

        $districtAdmin = \App\Models\DistrictAdmin::find($request->id);
        $user = User::find($districtAdmin->user_id);
        $firstName = explode(' ', $user->name)[0];
        $newPassword = $firstName . '@' . rand(1000, 9999);
        $user->password = $newPassword;
        $user->save();

        return back()->with(
            'success',
            'Password reset! New password: ' . $newPassword
        );
    }

    public function toggleDistrictAdminStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:district_admins,id'
        ]);

        $districtAdmin = \App\Models\DistrictAdmin::find($request->id);
        $user = User::find($districtAdmin->user_id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with(
            'success',
            'District Admin ' . $status . ' successfully!'
        );
    }

    public function deleteDistrictAdmin(Request $request)
{
    $request->validate([
        'id' => 'required|exists:district_admins,id',
    ]);

    // Verify the logged-in super admin's password
    $admin = Auth::guard('super_admin')->user();

    if (!$admin || !\Illuminate\Support\Facades\Hash::check((string) $request->password, $admin->password)) {
        return back()->with('error', 'Incorrect password. District Admin was not deleted.');
    }

    $districtAdmin = \App\Models\DistrictAdmin::find($request->id);

    // ── Block deletion if the district still has members ──
    $memberCount = MemberProfile::where('district_id', $districtAdmin->district_id)->count();

    if ($memberCount > 0) {
        return back()->with(
            'error',
            "Cannot delete! This district admin's district still has {$memberCount} member(s). Reassign or remove them first."
        );
    }

    $user = User::find($districtAdmin->user_id);

    \Illuminate\Support\Facades\DB::transaction(function () use ($districtAdmin, $user) {
        $districtAdmin->delete();
        if ($user) {
            $user->tokens()->delete();
            $user->delete();
        }
    });

    return back()->with('success', 'District Admin deleted successfully!');
}

    public function members(Request $request)
{
    $fixed = State::fixed();

    $query = MemberProfile::with(['user', 'unit', 'district', 'state']);

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }
    if ($request->filled('district_id')) {
        $query->where('district_id', $request->district_id);
    }
    if ($request->filled('membership_type')) {
        $query->where('membership_type', $request->membership_type);
    }

    return view('admin.members', [
        'members' => $query->latest()->paginate(20),
        'districts' => $fixed
            ? District::where('state_id', $fixed->id)->orderBy('name')->get()
            : District::all(),
        'states' => State::all(),
        'fixedState' => $fixed,
        'status' => $request->status,
        'district_id' => $request->district_id,
        'membership_type' => $request->membership_type,
    ]);
}

    public function storeMember(Request $request)
    {
        // Force Kerala, whatever the form sends
if ($fixed = State::fixed()) {
    $request->merge(['state_id' => $fixed->id]);
}

        $request->validate([
            'name'         => [
                'required',
                'string',
                'max:100',
                'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/',
            ],
            'email'        => [
                'nullable',
                'email:rfc,dns',
                'max:150',
                'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
                'unique:users,email',
            ],
            'phone'        => [
                'required',
                'digits:10',
                'regex:/^[6-9][0-9]{9}$/',
                'unique:users,phone'
            ],
            'dob'          => ['required', 'date', 'before:today', new MinimumAge(18)],
            'organization' => 'required|string|max:150',
            'address'      => 'required|string|max:500',
            'state_id'     => 'required|exists:states,id',
            'district_id'  => 'required|exists:districts,id',
            'unit_id'      => 'required|exists:units,id',
            'photo'        => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'name.max'         => 'Name cannot exceed 100 characters.',
            'name.regex'       => 'Name can only contain letters, spaces, hyphens and apostrophes.',
            'email.email'      => 'Enter a valid email address.',
            'email.dns'        => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.max'        => 'Email cannot exceed 150 characters.',
            'email.regex'      => 'Email must include a valid domain (e.g. name@example.com).',
            'email.unique'     => 'This email is already registered.',
            'phone.regex'      => 'Enter a valid 10-digit Indian phone number.',
            'phone.unique'     => 'This phone number is already used.',
            'organization.required' => 'Shop name is required.',
            'organization.max'      => 'Shop name cannot exceed 150 characters.',
            'address.required' => 'Address is required.',
            'address.max'      => 'Address cannot exceed 500 characters.',
            'photo.required' => 'Member photo is required.',
            'photo.image'    => 'The file must be an image (jpg, jpeg or png).',
            'photo.mimes'    => 'Photo must be a jpg, jpeg or png file.',
            'photo.max'      => 'Photo cannot exceed 2MB.',
        ]);

        // Email is optional: always store NULL (never '') so the unique index is safe
        $email = trim((string) $request->email) !== '' ? trim($request->email) : null;

        // ── Ask FUMMA whether this phone is an active FUMMA member ──
        // Done before the DB transaction so the HTTP call never holds it open.
        $isFummaMember = app(\App\Services\FummaMemberService::class)->isActive($request->phone) ? 1 : 0;

        // ── Handle photo ──
        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo     = $request->file('photo');
            $fileName  = 'member_' . time() . '_' . $photo->getClientOriginalName();
            $photoPath = $photo->storeAs('member_photos', $fileName, 'public');
        }

        // ── Create user + profile + approve, all atomically ──
        try {
            $profile = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $photoPath, $email, $isFummaMember) {
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
                    'unit_id'         => $request->unit_id,
                    'district_id'     => $request->district_id,
                    'state_id'        => $request->state_id,
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

            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

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

        $fummaNote = $isFummaMember ? ' FUMMA member: Yes.' : ' FUMMA member: No.';

        return back()->with(
            'success',
            'Member ' . $profile->user->name . ' created and approved! Membership ID: ' .
            $profile->membership_id . '.' . $fummaNote . $emailNote
        );
    }

public function updateMember(Request $request)
{
    $existingProfile = MemberProfile::find($request->id);

    $request->validate([
        'id'           => 'required|exists:member_profiles,id',
        'name'         => [
            'required', 'string', 'max:100',
            'regex:/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/',
        ],
        'phone'        => ['required', 'regex:/^[6-9]\d{9}$/'],
        'dob'          => ['required', 'date', 'before:today', new MinimumAge(18)],
        'organization' => 'required|string|max:150',
        'address'      => 'required|string|max:500',
        'photo'        => [
            Rule::requiredIf(fn() => $existingProfile && !$existingProfile->photo),
            'nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048',
        ],
    ], [
        'name.regex'     => 'Name can only contain letters, spaces, hyphens and apostrophes.',
        'phone.regex'    => 'Enter a valid 10-digit Indian phone number.',
        'photo.required' => 'This member has no photo yet. Please upload one.',
        'photo.max'      => 'Photo cannot exceed 2MB.',
        'organization.required' => 'Shop name is required.',
        'organization.max'      => 'Shop name cannot exceed 150 characters.',
        'address.required' => 'Address is required.',
        'address.max'      => 'Address cannot exceed 500 characters.',
    ]);


        $profile = MemberProfile::with('user')->findOrFail($request->id);

        // phone must stay unique across users, excluding this member's own user row
        $phoneTaken = User::where('phone', $request->phone)
            ->where('id', '!=', $profile->user_id)
            ->exists();
        if ($phoneTaken) {
            return back()->with('error', 'This phone number is already used by another account.');
        }

        $profile->user->name  = $request->name;
        $profile->user->phone = $request->phone;
        $profile->user->save();

        if ($request->hasFile('photo')) {
            if ($profile->photo) {
                Storage::disk('public')->delete($profile->photo);
            }
            $photo = $request->file('photo');
            $fileName = 'member_' . time() . '_' . $photo->getClientOriginalName();
            $profile->photo = $photo->storeAs('member_photos', $fileName, 'public');
        }

        $profile->dob          = $request->dob;
        $profile->organization = $request->organization;
        $profile->address      = $request->address;
        $profile->save();

        return back()->with('success', 'Member updated successfully!');
    }

    public function memberDetail($id)
    {
        $member = MemberProfile::with(['user', 'unit', 'district', 'state'])->find($id);

        return response()->json([
            'name'          => $member->user->name,
            'email'         => $member->user->email,
            'phone'         => $member->user->phone,
            'dob'           => $member->dob,
            'organization'  => $member->organization,
            'address'       => $member->address,
            'photo'         => $member->photo ? asset('storage/' . $member->photo) : null,
            'unit_name'     => $member->unit->name,
            'district_name' => $member->district->name,
            'state_name'    => $member->state->name,
            'membership_id' => $member->membership_id ?? '—',
            'status'        => $member->status,
            'joined_date'   => $member->joined_date ?? '—',
            'expiry_date'   => $member->expiry_date ?? '—',
            'registered_on' => $member->created_at->format('d M Y'),
        ]);
    }

    public function approveMember(Request $request)
{
    $request->validate(['id' => 'required|exists:member_profiles,id']);

    $profile = MemberProfile::with(['user', 'unit'])->findOrFail($request->id);

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
            $emailNote = ' (Could not send email — the address on file, ' .
                         $profile->user->email . ', looks invalid or undeliverable.)';
        }
    }

    return back()->with(
        'success',
        $profile->user->name . ' approved! Membership ID: ' . $profile->membership_id . '.' . $emailNote
    );
}

    public function rejectMember(Request $request)
{
    $request->validate([
        'id'     => 'required|exists:member_profiles,id',
        'reason' => 'nullable|string',
    ]);

    $profile = MemberProfile::with(['user', 'unit'])->findOrFail($request->id);

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
            $emailNote = ' (Could not send email — the address on file, ' .
                         $profile->user->email . ', looks invalid or undeliverable.)';
        }
    }

    return back()->with('success', $profile->user->name . ' rejected.' . $emailNote);
}

    public function deleteMember(Request $request)
{
    $request->validate([
        'id'       => 'required|exists:member_profiles,id',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ]);

    // Verify the logged-in super admin's password
    $admin = Auth::guard('super_admin')->user();

    if (!$admin || !\Illuminate\Support\Facades\Hash::check($request->password, $admin->password)) {
        return back()->with('error', 'Incorrect password. Member was not deleted.');
    }

    $profile = MemberProfile::findOrFail($request->id);

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, $admin->id, 'admin');
    } catch (\Throwable $e) {
        Log::error('Member delete failed for profile #' . $profile->id . ': ' . $e->getMessage());
        return back()->with('error', 'Could not delete this member. They may still have linked records (payments, registrations, etc.).');
    }

    return back()->with('success', 'Member deleted successfully!');
}

    // ─────────────────────────────────
    // SEND STATUS EMAIL (reusable)
    // NOTE: this intentionally does NOT catch its own exceptions.
    // Callers (approveMember/rejectMember) decide how to handle failure,
    // so they can customize the message shown back to the admin.
    // ─────────────────────────────────
    private function sendStatusMail(
        \App\Models\MemberProfile $profile,
        string $status,
        ?string $reason = null
    ): void {
        $profile->loadMissing(['user', 'unit']);

        $unitAdminEmail = \App\Models\UnitAdmin::with('user')
            ->where('unit_id', $profile->unit_id)
            ->first()?->user->email
            ?? config('mail.from.address');

        Mail::to($profile->user->email)
            ->send(new MemberStatusMail(
                memberName:   $profile->user->name,
                unitName:     $profile->unit->name,
                status:       $status,
                reason:       $reason,
                contactEmail: $unitAdminEmail,
                loginUrl:     url('/login'),
            ));
    }

    // ─────────────────────────────────
    // NEWS
    // ─────────────────────────────────
    public function news()
    {
        $news = News::with('createdBy')->latest()->paginate(20);

        $creatorIds = $news->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.news', [
            'news'           => $news,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    public function storeNews(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
            'status'  => 'required|in:DRAFT,PUBLISHED',
            'image'   => 'required|image|mimes:jpg,jpeg,png|max:5120', // 5MB
        ], [
            'title.max'      => 'Title cannot exceed 200 characters.',
            'image.required' => 'Please upload an image for this news post.',
            'image.image'    => 'The file must be an image (jpg, jpeg, or png).',
            'image.max'      => 'Image cannot exceed 5MB.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = 'news_' . time() . '_'
                . $image->getClientOriginalName();
            $imagePath = $image->storeAs(
                'news_images',
                $fileName,
                'public'
            );
        }

        News::create([
            'created_by' => auth()->id(),
            'title' => $request->input('title'),
            'content' => $request->input('content'),
            'image' => $imagePath,
            'status' => $request->input('status'),
            'published_at' => $request->status === 'PUBLISHED'
                ? Carbon::now()
                : null,
        ]);

        return back()->with('success', 'News created successfully!');
    }

    public function updateNews(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:news,id',
            'title' => 'required|string',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB
        ]);

        $news = News::find($request->id);

        if ($request->hasFile('image')) {
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }
            $image = $request->file('image');
            $fileName = 'news_' . time() . '_'
                . $image->getClientOriginalName();
            $news->image = $image->storeAs(
                'news_images',
                $fileName,
                'public'
            );
        }

        $news->update([
            'title' => $request->input('title'),
            'content' => $request->input('content'),
        ]);
        $news->save();

        return back()->with('success', 'News updated successfully!');
    }

    public function toggleNewsStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:news,id'
        ]);

        $news = News::find($request->id);

        if ($news->status === 'DRAFT') {
            $news->status = 'PUBLISHED';
            $news->published_at = Carbon::now();
        } else {
            $news->status = 'DRAFT';
            $news->published_at = null;
        }

        $news->save();

        $status = $news->status === 'PUBLISHED'
            ? 'published'
            : 'unpublished';

        return back()->with('success', 'News ' . $status . '!');
    }

    public function deleteNews(Request $request)
    {
        $news = News::find($request->id);

        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }

        $news->delete();
        return back()->with('success', 'News deleted successfully!');
    }

    // ─────────────────────────────────
    // EVENTS
    // ─────────────────────────────────
    public function events()
    {
        $events = Event::with('createdBy')
            ->withCount('registrations')
            ->latest()
            ->paginate(20);

        $creatorIds = $events->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.events', [
            'events'         => $events,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    public function storeEvent(Request $request)
    {
        $request->validate([
            'title'                 => 'required|string|max:200',
            'description'           => 'required|string',
            'event_date'            => 'required|date|after_or_equal:today',
            'event_time'            => 'required',
            'location'              => 'nullable|string',
            'total_seats'           => 'required|integer|min:1|max:1000',
            'registration_deadline' => 'nullable|date|before_or_equal:event_date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'                             => 'Event title cannot exceed 200 characters.',
            'event_date.after_or_equal'             => 'Event date cannot be in the past.',
            'total_seats.min'                       => 'Total seats must be at least 1.',
            'total_seats.max'                       => 'Total seats cannot exceed 1,000.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be on or before the event date.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = 'event_' . time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('event_images', $fileName, 'public');
        }

        Event::create([
            'created_by'            => auth()->id(),
            'title'                 => $request->title,
            'description'           => $request->description,
            'image'                 => $imagePath,
            'event_date'            => $request->event_date,
            'event_time'            => $request->event_time,
            'location'              => $request->location,
            'ticket_type'           => 'FREE',
            'ticket_price'          => 0,
            'total_seats'           => $request->total_seats,
            'registration_deadline' => $request->registration_deadline,
            'status'                => 'UPCOMING',
        ]);

        return back()->with('success', 'Event created successfully!');
    }

    public function updateEvent(Request $request)
    {
        $request->validate([
            'id'                    => 'required|exists:events,id',
            'title'                 => 'required|string|max:200',
            'description'           => 'required|string',
            'event_date'            => 'required|date',
            'event_time'            => 'required',
            'location'              => 'nullable|string',
            'total_seats'           => 'required|integer|min:1|max:1000',
            'registration_deadline' => 'nullable|date|before_or_equal:event_date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'                             => 'Event title cannot exceed 200 characters.',
            'total_seats.min'                       => 'Total seats must be at least 1.',
            'total_seats.max'                       => 'Total seats cannot exceed 1,000.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be on or before the event date.',
        ]);

        $event = Event::find($request->id);

        if ($request->hasFile('image')) {
            if ($event->image) Storage::disk('public')->delete($event->image);
            $image        = $request->file('image');
            $fileName     = 'event_' . time() . '_' . $image->getClientOriginalName();
            $event->image = $image->storeAs('event_images', $fileName, 'public');
        }

        $event->title                 = $request->title;
        $event->description           = $request->description;
        $event->event_date            = $request->event_date;
        $event->event_time            = $request->event_time;
        $event->location              = $request->location;
        $event->total_seats           = $request->total_seats;
        $event->registration_deadline = $request->registration_deadline;

        $event->save();

        return back()->with('success', 'Event updated successfully!');
    }

    public function updateEventStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:events,id',
            'status' => 'required|in:UPCOMING,ONGOING,COMPLETED,CANCELLED',
        ]);

        $event         = Event::find($request->id);
        $event->status = $request->status;
        $event->save();

        return back()->with('success', 'Event status updated to ' . $request->status . '!');
    }

    public function deleteEvent(Request $request)
    {
        $event = Event::find($request->id);

        if ($event->image) Storage::disk('public')->delete($event->image);

        $event->delete();
        return back()->with('success', 'Event deleted successfully!');
    }

    public function eventDetail($id)
    {
        $event = Event::find($id);

        if (!$event) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'          => $event->title,
            'description'    => $event->description,
            'event_date_raw' => $event->event_date->format('Y-m-d'),
            'event_time'     => $event->event_time,
            'location'       => $event->location,
            'total_seats'    => $event->total_seats,
            'deadline_raw'   => $event->registration_deadline
                ? $event->registration_deadline->format('Y-m-d') : null,
        ]);
    }

    public function eventRegistrations($id)
    {
        $event = Event::with(
            'registrations.user',
            'registrations.memberProfile.district',
            'registrations.memberProfile.unit'
        )->find($id);

        if (!$event) {
            return redirect()->route('admin.events')
                ->with('error', 'Event not found!');
        }

        return view('admin.event-registrations', [
            'event'         => $event,
            'registrations' => $event->registrations,
        ]);
    }

    // ─────────────────────────────────
    // CHANGE PASSWORD (Web)
    // ─────────────────────────────────
    public function showChangePassword()
    {
        return view('admin.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = auth()->user();

        if (
            !\Illuminate\Support\Facades\Hash::check(
                $request->current_password,
                $user->password
            )
        ) {
            return back()->with(
                'error',
                'Current password is incorrect!'
            );
        }

        if (
            \Illuminate\Support\Facades\Hash::check(
                $request->new_password,
                $user->password
            )
        ) {
            return back()->with(
                'error',
                'New password must be different from current password!'
            );
        }

        $user->password = \Illuminate\Support\Facades\Hash::make(
            $request->new_password
        );
        $user->save();

        return back()->with(
            'success',
            'Password changed successfully!'
        );
    }

    // ─────────────────────────────────
    // BULK IMPORT PAGE
    // ─────────────────────────────────
    public function bulkImport()
{
    $fixed = State::fixed();

    return view('admin.bulk-import', [
        'fixedState' => $fixed,
        'states'     => State::all(),
        'districts'  => District::all(),
        'units'      => Unit::all(),
    ]);
}

public function downloadTemplate()
{
    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="member_import_template.csv"',
    ];

    $fixed = State::fixed(); // Kerala, or null in multi-state mode

    // The state column only exists in multi-state mode
    $columns = ['sl_no', 'name', 'email', 'phone', 'dob', 'shop_name'];
    if (!$fixed) {
        $columns[] = 'state';
    }
    array_push($columns, 'district', 'unit', 'address');

    $unit     = Unit::with('district.state')
        ->when($fixed, fn($q) => $q->whereHas('district', fn($d) => $d->where('state_id', $fixed->id)))
        ->first();
    $state    = $unit?->district?->state?->name ?? 'Kerala';
    $district = $unit?->district?->name ?? 'Kozhikode';
    $unitName = $unit?->name ?? 'Vadakara';

    // Builds one sample row, adding the state cell only when needed
    $makeRow = function ($name, $email, $phone, $dob, $shop, $address) use ($fixed, $state, $district, $unitName) {
        $row = [$name, $email, $phone, $dob, $shop];
        if (!$fixed) {
            $row[] = $state;
        }
        return array_merge($row, [$district, $unitName, $address]);
    };

    // Rows 1-2 are examples (delete or overwrite them); the rest are blank numbered rows.
    $samples = [
        1 => $makeRow('Fathima Beevi', 'fathima.sample1@gmail.com', '9845001001', '1990-05-15', 'Fathima Stores', 'Main Road, Vadakara'),
        2 => $makeRow('Suresh Kumar',  '',                          '9845001002', '1985-08-20', 'Suresh Traders', 'Market Street, Vadakara'),
    ];

    $blankCells = count($columns) - 1; // everything except sl_no

    $callback = function () use ($columns, $samples, $blankCells) {
        $file = fopen('php://output', 'w');
        fputcsv($file, $columns);
        for ($i = 1; $i <= 30; $i++) {
            fputcsv($file, array_merge([$i], $samples[$i] ?? array_fill(0, $blankCells, '')));
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

    // ─────────────────────────────────
    // PROCESS BULK IMPORT
    // ─────────────────────────────────
        public function processBulkImport(Request $request)
    {
        // Up to ~30 rows, each may call the FUMMA API (8s timeout max), so allow enough time.
        set_time_limit(300);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ], [
            'csv_file.mimes' => 'Only .csv files are accepted.',
            'csv_file.max'   => 'File size cannot exceed 2MB.',
        ]);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        // Read the header row ONCE. If the first column is a serial number, skip it in every row.
        $header      = fgetcsv($handle);
        $firstHeader = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $header[0] ?? '')));
        $offset      = in_array($firstHeader, ['sl_no', 'sl no', 'slno', 's.no', 'sno', 'serial', 'serial_no']) ? 1 : 0;
                // Does this CSV have a "state" column? (new template: no, old template: yes)
        $headerNames = array_map(
            fn($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))),
            array_slice($header, $offset)
        );
        $hasStateCol = in_array('state', $headerNames);

        $results = [
            'success' => [], 'errors' => [],
            'total' => 0, 'created' => 0, 'failed' => 0,
        ];

        $norm = fn($v) => mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $v)));

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $cells = array_slice($row, $offset);

            // skip rows with no data
            if (count(array_filter($cells, fn($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $results['total']++;

                        // Columns after "shop_name" shift left by one when there is no state column
            $c = $hasStateCol ? 6 : 5;

            $data = [
                'name'      => trim($cells[0] ?? ''),
                'email'     => trim($cells[1] ?? ''),
                'phone'     => trim($cells[2] ?? ''),
                'dob'       => trim($cells[3] ?? ''),
                'shop_name' => trim($cells[4] ?? ''),
                'state'     => $hasStateCol ? trim($cells[5] ?? '') : '',
                'district'  => trim($cells[$c] ?? ''),
                'unit'      => trim($cells[$c + 1] ?? ''),
                'taluk'     => trim($cells[$c + 1] ?? ''),
                'address'   => trim($cells[$c + 2] ?? ''),
            ];
            
                        // Kerala is fixed: ignore whatever is typed in the CSV state column
            if ($fixed = State::fixed()) {
                $data['state'] = $fixed->name;
            }

            $errors = [];

            // ── Name ──
            if (empty($data['name']))         $errors[] = 'Name is required';
            if (strlen($data['name']) > 100)  $errors[] = 'Name cannot exceed 100 characters';
            if (!empty($data['name']) && !preg_match('/^[A-Za-z]+(?:[\s\'\-][A-Za-z]+)*$/', $data['name'])) {
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
                if (!preg_match('/\.[a-zA-Z]{2,}$/', $email)) {
                    $errors[] = 'Email must include a valid domain';
                    $emailValid = false;
                }
                if (strlen($email) > 150) {
                    $errors[] = 'Email cannot exceed 150 characters';
                    $emailValid = false;
                }

                if ($emailValid && !$this->emailDomainHasMailRecords($email)) {
                    $errors[] = 'Email domain does not appear to accept mail (check for typos)';
                }

                if (User::withTrashed()->where('email', $email)->exists()) {
                    $errors[] = 'Email already exists';
                }
            }

            // ── Phone ──
            if (empty($data['phone']) || strlen($data['phone']) != 10) $errors[] = 'Phone must be 10 digits';
            if (!preg_match('/^[6-9]\d{9}$/', $data['phone']))         $errors[] = 'Phone must start with 6-9';
            if (!empty($data['phone']) && User::withTrashed()->where('phone', $data['phone'])->exists()) {
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
                    } elseif ($parsedDob->age < 18) {
                        $errors[] = 'Member must be at least 18 years old';
                    } else {
                        $dob = $parsedDob->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $errors[] = 'DOB is not a valid date (use YYYY-MM-DD)';
                }
            }

            // ── Shop name / address ──
            if (empty($data['shop_name']))        $errors[] = 'Shop name is required';
            if (strlen($data['shop_name']) > 150) $errors[] = 'Shop name cannot exceed 150 characters';
            if (empty($data['address']))          $errors[] = 'Address is required';
            if (strlen($data['address']) > 500)   $errors[] = 'Address cannot exceed 500 characters';

            // ── State → District → Unit ──
            $stateModel = $districtModel = $unitModel = null;

            if (empty($data['state'])) {
                $errors[] = 'State is required';
            } else {
                $stateModel = State::whereRaw('LOWER(TRIM(name)) = ?', [$norm($data['state'])])->first();
                if (!$stateModel) {
                    $errors[] = "State '{$data['state']}' not found";
                }
            }

            if (empty($data['district'])) {
                $errors[] = 'District is required';
            } elseif ($stateModel) {
                $districtModel = District::where('state_id', $stateModel->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$norm($data['district'])])
                    ->first();
                if (!$districtModel) {
                    $errors[] = "District '{$data['district']}' not found in state '{$stateModel->name}'";
                }
            }

            $unitInput = !empty($data['unit']) ? $data['unit'] : ($data['taluk'] ?? '');
            if (empty($unitInput)) {
                $errors[] = 'Unit is required';
            } elseif ($districtModel) {
                $unitModel = Unit::where('district_id', $districtModel->id)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [$norm($unitInput)])
                    ->first();
                if (!$unitModel) {
                    $errors[] = "Unit '{$unitInput}' not found in district '{$districtModel->name}'";
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
                \Illuminate\Support\Facades\DB::transaction(function () use ($data, $email, $dob, $stateModel, $districtModel, $unitModel, $isFummaMember) {
                    $user = User::create([
                        'name'      => $data['name'],
                        'email'     => $email, // null when left blank
                        'phone'     => $data['phone'],
                        'password'  => \Illuminate\Support\Facades\Hash::make($data['phone']),
                        'role'      => 'member',
                        'is_active' => true,
                    ]);

                    MemberProfile::create([
                        'user_id'         => $user->id,
                        'unit_id'         => $unitModel->id,
                        'district_id'     => $districtModel->id,
                        'state_id'        => $stateModel->id,
                        'status'          => 'PENDING',
                        'dob'             => $dob,
                        'organization'    => $data['shop_name'],
                        'address'         => $data['address'],
                        'photo'           => null,
                        'is_fumma_member' => $isFummaMember,
                    ]);
                });

                $results['success'][] = [
                    'row'             => $rowNumber,
                    'name'            => $data['name'],
                    'email'           => $email,
                    'is_fumma_member' => $isFummaMember,
                ];
                $results['created']++;

            } catch (\Exception $e) {
                Log::error('Bulk import row ' . $rowNumber . ' failed: ' . $e->getMessage());

                $msg = ($e instanceof \Illuminate\Database\QueryException && ($e->errorInfo[1] ?? null) == 1062)
                    ? 'Email or phone already exists (possibly a previously deleted account)'
                    : 'Something went wrong while saving this row';

                $results['errors'][] = ['row' => $rowNumber, 'name' => $data['name'], 'email' => $email, 'errors' => [$msg]];
                $results['failed']++;
            }
        }

        fclose($handle);
        return back()->with('import_results', $results);
    }

    // ─────────────────────────────────
    // Helper: does this email's domain actually have mail (MX/A) records?
    // Used by bulk import since Laravel's 'email:rfc,dns' validation rule
    // only applies to normal form requests, not manual per-row CSV checks.
    // ─────────────────────────────────
    private function emailDomainHasMailRecords(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);
        if (!$domain) return false;

        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    public function payments(Request $request)
    {
        $query = Payment::with(['user', 'plan', 'memberProfile.unit', 'memberProfile.district'])
                    ->latest();

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('payment_type')) $query->where('payment_type', $request->payment_type);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);

        $payments = $query->get();

        return view('admin.payments', [
            'payments'       => $payments,
            'totalRevenue'   => Payment::where('status','SUCCESS')->sum('amount'),
            'memberRevenue'  => Payment::where('status','SUCCESS')->where('payment_type','MEMBERSHIP')->sum('amount'),
            'eventRevenue'   => Payment::where('status','SUCCESS')->where('payment_type','EVENT')->sum('amount'),
            'renewalRevenue' => Payment::where('status','SUCCESS')->where('payment_type','RENEWAL')->sum('amount'),
            'totalCount'     => Payment::where('status','SUCCESS')->count(),
            'pendingCount'   => Payment::where('status','PENDING')->count(),
            'failedCount'    => Payment::where('status','FAILED')->count(),
        ]);
    }

    // ─────────────────────────────────
    // PRIVATE: resolve profiles from
    // active filter + district + unit
    // ─────────────────────────────────
    private function resolveRenewalProfiles(Request $request)
    {
        $filter     = $request->get('filter', 'all');
        $districtId = $request->get('district_id');
        $unitId     = $request->get('unit_id');
        $today      = Carbon::today();

        $query = MemberProfile::with(['user'])
            ->whereIn('status', ['APPROVED', 'EXPIRED'])
            ->whereNotNull('expiry_date');

        if ($districtId) $query->where('district_id', $districtId);
        if ($unitId)     $query->where('unit_id', $unitId);

        switch ($filter) {
            case 'expired':
                $query->whereDate('expiry_date', '<',  $today); break;
            case 'due_soon':
                $query->whereDate('expiry_date', '>=', $today)
                      ->whereDate('expiry_date', '<=', $today->copy()->addDays(30)); break;
            case 'due_60':
                $query->whereDate('expiry_date', '>',  $today->copy()->addDays(30))
                      ->whereDate('expiry_date', '<=', $today->copy()->addDays(60)); break;
            case 'active':
                $query->whereDate('expiry_date', '>',  $today->copy()->addDays(60)); break;
        }

        return $query->get();
    }

    // ─────────────────────────────────
    // FUNDRAISING CAMPAIGNS
    // ─────────────────────────────────
    public function fundraising()
    {
        $campaigns = \App\Models\FundraisingCampaign::with('createdBy')
            ->latest()
            ->paginate(20);

        $creatorIds = $campaigns->pluck('created_by')->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.fundraising', [
            'campaigns'      => $campaigns,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    public function storeFundraisingCampaign(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:200',
            'description'   => 'required|string',
            'target_amount' => ['required', 'numeric', 'min:1', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'raised_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'end_date'      => 'nullable|date|after_or_equal:today',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'           => 'Title cannot exceed 200 characters.',
            'target_amount.max'   => 'Target amount cannot exceed 7 digits (max ₹99,99,999).',
            'target_amount.regex' => 'Enter a valid amount with up to 7 digits.',
            'raised_amount.max'   => 'Raised amount cannot exceed 7 digits (max ₹99,99,999).',
            'raised_amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = 'campaign_' . time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('campaign_images', $fileName, 'public');
        }

        \App\Models\FundraisingCampaign::create([
            'created_by'    => auth()->id(),
            'title'         => $request->title,
            'description'   => $request->description,
            'image'         => $imagePath,
            'target_amount' => $request->target_amount,
            'raised_amount' => $request->raised_amount ?? 0,
            'end_date'      => $request->end_date,
            'status'        => 'ACTIVE', // model's saving() event corrects this if already met
        ]);

        return back()->with('success', 'Campaign created successfully!');
    }

    public function updateFundraisingCampaign(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:fundraising_campaigns,id',
            'title'         => 'required|string|max:200',
            'description'   => 'required|string',
            'target_amount' => ['required', 'numeric', 'min:1', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'raised_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'end_date'      => 'nullable|date',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'           => 'Title cannot exceed 200 characters.',
            'target_amount.max'   => 'Target amount cannot exceed 7 digits (max ₹99,99,999).',
            'target_amount.regex' => 'Enter a valid amount with up to 7 digits.',
            'raised_amount.max'   => 'Raised amount cannot exceed 7 digits (max ₹99,99,999).',
            'raised_amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        $campaign = \App\Models\FundraisingCampaign::find($request->id);

        if ($request->hasFile('image')) {
            if ($campaign->image) {
                Storage::disk('public')->delete($campaign->image);
            }
            $image           = $request->file('image');
            $fileName        = 'campaign_' . time() . '_' . $image->getClientOriginalName();
            $campaign->image = $image->storeAs('campaign_images', $fileName, 'public');
        }

        $campaign->title         = $request->title;
        $campaign->description   = $request->description;
        $campaign->target_amount = $request->target_amount;
        $campaign->raised_amount = $request->raised_amount ?? $campaign->raised_amount;
        $campaign->end_date      = $request->end_date;
        // status is now handled automatically by the model's saving() event

        $campaign->save();

        return back()->with('success', 'Campaign updated successfully!');
    }

    public function updateFundraisingStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:fundraising_campaigns,id',
            'status' => 'required|in:ACTIVE,COMPLETED,CANCELLED',
        ]);

        $campaign         = \App\Models\FundraisingCampaign::find($request->id);
        $campaign->status = $request->status;
        $campaign->save();

        return back()->with('success', 'Campaign status updated to ' . $request->status . '!');
    }

    public function deleteFundraisingCampaign(Request $request)
    {
        $campaign = \App\Models\FundraisingCampaign::find($request->id);

        if ($campaign->image) {
            Storage::disk('public')->delete($campaign->image);
        }

        $campaign->delete();
        return back()->with('success', 'Campaign deleted successfully!');
    }

    public function fundraisingCampaignDetail($id)
    {
        $campaign = \App\Models\FundraisingCampaign::find($id);

        if (!$campaign) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'          => $campaign->title,
            'description'    => $campaign->description,
            'target_amount'  => $campaign->target_amount,
            'raised_amount'  => $campaign->raised_amount,
            'end_date_raw'   => $campaign->end_date ? $campaign->end_date->format('Y-m-d') : null,
            'status'         => $campaign->status,
        ]);
    }

    // ─────────────────────────────────
    // OTHER FEES (admin)
    // ─────────────────────────────────
    public function otherFees()
    {
        $fees = \App\Models\OtherFee::with('createdBy')
            ->withCount('payments')
            ->latest()
            ->paginate(20);

        $creatorIds = $fees->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.other-fees', [
            'fees'           => $fees,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    public function storeOtherFee(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:200',
            'amount'   => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'due_date' => 'nullable|date|after_or_equal:today',
        ], [
            'title.max'               => 'Title cannot exceed 200 characters.',
            'amount.max'              => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'            => 'Enter a valid amount with up to 7 digits.',
            'due_date.date'           => 'Please enter a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ]);

        \App\Models\OtherFee::create([
            'title'      => $request->title,
            'amount'     => $request->amount,
            'due_date'   => $request->due_date,
            'created_by' => auth()->id(),
            'is_active'  => true,
        ]);

        return back()->with('success', 'Other fee created successfully! It will apply to all active members.');
    }

    public function updateOtherFee(Request $request)
    {
        $request->validate([
            'id'       => 'required|exists:other_fees,id',
            'title'    => 'required|string|max:200',
            'amount'   => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'due_date' => 'nullable|date|after_or_equal:today',
        ], [
            'title.max'               => 'Title cannot exceed 200 characters.',
            'amount.max'              => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'            => 'Enter a valid amount with up to 7 digits.',
            'due_date.date'           => 'Please enter a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ]);

        \App\Models\OtherFee::find($request->id)->update([
            'title'    => $request->title,
            'amount'   => $request->amount,
            'due_date' => $request->due_date,
        ]);

        return back()->with('success', 'Other fee updated successfully!');
    }

    public function toggleOtherFeeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:other_fees,id'
        ]);

        $fee = \App\Models\OtherFee::find($request->id);
        $fee->is_active = !$fee->is_active;
        $fee->save();

        $status = $fee->is_active ? 'activated' : 'deactivated';
        return back()->with('success', 'Fee ' . $status . '! ' . ($fee->is_active
            ? 'It will now apply to members who check their dues.'
            : 'No new members will be charged this fee.'));
    }

    public function deleteOtherFee(Request $request)
    {
        $fee = \App\Models\OtherFee::find($request->id);

        // Prevent deleting a fee that already has payments recorded
        if ($fee->payments()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'Cannot delete! Some members have already paid this fee. Deactivate it instead.');
        }

        $fee->delete(); // cascades to unpaid other_fee_payments
        return back()->with('success', 'Other fee deleted successfully!');
    }

    public function otherFeeDetail($id)
    {
        $fee = \App\Models\OtherFee::find($id);

        if (!$fee) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'        => $fee->title,
            'amount'       => $fee->amount,
            'due_date_raw' => $fee->due_date ? $fee->due_date->format('Y-m-d') : null,
            'is_active'    => $fee->is_active,
        ]);
    }

    // Optional: see who has/hasn't paid a given fee
    public function otherFeePayers($id)
    {
        $fee = \App\Models\OtherFee::with([
            'payments.memberProfile.user',
            'payments.memberProfile.unit',
        ])->find($id);

        if (!$fee) {
            return redirect()->route('admin.other-fees')->with('error', 'Fee not found!');
        }

        return view('admin.other-fee-payers', [
            'fee'      => $fee,
            'payments' => $fee->payments,
        ]);
    }

    public function schemes()
    {
        $schemes = \App\Models\Scheme::with('createdBy')
            ->withCount(['dues', 'members'])
            ->latest()
            ->paginate(20);

        $creatorIds = $schemes->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.schemes', [
            'schemes'        => $schemes,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
            'allMembers'     => \App\Models\MemberProfile::with('user')
                ->where('status', 'APPROVED')
                ->get(),
        ]);
    }

    public function storeScheme(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'amount'          => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'duration_months' => 'required|integer|min:1|max:360',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'member_ids'      => 'required|array|min:1',
            'member_ids.*'    => 'exists:member_profiles,id',
        ], [
            'member_ids.required' => 'Select at least one member for this scheme.',
            'title.max'           => 'Title cannot exceed 200 characters.',
            'amount.max'          => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'        => 'Enter a valid amount with up to 7 digits.',
            'duration_months.max' => 'Duration cannot exceed 360 months (30 years).',
        ]);

        $scheme = \App\Models\Scheme::create([
            'title'           => $request->title,
            'description'     => $request->description,
            'amount'          => $request->amount,
            'duration_months' => $request->duration_months,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'created_by'      => auth()->id(),
            'is_active'       => true,
        ]);

        $scheme->members()->sync($request->member_ids);

        return back()->with('success', 'Scheme created successfully! Applied to ' . count($request->member_ids) . ' selected member(s).');
    }

    public function updateScheme(Request $request)
    {
        $request->validate([
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'amount'          => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'duration_months' => 'required|integer|min:1|max:360',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'member_ids'      => 'required|array|min:1',
            'member_ids.*'    => 'exists:member_profiles,id',
        ], [
            'member_ids.required' => 'Select at least one member for this scheme.',
            'title.max'           => 'Title cannot exceed 200 characters.',
            'amount.max'          => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'        => 'Enter a valid amount with up to 7 digits.',
            'duration_months.max' => 'Duration cannot exceed 360 months (30 years).',
        ]);

        $scheme = \App\Models\Scheme::find($request->id);

        $scheme->update([
            'title'           => $request->title,
            'description'     => $request->description,
            'amount'          => $request->amount,
            'duration_months' => $request->duration_months,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ]);

        // Sync members — adding new ones, removing unselected ones.
        // Existing SchemeFeeDue rows for removed members are left untouched
        // (their history stays intact); they just stop getting new monthly dues.
        $scheme->members()->sync($request->member_ids);

        return back()->with('success', 'Scheme updated successfully! Now applies to ' . count($request->member_ids) . ' selected member(s).');
    }

    public function schemeDetail($id)
    {
        $scheme = \App\Models\Scheme::with('members.user')->find($id);

        if (!$scheme) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'            => $scheme->title,
            'description'      => $scheme->description,
            'amount'           => $scheme->amount,
            'duration_months'  => $scheme->duration_months,
            'start_date_raw'   => $scheme->start_date?->format('Y-m-d'),
            'end_date_raw'     => $scheme->end_date?->format('Y-m-d'),
            'is_active'        => $scheme->is_active,
            'member_ids'       => $scheme->members->pluck('id'),
            'members'          => $scheme->members->map(function ($m) {
                return [
                    'id'    => $m->id,
                    'name'  => $m->user->name ?? '—',
                    'email' => $m->user->email ?? '—',
                ];
            }),
        ]);
    }

    public function toggleSchemeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:schemes,id'
        ]);

        $scheme = \App\Models\Scheme::find($request->id);
        $scheme->is_active = !$scheme->is_active;
        $scheme->save();

        $status = $scheme->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Scheme {$status}!");
    }

    public function deleteScheme(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:schemes,id'
        ]);

        $scheme = \App\Models\Scheme::find($request->id);

        // Prevent deleting a scheme that already has paid dues
        if ($scheme->dues()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'Cannot delete! Some members have already paid dues under this scheme. Deactivate it instead.');
        }

        $scheme->delete(); // cascades to scheme_members pivot and unpaid dues, if configured
        return back()->with('success', 'Scheme deleted successfully!');
    }

    public function schemePayers($id)
    {
        $scheme = \App\Models\Scheme::with([
            'dues.memberProfile.user',
            'dues.memberProfile.unit',
        ])->find($id);

        if (!$scheme) {
            return redirect()->route('admin.schemes')->with('error', 'Scheme not found!');
        }

        return view('admin.scheme-payers', [
            'scheme' => $scheme,
            'dues'   => $scheme->dues,
        ]);
    }

    // ─────────────────────────────────
    // COMMITTEES
    // ─────────────────────────────────
    public function committees()
    {
        $committees = \App\Models\Committee::with('createdBy')
            ->withCount('members')
            ->latest()
            ->paginate(20);

        $creatorIds = $committees->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.committees', [
            'committees'     => $committees,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    public function storeCommittee(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ], [
            'name.max' => 'Committee name cannot exceed 200 characters.',
        ]);

        \App\Models\Committee::create([
            'name'        => $request->name,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'created_by'  => auth()->id(),
            'is_active'   => true,
        ]);

        return back()->with('success', 'Committee created successfully! Now add members to it.');
    }

    public function updateCommittee(Request $request)
    {
        $request->validate([
            'id'          => 'required|exists:committees,id',
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ], [
            'name.max' => 'Committee name cannot exceed 200 characters.',
        ]);

        \App\Models\Committee::find($request->id)->update([
            'name'        => $request->name,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
        ]);

        return back()->with('success', 'Committee updated successfully!');
    }

    public function toggleCommitteeStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:committees,id']);

        $committee = \App\Models\Committee::find($request->id);
        $committee->is_active = !$committee->is_active;
        $committee->save();

        $status = $committee->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Committee {$status}!");
    }

    public function deleteCommittee(Request $request)
    {
        $committee = \App\Models\Committee::find($request->id);
        $committee->delete(); // cascades to committee_members
        return back()->with('success', 'Committee deleted successfully!');
    }

    public function committeeDetail($id)
    {
        $committee = \App\Models\Committee::find($id);

        if (!$committee) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'name'           => $committee->name,
            'description'    => $committee->description,
            'start_date_raw' => $committee->start_date->format('Y-m-d'),
            'end_date_raw'   => $committee->end_date?->format('Y-m-d'),
            'is_active'      => $committee->is_active,
        ]);
    }

    // ── Manage members of a committee: add / edit role / remove ──
    public function committeeMembers($id)
    {
        $committee = \App\Models\Committee::with(['members.user'])->find($id);

        if (!$committee) {
            return redirect()->route('admin.committees')->with('error', 'Committee not found!');
        }

        $assignedIds = $committee->members->pluck('id');

        $availableMembers = \App\Models\MemberProfile::with('user')
            ->where('status', 'APPROVED')
            ->whereNotIn('id', $assignedIds)
            ->get();

        return view('admin.committee-members', [
            'committee'        => $committee,
            'availableMembers' => $availableMembers,
        ]);
    }

    public function addCommitteeMember(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
            'role'              => 'required|string|max:100',
        ]);

        $committee = \App\Models\Committee::find($request->committee_id);

        if ($committee->members()->where('member_profiles.id', $request->member_profile_id)->exists()) {
            return back()->with('error', 'This member is already part of the committee.');
        }

        $committee->members()->attach($request->member_profile_id, ['role' => $request->role]);

        return back()->with('success', 'Member added to committee!');
    }

    public function updateCommitteeMemberRole(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
            'role'              => 'required|string|max:100',
        ]);

        $committee = \App\Models\Committee::find($request->committee_id);
        $committee->members()->updateExistingPivot($request->member_profile_id, ['role' => $request->role]);

        return back()->with('success', 'Member role updated!');
    }

    public function removeCommitteeMember(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
        ]);

        $committee = \App\Models\Committee::find($request->committee_id);
        $committee->members()->detach($request->member_profile_id);

        return back()->with('success', 'Member removed from committee.');
    }

    // ─────────────────────────────────
    // GALLERY
    // ─────────────────────────────────
    public function gallery()
    {
        $images = \App\Models\GalleryImage::with('createdBy')
            ->latest()
            ->paginate(24);

        $creatorIds = $images->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.gallery', [
            'images'         => $images,
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    public function storeGalleryImages(Request $request)
    {
        $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        foreach ($request->file('images') as $file) {
            $fileName = 'gallery_' . time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $path     = $file->storeAs('gallery_images', $fileName, 'public');

            \App\Models\GalleryImage::create([
                'image'      => $path,
                'created_by' => auth()->id(),
                'is_active'  => true,
            ]);
        }

        return back()->with('success', count($request->file('images')) . ' image(s) uploaded successfully!');
    }

    public function toggleGalleryImageStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:gallery_images,id']);

        $image = \App\Models\GalleryImage::find($request->id);
        $image->is_active = !$image->is_active;
        $image->save();

        $status = $image->is_active ? 'shown in' : 'hidden from';
        return back()->with('success', "Image {$status} the app gallery.");
    }

    public function deleteGalleryImage(Request $request)
    {
        $image = \App\Models\GalleryImage::find($request->id);

        if ($image->image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image);
        }

        $image->delete();
        return back()->with('success', 'Image deleted successfully!');
    }

    // ─────────────────────────────────
    // MONTHLY FEE SETTING
    // ─────────────────────────────────
    public function monthlyFee()
    {
        return view('admin.monthly-fee', [
            'setting' => \App\Models\MonthlyFeeSetting::current(),
        ]);
    }

    public function updateMonthlyFee(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:200',
            'amount'  => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'due_day' => 'required|integer|min:1|max:28',
        ], [
            'title.max'    => 'Title cannot exceed 200 characters.',
            'amount.max'   => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex' => 'Enter a valid amount with up to 7 digits.',
            'due_day.max'  => 'Due day cannot be later than the 28th, so it stays valid in every month including February.',
        ]);

        $setting = \App\Models\MonthlyFeeSetting::current();
        $setting->update([
            'title'   => $request->title,
            'amount'  => $request->amount,
            'due_day' => $request->due_day,
        ]);

        return back()->with('success', 'Monthly fee updated! This applies to dues generated from now on — dues already created for the current month keep their old amount.');
    }

    // ─────────────────────────────────
    // EXPENSES (admin)
    // ─────────────────────────────────
    public function expenses()
    {
        $today = Carbon::today();

        $expenses = \App\Models\Expense::with('createdBy')
            ->latest('expense_date')
            ->paginate(20);

        // ── Quick stats ──
        $todaysExpense     = \App\Models\Expense::whereDate('expense_date', $today)->sum('amount');
        $thisMonthExpense  = \App\Models\Expense::whereYear('expense_date', $today->year)
            ->whereMonth('expense_date', $today->month)->sum('amount');
        $thisYearExpense   = \App\Models\Expense::whereYear('expense_date', $today->year)->sum('amount');
        $totalTransactions = \App\Models\Expense::whereYear('expense_date', $today->year)->count();

        // ── Report: category breakdown (this month) ──
        $categoryTotals = \App\Models\Expense::whereYear('expense_date', $today->year)
            ->whereMonth('expense_date', $today->month)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // ── Report: month-wise totals (this year) ──
        $monthlyTotals = \App\Models\Expense::whereYear('expense_date', $today->year)
            ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('month')
            ->orderByDesc('month')
            ->get();

        return view('admin.expenses', [
            'expenses'   => $expenses,
            'categories' => ['Maintenance', 'Events', 'Utilities', 'Cleaning', 'Stationery', 'Others'],

            'todaysExpense'     => $todaysExpense,
            'thisMonthExpense'  => $thisMonthExpense,
            'thisYearExpense'   => $thisYearExpense,
            'totalTransactions' => $totalTransactions,
            'categoryTotals'    => $categoryTotals,
            'monthlyTotals'     => $monthlyTotals,
        ]);
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:100',
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
            'paid_to'      => 'nullable|string|max:150',
            'status'       => 'required|in:PENDING,APPROVED,PAID,REJECTED',
        ], [
            'amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        \App\Models\Expense::create([
            'title'        => $request->title,
            'category'     => $request->category,
            'amount'       => $request->amount,
            'expense_date' => $request->expense_date,
            'expense_time' => $request->expense_time ?: now()->format('H:i'),
            'paid_to'      => $request->paid_to,
            'status'       => $request->status,
            'created_by'   => auth()->id(),
            'unit_id'      => null,   // ← global expense, every member sees it
        ]);

        return back()->with('success', 'Expense recorded successfully!');
    }

    public function updateExpense(Request $request)
    {
        $request->validate([
            'id'           => 'required|exists:expenses,id',
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:100',
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
            'paid_to'      => 'nullable|string|max:150',
            'status'       => 'required|in:PENDING,APPROVED,PAID,REJECTED',
        ], [
            'amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        \App\Models\Expense::find($request->id)->update($request->only([
            'title', 'category', 'amount', 'expense_date', 'expense_time', 'paid_to', 'status',
        ]));

        return back()->with('success', 'Expense updated successfully!');
    }

    public function deleteExpense(Request $request)
    {
        $request->validate(['id' => 'required|exists:expenses,id']);
        \App\Models\Expense::find($request->id)->delete();
        return back()->with('success', 'Expense deleted successfully!');
    }

    public function expenseDetail($id)
    {
        $expense = \App\Models\Expense::find($id);
        if (!$expense) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'        => $expense->title,
            'category'     => $expense->category,
            'amount'       => $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'expense_time' => $expense->expense_time,
            'paid_to'      => $expense->paid_to,
            'status'       => $expense->status,
        ]);
    }

    // ─────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────
    public function logout(Request $request)
    {
        Auth::guard('super_admin')->logout();

        return redirect()->route('admin.login');
    }
}