<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class AuthController extends Controller
{
    // ─────────────────────────────────
    // POST /api/login
    // ─────────────────────────────────
    public function login(Request $request)
    {
        if ($request->isMethod('GET')) {
            return response()->json([
                'status'      => '0',
                'status_code' => '405',
                'message'     => 'The /api/login endpoint requires a POST request with "email" and "password".',
                'data'        => [
                    'method'   => 'POST',
                    'endpoint' => url('/api/login'),
                    'payload'  => [
                        'email'    => 'user@example.com',
                        'password' => 'your_password',
                    ],
                ],
            ], 405);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ])->stopOnFirstFailure();

        if ($validator->fails()) {
            return response()->json([
                'status'      => '0',
                'status_code' => '422',
                'message'     => $validator->errors()->first(),
                'data'        => (object) [],
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid email or password', 401);
        }

        if (!$user->is_active) {
            return ApiResponse::error('Your account is disabled. Contact admin.', 403);
        }

        // ══════════════════════════════
        // ROLE: member
        // ══════════════════════════════
        if ($user->role === 'member') {

            $profile = \App\Models\MemberProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return ApiResponse::error('Member profile not found.', 404);
            }

            // ── PENDING or REJECTED ──
            if (in_array($profile->status, ['PENDING', 'REJECTED'])) {
                return response()->json([
                    'status'      => 0,
                    'status_code' => 403,
                    'message'     => $profile->status === 'PENDING'
                                        ? 'Your registration is under review by the unit admin.'
                                        : 'Your registration was rejected. Please contact your unit admin.',
                    'data'        => [
                        'approval_status' => $profile->status === 'PENDING'
                                                ? 'under_review'
                                                : 'rejected',
                    ]
                ], 403);
            }

            // ── APPROVED / ACTIVE — full login ──
            if ($profile->status === 'APPROVED' || $profile->status === 'ACTIVE') {
                $user->tokens()->delete();
                $token            = $user->createToken('auth_token')->plainTextToken;
                $user->auth_token = $token;
                $user->save();

                $profile->load(['unit', 'district', 'state']);

                // NOTE: Membership ID is created at approval, never here.

                return response()->json([
                    'status'      => 1,
                    'status_code' => 200,
                    'message'     => 'Login successful',
                    'data'        => [
                        'user_id'         => (string) $user->id,
                        'name'            => $user->name,
                        'email'           => $user->email,
                        'phone'           => $user->phone,
                        'role'            => $user->role,
                        'token'           => $token,
                        'membership_id'   => $profile->membership_id,
                        'joined_date'     => (string) $profile->joined_date,
                        'dob'             => (string) $profile->dob,
                        'organization'    => $profile->organization,
                        'photo'           => $profile->photo ? asset('storage/' . $profile->photo) : null,
                        'unit_id'         => (string) $profile->unit_id,
                        'unit_name'       => $profile->unit->name ?? null,
                        'state_id'        => (string) $profile->state_id,
                        'state_name'      => $profile->state->name ?? null,
                        'approval_status' => 'approved',
                        'payment_status'  => 'not_required',
                    ]
                ], 200);
            }
        }

        // ══════════════════════════════
        // ROLE: super_admin
        // ══════════════════════════════
        if ($user->role === 'super_admin') {
            $user->tokens()->delete();
            $token            = $user->createToken('auth_token')->plainTextToken;
            $user->auth_token = $token;
            $user->save();

            return response()->json([
                'status'      => 1,
                'status_code' => 200,
                'message'     => 'Login successful',
                'data'        => [
                    'user_id' => (string) $user->id,
                    'name'    => $user->name,
                    'email'   => $user->email,
                    'phone'   => $user->phone,
                    'role'    => $user->role,
                    'token'   => $token,
                ]
            ], 200);
        }

        // ══════════════════════════════
        // ROLE: district_admin
        // ══════════════════════════════
        if ($user->role === 'district_admin') {
            $districtAdmin = \App\Models\DistrictAdmin::with(['district.state'])
                                ->where('user_id', $user->id)
                                ->first();

            if (!$districtAdmin) {
                return ApiResponse::error('District Admin profile not found. Contact admin.', 404);
            }

            $user->tokens()->delete();
            $token            = $user->createToken('auth_token')->plainTextToken;
            $user->auth_token = $token;
            $user->save();

            return response()->json([
                'status'      => 1,
                'status_code' => 200,
                'message'     => 'Login successful',
                'data'        => [
                    'user_id'       => (string) $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'phone'         => $user->phone,
                    'role'          => $user->role,
                    'token'         => $token,
                    'district_id'   => (string) $districtAdmin->district_id,
                    'district_name' => $districtAdmin->district->name ?? null,
                    'state_id'      => (string) ($districtAdmin->district->state_id ?? ''),
                    'state_name'    => $districtAdmin->district->state->name ?? null,
                ]
            ], 200);
        }

        // ══════════════════════════════
        // ROLE: unit_admin
        // ══════════════════════════════
        if ($user->role === 'unit_admin') {
            $unitAdmin = \App\Models\UnitAdmin::with(['unit.district.state'])
                            ->where('user_id', $user->id)
                            ->first();

            if (!$unitAdmin) {
                return ApiResponse::error('Unit Admin profile not found. Contact admin.', 404);
            }

            $user->tokens()->delete();
            $token            = $user->createToken('auth_token')->plainTextToken;
            $user->auth_token = $token;
            $user->save();

            return response()->json([
                'status'      => 1,
                'status_code' => 200,
                'message'     => 'Login successful',
                'data'        => [
                    'user_id'       => (string) $user->id,
                    'name'          => $user->name,
                    'email'         => $user->email,
                    'phone'         => $user->phone,
                    'role'          => $user->role,
                    'token'         => $token,
                    'unit_id'       => (string) $unitAdmin->unit_id,
                    'unit_name'     => $unitAdmin->unit->name ?? null,
                    'state_id'      => (string) ($unitAdmin->unit->district->state_id ?? \App\Models\State::fixed()?->id ?? '1'),
                    'state_name'    => $unitAdmin->unit->district->state->name ?? \App\Models\State::fixed()?->name ?? 'Kerala',
                ]
            ], 200);
        }

        // ── Fallback (unknown role) ──
        return ApiResponse::error('Unauthorized role.', 403);
    }
    
    // ─────────────────────────────────
// POST /api/v2/login
// Members: membership ID + phone number
// Admins : email + password
// ─────────────────────────────────
public function loginV2(Request $request)
{
    if ($request->isMethod('GET')) {
        return response()->json([
            'status'      => '0',
            'status_code' => '405',
            'message'     => 'The /api/v2/login endpoint requires a POST request with "username" and "password".',
            'data'        => [
                'method'   => 'POST',
                'endpoint' => url('/api/v2/login'),
                'payload'  => [
                    'username' => 'MemberNet/XXX/001 (or phone/email)',
                    'password' => 'your_password (phone number for members)',
                ],
            ],
        ], 405);
    }

    $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
        'username' => 'required|string',
        'password' => 'required|string',
    ])->stopOnFirstFailure();

    if ($validator->fails()) {
        return response()->json([
            'status'      => '0',
            'status_code' => '422',
            'message'     => $validator->errors()->first(),
            'data'        => (object) [],
        ], 422);
    }

    $username      = trim($request->username);
    $memberProfile = null;

    if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
        // Email → admins (and pending members who have no membership ID yet)
        $user = User::where('email', $username)->first();
    } else {
        // Anything else → membership ID
        $memberProfile = \App\Models\MemberProfile::where('membership_id', $username)->first();
        $user = $memberProfile ? User::find($memberProfile->user_id) : null;
    }

    if (!$user) {
        return ApiResponse::error('Invalid username or password', 401);
    }

    // Members: password = phone number. Admins: hashed password.
    $valid = $user->role === 'member'
        ? hash_equals((string) $user->phone, (string) $request->password)
        : Hash::check($request->password, $user->password);

    if (!$valid) {
        return ApiResponse::error('Invalid username or password', 401);
    }

    if (!$user->is_active) {
        return ApiResponse::error('Your account is disabled. Contact admin.', 403);
    }

    // ══════════ member ══════════
    if ($user->role === 'member') {
        $profile = $memberProfile ?: \App\Models\MemberProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return ApiResponse::error('Member profile not found.', 404);
        }

        if (in_array($profile->status, ['PENDING', 'REJECTED'])) {
            return response()->json([
                'status'      => 0,
                'status_code' => 403,
                'message'     => $profile->status === 'PENDING'
                                    ? 'Your registration is under review by the unit admin.'
                                    : 'Your registration was rejected. Please contact your unit admin.',
                'data'        => [
                    'approval_status' => $profile->status === 'PENDING' ? 'under_review' : 'rejected',
                ]
            ], 403);
        }

        if (!in_array($profile->status, ['APPROVED', 'ACTIVE'])) {
            return ApiResponse::error('Your membership is not active. Contact your unit admin.', 403);
        }

        $token = $this->issueToken($user);
        $profile->load(['unit', 'district', 'state']);

        return $this->loginSuccess([
            'user_id'         => (string) $user->id,
            'name'            => $user->name,
            'email'           => $user->email ?? '',
            'phone'           => $user->phone,
            'role'            => $user->role,
            'token'           => $token,
            'membership_id'   => $profile->membership_id,
            'joined_date'     => (string) $profile->joined_date,
            'dob'             => (string) $profile->dob,
            'organization'    => $profile->organization,
            'photo'           => $profile->photo ? asset('storage/' . $profile->photo) : null,
            'unit_id'         => (string) $profile->unit_id,
            'unit_name'       => $profile->unit->name ?? null,
            'state_id'        => (string) $profile->state_id,
            'state_name'      => $profile->state->name ?? null,
            'approval_status' => 'approved',
            'payment_status'  => 'not_required',
        ]);
    }

    // ══════════ super_admin ══════════
    if ($user->role === 'super_admin') {
        $token = $this->issueToken($user);

        return $this->loginSuccess([
            'user_id' => (string) $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'phone'   => $user->phone,
            'role'    => $user->role,
            'token'   => $token,
        ]);
    }

    // ══════════ district_admin ══════════
    if ($user->role === 'district_admin') {
        $districtAdmin = \App\Models\DistrictAdmin::with(['district.state'])
                            ->where('user_id', $user->id)->first();

        if (!$districtAdmin) {
            return ApiResponse::error('District Admin profile not found. Contact admin.', 404);
        }

        $token = $this->issueToken($user);

        return $this->loginSuccess([
            'user_id'       => (string) $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'role'          => $user->role,
            'token'         => $token,
            'district_id'   => (string) $districtAdmin->district_id,
            'district_name' => $districtAdmin->district->name ?? null,
            'state_id'      => (string) ($districtAdmin->district->state_id ?? ''),
            'state_name'    => $districtAdmin->district->state->name ?? null,
        ]);
    }

    // ══════════ unit_admin ══════════
    if ($user->role === 'unit_admin') {
        $unitAdmin = \App\Models\UnitAdmin::with(['unit.district.state'])
                        ->where('user_id', $user->id)->first();

        if (!$unitAdmin) {
            return ApiResponse::error('Unit Admin profile not found. Contact admin.', 404);
        }

        $token = $this->issueToken($user);

        return $this->loginSuccess([
            'user_id'       => (string) $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'phone'         => $user->phone,
            'role'          => $user->role,
            'token'         => $token,
            'unit_id'       => (string) $unitAdmin->unit_id,
            'unit_name'     => $unitAdmin->unit->name ?? null,
            'state_id'      => (string) ($unitAdmin->unit->district->state_id ?? \App\Models\State::fixed()?->id ?? '1'),
            'state_name'    => $unitAdmin->unit->district->state->name ?? \App\Models\State::fixed()?->name ?? 'Kerala',
        ]);
    }

    return ApiResponse::error('Unauthorized role.', 403);
}

