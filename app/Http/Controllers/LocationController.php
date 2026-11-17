<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\District;
use App\Models\State;
use App\Models\Unit;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    // ─────────────────────────────────
    // STATES
    // ─────────────────────────────────

public function getStates()
{
    $fixed = State::fixed();

    $states = State::where('is_active', true)
        ->when($fixed, fn($q) => $q->where('id', $fixed->id))
        ->whereHas('districts', function ($query) {
            $query->where('is_active', true)
                ->whereHas('units', function ($q) {
                    $q->where('is_active', true)
                      ->whereHas('unitAdmin');
                });
        })
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

    return ApiResponse::success($states, 'States fetched successfully');
}

    // POST /api/admin/states
    public function createState(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:states'
        ]);

        $state = State::create([
            'name'      => $request->name,
            'is_active' => true
        ]);

        return ApiResponse::success(
    [
        'id'   => $state->id,
        'name' => $state->name
    ],
    'State created successfully',
    201
);
    }

    // PUT /api/admin/states
    public function updateState(Request $request)
    {
        $request->validate([
            'id'   => 'required|exists:states,id',
            'name' => [
                'required',
                'string',
                Rule::unique('states')->ignore($request->id)
            ]
        ]);

        $state = State::find($request->id);

        $state->update([
            'name' => $request->name
        ]);

        return ApiResponse::success(
    [
        'id'   => $state->id,
        'name' => $state->name
    ],
    'State updated successfully'
);
    }

    // DELETE /api/admin/states
    public function deleteState(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:states,id'
        ]);

        $state = State::find($request->id);

        if ($state->districts()->count() > 0) {
            return ApiResponse::error(
                'Cannot delete state with existing districts.
                 Delete districts first.',
                400
            );
        }

        $state->delete();

        return ApiResponse::success(
            null,
            'State deleted successfully'
        );
    }

public function getDistricts(Request $request)
{
    // Kerala is fixed: ignore whatever state_id the app sends
    if ($fixed = State::fixed()) {
        $request->merge(['state_id' => $fixed->id]);
    }

    $request->validate([
        'state_id' => 'required|exists:states,id'
    ]);

    $districts = District::where('state_id', $request->state_id)
        ->where('is_active', true)
        ->whereHas('units', function ($query) {
            $query->where('is_active', true)
                  ->whereHas('unitAdmin');
        })
        ->select('id', 'state_id', 'name')
        ->orderBy('name')
        ->get()
        ->map(function ($district) {
            return [
                'id'       => $district->id,
                'state_id' => (int) $district->state_id,
                'name'     => $district->name,
            ];
        });

    return ApiResponse::success(
        $districts,
        'Districts fetched successfully'
    );
}

    // POST /api/admin/districts
    public function createDistrict(Request $request)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
            'name'     => [
                'required',
                'string',
                Rule::unique('districts')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'state_id',
                            $request->state_id
                        );
                    })
            ]
        ]);

        $district = District::create([
            'state_id'  => $request->state_id,
            'name'      => $request->name,
            'is_active' => true
        ]);

        return ApiResponse::success(
    [
        'id'       => $district->id,
        'state_id' => $district->state_id,
        'name'     => $district->name
    ],
    'District created successfully',
    201
);
    }

    // PUT /api/admin/districts
    public function updateDistrict(Request $request)
    {
        $request->validate([
            'id'   => 'required|exists:districts,id',
            'name' => [
                'required',
                'string',
                Rule::unique('districts')
                    ->where(function ($query) use ($request) {
                        $district = District::find($request->id);
                        return $query->where(
                            'state_id',
                            $district->state_id
                        );
                    })
                    ->ignore($request->id)
            ]
        ]);

        $district = District::find($request->id);

        $district->update([
            'name' => $request->name
        ]);

        return ApiResponse::success(
    [
        'id'       => $district->id,
        'state_id' => $district->state_id,
        'name'     => $district->name
    ],
    'District updated successfully'
);
    }

    // DELETE /api/admin/districts
    public function deleteDistrict(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:districts,id'
        ]);

        $district = District::find($request->id);

        if ($district->units()->count() > 0) {
            return ApiResponse::error(
                'Cannot delete district with existing units.
                 Delete units first.',
                400
            );
        }

        $district->delete();

        return ApiResponse::success(
            null,
            'District deleted successfully'
        );
    }

    // ─────────────────────────────────
    // UNITS
    // ─────────────────────────────────

