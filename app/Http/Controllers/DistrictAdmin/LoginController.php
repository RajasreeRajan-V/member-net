<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected string $guard = 'district_admin';
    protected string $role  = 'district_admin';

    public function showLogin()
    {
        if (Auth::guard($this->guard)->check()) {
            return redirect()->route('district-admin.dashboard');
        }
        return view('district-admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = [
            'email'    => $request->email,
            'password' => $request->password,
            'role'     => $this->role,
        ];

        if (!Auth::guard($this->guard)->attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if (!Auth::guard($this->guard)->user()->is_active) {
            Auth::guard($this->guard)->logout();
            return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
        }

        $request->session()->regenerate();
        return redirect()->route('district-admin.dashboard');
    }
}