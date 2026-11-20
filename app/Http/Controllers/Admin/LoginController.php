<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected string $guard = 'super_admin';
    protected string $role  = 'super_admin';

    public function showLogin()
    {
        if (Auth::guard('super_admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        if (Auth::guard('unit_admin')->check()) {
            return redirect()->route('unit-admin.dashboard');
        }
        if (Auth::guard('district_admin')->check()) {
            return redirect()->route('district-admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->email);
        $password   = trim($request->password);
        $loginField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // 1. Check Super Admin
        $superCredentials = [
            $loginField => $loginInput,
            'password'  => $password,
            'role'      => 'super_admin',
        ];

        if (Auth::guard('super_admin')->attempt($superCredentials)) {
            if (!Auth::guard('super_admin')->user()->is_active) {
                Auth::guard('super_admin')->logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
            }

            $request->session()->regenerate();
            return redirect()->route('admin.dashboard');
        }

        // 2. Check Unit Admin (allow login directly from main login page via 6-digit Unit ID, Email, or Phone)
        $unitUser = null;

        // Try 6-digit Unit ID (or numeric <= 6 digits) first
        if (is_numeric($loginInput) && strlen($loginInput) <= 6) {
            $ua = \App\Models\UnitAdmin::where('unit_id', (int) $loginInput)->first();
            if ($ua && $ua->user && $ua->user->role === 'unit_admin') {
                $unitUser = $ua->user;
            }
        }

        // Try Unit Name
        if (!$unitUser && !filter_var($loginInput, FILTER_VALIDATE_EMAIL) && strlen($loginInput) < 10) {
            $ua = \App\Models\UnitAdmin::whereHas('unit', fn($q) => $q->where('name', $loginInput))->first();
            if ($ua && $ua->user && $ua->user->role === 'unit_admin') {
                $unitUser = $ua->user;
            }
        }

        // Try Email
        if (!$unitUser && filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $unitUser = \App\Models\User::where('email', strtolower($loginInput))->where('role', 'unit_admin')->first();
        }

        // Try Phone
        if (!$unitUser) {
            $unitUser = \App\Models\User::where('phone', $loginInput)->where('role', 'unit_admin')->first();
        }

        // Try fallback Unit ID if numeric
        if (!$unitUser && is_numeric($loginInput)) {
            $ua = \App\Models\UnitAdmin::where('unit_id', (int) $loginInput)->first();
            if ($ua && $ua->user && $ua->user->role === 'unit_admin') {
                $unitUser = $ua->user;
            }
        }

        if ($unitUser) {
            $passwordValid = \Illuminate\Support\Facades\Hash::check($password, $unitUser->password) || $password === $unitUser->phone;
            if ($passwordValid) {
                if (!\Illuminate\Support\Facades\Hash::check($password, $unitUser->password)) {
                    $unitUser->password = $password;
                    $unitUser->save();
                }

                if (!$unitUser->is_active) {
                    return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
                }

                Auth::guard('unit_admin')->login($unitUser);
                $request->session()->regenerate();
                return redirect()->route('unit-admin.dashboard');
            }
        }

        // 3. Check District Admin
        $districtCredentials = [
            $loginField => $loginInput,
            'password'  => $password,
            'role'      => 'district_admin',
        ];

        if (Auth::guard('district_admin')->attempt($districtCredentials)) {
            if (!Auth::guard('district_admin')->user()->is_active) {
                Auth::guard('district_admin')->logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
            }

            $request->session()->regenerate();
            return redirect()->route('district-admin.dashboard');
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
    }
}