public function getUnits(Request $request)
{
    // Kerala is fixed: the district must belong to Kerala
    $districtRule = Rule::exists('districts', 'id');
    if ($fixed = State::fixed()) {
        $districtRule = $districtRule->where('state_id', $fixed->id);
    }

    $request->validate([
        'district_id' => ['required', $districtRule]
    ]);

    $units = Unit::where('district_id', $request->district_id)
        ->where('is_active', true)
        ->whereHas('unitAdmin')
        ->select('id', 'district_id', 'name')
        ->orderBy('name')
        ->get()
        ->map(function ($unit) {
            return [
                'id'          => $unit->id,
                'district_id' => (int) $unit->district_id,
                'name'        => $unit->name,
            ];
        });

    return ApiResponse::success(
        $units,
        'Units fetched successfully'
    );
}

    // POST /api/admin/units
    public function createUnit(Request $request)
    {
        $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name'        => [
                'required',
                'string',
                Rule::unique('units')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'district_id',
                            $request->district_id
                        );
                    })
            ]
        ]);

        $unit = Unit::create([
            'district_id' => $request->district_id,
            'name'        => $request->name,
            'is_active'   => true
        ]);

        return ApiResponse::success(
    [
        'id'          => $unit->id,
        'district_id' => $unit->district_id,
        'name'        => $unit->name
    ],
    'Unit created successfully',
    201
);
    }

    // PUT /api/admin/units
    public function updateUnit(Request $request)
    {
        $request->validate([
            'id'   => 'required|exists:units,id',
            'name' => [
                'required',
                'string',
                Rule::unique('units')
                    ->where(function ($query) use ($request) {
                        $unit = Unit::find($request->id);
                        return $query->where(
                            'district_id',
                            $unit->district_id
                        );
                    })
                    ->ignore($request->id)
            ]
        ]);

        $unit = Unit::find($request->id);

        $unit->update([
            'name' => $request->name
        ]);

        return ApiResponse::success(
    [
        'id'          => $unit->id,
        'district_id' => $unit->district_id,
        'name'        => $unit->name
    ],
    'Unit updated successfully'
);
    }

    // DELETE /api/admin/units
    public function deleteUnit(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:units,id'
        ]);

        $unit = Unit::find($request->id);

        $unit->delete();

        return ApiResponse::success(
            null,
            'Unit deleted successfully'
        );
    }
    


// ─────────────────────────────────
// STATS (state is fixed: Kerala)
// ─────────────────────────────────

// GET /api/admin/stats/districts   (no body)
public function getDistrictStats()
{
    $state = State::fixed();

    if (!$state) {
        return ApiResponse::error('Fixed state is not configured.', 400);
    }

    // All active units of Kerala with their approved-member count (1 query)
    $unitsByDistrict = Unit::where('is_active', true)
        ->whereHas('district', fn ($q) =>
            $q->where('state_id', $state->id)->where('is_active', true)
        )
        ->withCount([
            'members as members_count' => fn ($q) => $q->where('status', 'APPROVED')
        ])
        ->get(['id', 'district_id'])
        ->groupBy('district_id');

    // ALL active districts, even those with 0 units / 0 members
    $districts = District::with(['districtAdmin.user'])
        ->where('state_id', $state->id)
        ->where('is_active', true)
        ->orderBy('name')
        ->get()
        ->map(function ($district) use ($state, $unitsByDistrict) {
            $units = $unitsByDistrict->get($district->id, collect());

            $row = [
                'id'            => $district->id,
                'name'          => $district->name,
                'units_count'   => $units->count(),
                'members_count' => (int) $units->sum('members_count'),
            ];

            // Add district_admin ONLY when one is assigned
            $da = $district->districtAdmin;
            if ($da && $da->user) {
                $row['district_admin'] = [
                    'id'            => (string) $da->id,
                    'name'          => $da->user->name,
                    'phone'         => $da->user->phone,
                    'email'         => $da->user->email,
                    'is_active'     => (bool) $da->user->is_active,
                    'district_name' => $district->name,
                    'state_name'    => $state->name,
                ];
            }

            return $row;
        })
        ->values();

    return ApiResponse::success([
        'state_id'        => $state->id,
        'state_name'      => $state->name,
        'total_districts' => $districts->count(),
        'total_units'     => $districts->sum('units_count'),
        'total_members'   => $districts->sum('members_count'),
        'districts'       => $districts,
    ], 'District stats fetched successfully');
}

