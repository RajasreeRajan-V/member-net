<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\UnitAdmin;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitAdminController extends Controller
{
    // ─────────────────────────────────
    // GET ALL UNIT ADMINS
    // GET /api/admin/unit-admins
    // ─────────────────────────────────
    public function getUnitAdmins()
    {
        $unitAdmins = UnitAdmin::with([
            'user',
            'unit',
            'unit.district',
            'unit.district.state'
        ])->get();

        $data = $unitAdmins->map(function ($ua) {
            return [
                'id'            => (string) $ua->id,
                'user_id'       => (string) $ua->user_id,
                'name'          => $ua->user->name,
                'email'         => $ua->user->email,
                'phone'         => $ua->user->phone,
                'is_active'     => (string) $ua->user->is_active,
                'unit_id'       => (string) $ua->unit->id,
                'unit_name'     => $ua->unit->name,
                'district_id'   => (string) $ua->unit->district->id,
                'district_name' => $ua->unit->district->name,
                'state_id'      => (string) $ua->unit->district->state->id,
                'state_name'    => $ua->unit->district->state->name,
                'created_at'    => (string) $ua->created_at,
            ];
        });

        return ApiResponse::success(
            $data,
            'Unit admins fetched successfully'
        );
    }

    // ─────────────────────────────────
    // CREATE UNIT ADMIN
    // POST /api/admin/unit-admins
    // ─────────────────────────────────
    public function createUnitAdmin(Request $request)
    {
        $request->validate([
            'name'    => 'required|string',
            'email'   => 'required|email|unique:users,email',
            'phone'   => 'required|digits:10|unique:users,phone',
            'unit_id' => 'required|exists:units,id',
        ]);

        // Check unit already has admin
        $exists = UnitAdmin::where(
            'unit_id', $request->unit_id
        )->first();

        if ($exists) {
            return ApiResponse::error(
                'This unit already has an admin assigned.',
                400
            );
        }

        // Auto generate password
        // Format: FirstName@randomNumber
        $firstName = explode(' ', $request->name)[0];
        $password  = $firstName . '@' . rand(1000, 9999);

        // Create user account
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => $password,
            'role'      => 'unit_admin',
            'is_active' => true,
        ]);

        // Create unit admin record
        $unitAdmin = UnitAdmin::create([
            'user_id'     => $user->id,
            'unit_id'     => $request->unit_id,
            'assigned_by' => auth()->id(),
        ]);

        // Load unit details
        $unit = Unit::with('district.state')
                    ->find($request->unit_id);

        return ApiResponse::success(
            [
                'id'            => (string) $unitAdmin->id,
                'user_id'       => (string) $user->id,
                'name'          => $user->name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'password'      => $password,
                'unit_id'       => (string) $unit->id,
                'unit_name'     => $unit->name,
                'district_name' => $unit->district->name,
                'state_name'    => $unit->district->state->name,
            ],
            'Unit Admin created successfully',
            201
        );
    }

    // ─────────────────────────────────
    // UPDATE UNIT ADMIN
    // POST /api/admin/unit-admins/update
    // ─────────────────────────────────
    public function updateUnitAdmin(Request $request)
    {
        $request->validate([
            'id'    => 'required|exists:unit_admins,id',
            'name'  => 'sometimes|string',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')
                    ->ignore(
                        UnitAdmin::find($request->id)?->user_id
                    )
            ],
            'phone' => [
                'sometimes',
                'digits:10',
                Rule::unique('users', 'phone')
                    ->ignore(
                        UnitAdmin::find($request->id)?->user_id
                    )
            ],
        ]);

        $unitAdmin = UnitAdmin::find($request->id);
        $user      = User::find($unitAdmin->user_id);

        // Update only provided fields
        if ($request->filled('name'))
            $user->name  = $request->name;
        if ($request->filled('email'))
            $user->email = $request->email;
        if ($request->filled('phone'))
            $user->phone = $request->phone;

        $user->save();

        return ApiResponse::success(
            [
                'id'    => (string) $unitAdmin->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'Unit Admin updated successfully'
        );
    }

    // ─────────────────────────────────
    // DELETE UNIT ADMIN
    // DELETE /api/admin/unit-admins
    // ─────────────────────────────────
    public function deleteUnitAdmin(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:unit_admins,id'
        ]);

        $unitAdmin = UnitAdmin::find($request->id);
        $user      = User::find($unitAdmin->user_id);

        // Delete unit admin record first
        $unitAdmin->delete();

        // Then delete user account
        $user->delete();

        return ApiResponse::success(
            null,
            'Unit Admin deleted successfully'
        );
    }

    // ─────────────────────────────────
    // RESET PASSWORD
    // POST /api/admin/unit-admins/reset-password
    // ─────────────────────────────────
    public function resetPassword(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:unit_admins,id'
        ]);

        $unitAdmin = UnitAdmin::find($request->id);
        $user      = User::find($unitAdmin->user_id);

        // Generate new password
        $firstName   = explode(' ', $user->name)[0];
        $newPassword = $firstName . '@' . rand(1000, 9999);

        // Update password
        $user->password = $newPassword;
        $user->save();

        return ApiResponse::success(
            [
                'id'           => (string) $unitAdmin->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'new_password' => $newPassword,
            ],
            'Password reset successfully'
        );
    }

    // ─────────────────────────────────
    // TOGGLE ACTIVE STATUS
    // PUT /api/admin/unit-admins/toggle-status
    // ─────────────────────────────────
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:unit_admins,id'
        ]);

        $unitAdmin       = UnitAdmin::find($request->id);
        $user            = User::find($unitAdmin->user_id);
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active
            ? 'activated'
            : 'deactivated';

        return ApiResponse::success(
            [
                'id'        => (string) $unitAdmin->id,
                'name'      => $user->name,
                'is_active' => (string) (int) $user->is_active,
            ],
            'Unit Admin ' . $status . ' successfully'
        );
    }

    // ─────────────────────────────────
    // GET SINGLE UNIT ADMIN
    // POST /api/admin/unit-admins/detail
    // ─────────────────────────────────
    public function getUnitAdmin(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:unit_admins,id'
        ]);

        $ua = UnitAdmin::with([
            'user',
            'unit',
            'unit.district',
            'unit.district.state'
        ])->find($request->id);

        return ApiResponse::success(
            [
                'id'            => (string) $ua->id,
                'user_id'       => (string) $ua->user_id,
                'name'          => $ua->user->name,
                'email'         => $ua->user->email,
                'phone'         => $ua->user->phone,
                'is_active'     => (string) $ua->user->is_active,
                'unit_id'       => (string) $ua->unit->id,
                'unit_name'     => $ua->unit->name,
                'district_id'   => (string) $ua->unit->district->id,
                'district_name' => $ua->unit->district->name,
                'state_id'      => (string) $ua->unit->district->state->id,
                'state_name'    => $ua->unit->district->state->name,
                'created_at'    => (string) $ua->created_at,
            ],
            'Unit Admin fetched successfully'
        );
    }
}