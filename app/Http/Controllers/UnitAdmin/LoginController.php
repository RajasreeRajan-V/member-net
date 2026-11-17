<?php

namespace App\Http\Controllers\UnitAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected string $guard = 'unit_admin';
    protected string $role  = 'unit_admin';

    public function showLogin()
    {
        if (Auth::guard($this->guard)->check()) {
            return redirect()->route('unit-admin.dashboard');
        }
        return view('unit-admin.login');
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

        $credentials = [
            $loginField => $loginInput,
            'password'  => $password,
            'role'      => $this->role,
        ];

        if (!Auth::guard($this->guard)->attempt($credentials)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        if (!Auth::guard($this->guard)->user()->is_active) {
            Auth::guard($this->guard)->logout();
            return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
        }

        $request->session()->regenerate();
        return redirect()->route('unit-admin.dashboard');
    }
}