// ── helpers used only by loginV2 ──
private function issueToken(User $user): string
{
    $user->tokens()->delete();
    $token            = $user->createToken('auth_token')->plainTextToken;
    $user->auth_token = $token;
    $user->save();

    return $token;
}

private function loginSuccess(array $data)
{
    return response()->json([
        'status'      => 1,
        'status_code' => 200,
        'message'     => 'Login successful',
        'data'        => $data,
    ], 200);
}

    // ─────────────────────────────────
    // POST /api/logout
    // ─────────────────────────────────
    public function logout(Request $request)
    {
        $user = $request->user();

        $user->auth_token = null;
        $user->save();

        $user->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    // ─────────────────────────────────
    // GET /api/me
    // ─────────────────────────────────
    public function me(Request $request)
    {
        return ApiResponse::success(
            $request->user(),
            'Profile fetched successfully'
        );
    }

    // ─────────────────────────────────
    // POST /api/change-password
    // ─────────────────────────────────
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Current password is incorrect.', 400);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return ApiResponse::error('New password must be different from current password.', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return ApiResponse::success(null, 'Password changed successfully.');
    }

    // ─────────────────────────────────
    // POST /api/forgot-password
    // ─────────────────────────────────
    public function forgotPassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email' => 'required|email',
        ])->stopOnFirstFailure();

        if ($validator->fails()) {
            return response()->json([
                'status'      => '0',
                'status_code' => '422',
                'message'     => $validator->errors()->first(),
                'data'        => (object) [],
            ], 422);
        }

        $user = User::where('email', $request->email)
                    ->whereIn('role', ['member', 'unit_admin', 'district_admin'])
                    ->first();

        if (!$user) {
            return ApiResponse::error('Email is not in the list.', 404);
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $otp,
            'created_at' => Carbon::now(),
        ]);

        try {
            Mail::raw(
                "Hi {$user->name},\n\nYour OTP for password reset is: {$otp}\n\nThis OTP expires in 10 minutes.\n\nIf you did not request this, please ignore.",
                function ($message) use ($request) {
                    $message->to($request->email)->subject('ECA — Password Reset OTP');
                }
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('OTP email failed: ' . $e->getMessage());
        }

        return ApiResponse::success(null, 'OTP sent to your email.');
    }

    // ─────────────────────────────────
    // POST /api/reset-password
    // ─────────────────────────────────
    public function resetPassword(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'email'    => 'required|email',
            'otp'      => 'required|digits:6',
            'password' => 'required|min:8',
        ])->stopOnFirstFailure();

        if ($validator->fails()) {
            return response()->json([
                'status'      => '0',
                'status_code' => '422',
                'message'     => $validator->errors()->first(),
                'data'        => (object) [],
            ], 422);
        }

        $record = DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->where('token', $request->otp)
                    ->first();

        if (!$record) {
            return ApiResponse::error('Invalid OTP.', 400);
        }

        if (Carbon::parse($record->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return ApiResponse::error('OTP has expired. Please request a new one.', 400);
        }

        $user = User::where('email', $request->email)
                    ->whereIn('role', ['member', 'unit_admin', 'district_admin'])
                    ->first();

        if (!$user) {
            return ApiResponse::error('User not found.', 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Revoke all tokens — force re-login
        $user->tokens()->delete();

        return ApiResponse::success(null, 'Password reset successfully. Please login with your new password.');
    }
}