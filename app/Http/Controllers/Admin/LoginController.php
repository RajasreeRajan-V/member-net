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

        // 2. Check Unit Admin (allow login directly from main login page)
        $unitCredentials = [
            $loginField => $loginInput,
            'password'  => $password,
            'role'      => 'unit_admin',
        ];

        if (Auth::guard('unit_admin')->attempt($unitCredentials)) {
            if (!Auth::guard('unit_admin')->user()->is_active) {
                Auth::guard('unit_admin')->logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
            }

            $request->session()->regenerate();
            return redirect()->route('unit-admin.dashboard');
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