// POST /api/admin/stats/units   Body: { "district_id": 1 }
public function getUnitStats(Request $request)
{
    // District must belong to Kerala (fixed state)
    $districtRule = Rule::exists('districts', 'id');
    if ($fixed = State::fixed()) {
        $districtRule = $districtRule->where('state_id', $fixed->id);
    }

    $request->validate([
        'district_id' => ['required', $districtRule]
    ]);

    $district = District::with('state')->find($request->district_id);

    // ALL active units of the district, even those with 0 members
    $units = Unit::where('district_id', $district->id)
        ->where('is_active', true)
        ->withCount([
            'members as members_count' => fn ($q) => $q->where('status', 'APPROVED')
        ])
        ->orderBy('name')
        ->get()
        ->map(fn ($unit) => [
            'id'            => $unit->id,
            'name'          => $unit->name,
            'members_count' => (int) $unit->members_count,
        ])
        ->values();

    return ApiResponse::success([
        'district_id'   => $district->id,
        'district_name' => $district->name,
        'state_name'    => $district->state->name,
        'total_units'   => $units->count(),
        'total_members' => $units->sum('members_count'),
        'units'         => $units,
    ], 'Unit stats fetched successfully');
}

// ─────────────────────────────────
// ADMIN: LOCATION DROPDOWNS (Unit Admin registration)
//   States     → ALL active states
//   Districts  → ALL active districts of the selected state
//   Units      → ONLY active units that have NO unit admin yet
// ─────────────────────────────────

// GET /api/admin/locations/states
public function getUnassignedStates(Request $request)
{
    $states = State::where('is_active', true)
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

    return ApiResponse::success($states, 'States fetched successfully');
}

// POST /api/admin/locations/districts
public function getUnassignedDistricts()
{
    // $request->validate(['state_id' => 'required|exists:states,id']);
    // $districts = District::where('state_id', $request->state_id)
    //     ->where('is_active', true)
    //     ->select('id', 'state_id', 'name')
    //     ->orderBy('name')
    //     ->get()
    //     ->map(fn ($d) => [
    //         'id'       => $d->id,
    //         'state_id' => (int) $d->state_id,
    //         'name'     => $d->name,
    //     ]);
    
     $districts = District::where('is_active', true)
        ->select('id', 'state_id', 'name')
        ->orderBy('name')
        ->get()
        ->map(fn ($d) => [
            'id'       => $d->id,
            'state_id' => (int) $d->state_id,
            'name'     => $d->name,
        ]);

    return ApiResponse::success($districts, 'Districts fetched successfully');
}

// POST /api/admin/locations/units
public function getUnassignedUnits(Request $request)
{
    $request->validate(['district_id' => 'required|exists:districts,id']);

    $units = Unit::where('district_id', $request->district_id)
        ->where('is_active', true)
        ->whereDoesntHave('unitAdmin') // ← only units without a unit admin
        ->select('id', 'district_id', 'name')
        ->orderBy('name')
        ->get()
        ->map(fn ($u) => [
            'id'          => $u->id,
            'district_id' => (int) $u->district_id,
            'name'        => $u->name,
        ]);

    return ApiResponse::success($units, 'Units fetched successfully');
}

// ─────────────────────────────────
// DISTRICT ADMIN REGISTRATION DROPDOWNS
//   States     → ALL active states
//   Districts  → ONLY active districts that have NO district admin yet
// ─────────────────────────────────



// ─────────────────────────────────
// DISTRICT ADMIN REGISTRATION DROPDOWN
//   State is fixed (Kerala), so no input is needed.
//   Returns ONLY active districts that have NO district admin yet.
// ─────────────────────────────────

// GET /api/admin/district-admins/locations/districts
public function getDistrictsForDistrictAdmin()
{
    $fixed = State::fixed();

    if (!$fixed) {
        return ApiResponse::error('Fixed state is not configured.', 400);
    }

    $districts = District::where('state_id', $fixed->id)
        ->where('is_active', true)
        ->whereDoesntHave('districtAdmin')   // only districts without a district admin
        ->select('id', 'state_id', 'name')
        ->orderBy('name')
        ->get()
        ->map(fn ($d) => [
            'id'       => $d->id,
            'state_id' => (int) $d->state_id,
            'name'     => $d->name,
        ]);

    return ApiResponse::success($districts, 'Districts fetched successfully');
}
}