<?php

namespace App\Http\Controllers\UnitAdmin;

use App\Http\Controllers\Controller;
use App\Models\UnitAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $user       = null;

        // 1. Try finding UnitAdmin by Unit ID (e.g. KAN001)
        $ua = UnitAdmin::whereHas('unit', fn($q) => $q->where('unit_id', strtoupper($loginInput)))->first();
        if ($ua && $ua->user && $ua->user->role === $this->role) {
            $user = $ua->user;
        }

        // 2. Try finding UnitAdmin by 6-digit numeric Unit ID (or numeric <= 6 digits)
        if (!$user && is_numeric($loginInput) && strlen($loginInput) <= 6) {
            $ua = UnitAdmin::where('unit_id', (int) $loginInput)->first();
            if ($ua && $ua->user && $ua->user->role === $this->role) {
                $user = $ua->user;
            }
        }

        // 3. Try by Unit Name if not found yet
        if (!$user && !filter_var($loginInput, FILTER_VALIDATE_EMAIL) && strlen($loginInput) < 10) {
            $ua = UnitAdmin::whereHas('unit', fn($q) => $q->where('name', $loginInput))->first();
            if ($ua && $ua->user && $ua->user->role === $this->role) {
                $user = $ua->user;
            }
        }

        // 3. Try by Email
        if (!$user && filter_var($loginInput, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', strtolower($loginInput))->where('role', $this->role)->first();
        }

        // 4. Try by Phone as username
        if (!$user) {
            $user = User::where('phone', $loginInput)->where('role', $this->role)->first();
        }

        // 5. Try fallback Unit ID if numeric
        if (!$user && is_numeric($loginInput)) {
            $ua = UnitAdmin::where('unit_id', (int) $loginInput)->first();
            if ($ua && $ua->user && $ua->user->role === $this->role) {
                $user = $ua->user;
            }
        }

        // 6. Try by Name
        if (!$user) {
            $user = User::where('name', $loginInput)->where('role', $this->role)->first();
        }

        // Validate password against user's phone number or hashed password
        $passwordValid = false;
        if ($user) {
            if (Hash::check($password, $user->password) || $password === $user->phone) {
                $passwordValid = true;
                if (!Hash::check($password, $user->password)) {
                    $user->password = $password;
                    $user->save();
                }
            }
        }

        if (!$user || !$passwordValid) {
            return back()->withErrors(['email' => 'Invalid Unit ID / username or password.'])->withInput();
        }

        if (!$user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
        }

        Auth::guard($this->guard)->login($user);
        $request->session()->regenerate();

        return redirect()->route('unit-admin.dashboard');
    }
}