<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // ── Show forgot password form ──
    public function showForgotForm()
    {
        return view('admin.forgot-password');
    }

    // ── Send reset link ──
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
                    ->whereIn('role', ['super_admin', 'unit_admin'])
                    ->first();

        // Always show success message — don't reveal if email exists
        if (!$user) {
            return back()->with('success',
                'If that email exists in our system, a reset link has been sent.');
        }

        // Delete any existing token for this email
        DB::table('password_reset_tokens')
            ->where('email', $request->email)->delete();

        // Generate token
        $token = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Build reset URL — detect if unit admin or super admin
        $route = $user->role === 'unit_admin'
            ? 'unit-admin.password.reset.form'
            : 'admin.password.reset.form';

        $resetUrl = route($route, [
            'token' => $token,
            'email' => $request->email,
        ]);

        Mail::to($user->email)->send(
            new PasswordResetMail($resetUrl, $user->name)
        );

        return back()->with('success',
            'If that email exists in our system, a reset link has been sent.');
    }

    // ── Show reset form ──
    public function showResetForm(Request $request)
    {
        return view('admin.reset-password', [
            'token' => $request->token,
            'email' => $request->email,
        ]);
    }

    // ── Handle password reset ──
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required',
            'password'              => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        // Find the token record
        $record = DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->first();

        if (!$record) {
            return back()->withErrors(['email' => 'Invalid or expired reset link.']);
        }

        // Check token expired (60 minutes)
        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'Reset link has expired. Please request a new one.']);
        }

        // Verify token
        if (!Hash::check($request->token, $record->token)) {
            return back()->withErrors(['email' => 'Invalid reset link.']);
        }

        // Update password
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Delete used token
        DB::table('password_reset_tokens')
            ->where('email', $request->email)->delete();

        // Redirect to correct login page
        $loginRoute = $user->role === 'unit_admin'
            ? 'unit-admin.login'
            : 'admin.login';

        return redirect()->route($loginRoute)
            ->with('success', 'Password reset successfully! Please sign in.');
    }
}