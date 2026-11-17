<?php

namespace App\Http\Controllers\UnitAdmin;

use App\Http\Controllers\Controller;
use App\Models\Renewal;
use App\Models\MemberProfile;
use App\Models\UnitAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RenewalController extends Controller
{
    private function myUnitId(): int
    {
        return UnitAdmin::where('user_id', Auth::id())->value('unit_id');
    }

    // ─────────────────────────────────
    // LIST ALL RENEWAL RECORDS (this unit only)
    // ─────────────────────────────────
    public function index(Request $request)
    {
        $unitId = $this->myUnitId();

        $query = Renewal::with('memberProfile.user')
            ->whereHas('memberProfile', fn($q) => $q->where('unit_id', $unitId));

        if ($request->filled('member_id')) {
            $query->where('member_profile_id', $request->member_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $renewals = $query->latest()->paginate(20)->withQueryString();

        return view('unit-admin.renewals-manage', [
            'renewals' => $renewals,
            'members'  => MemberProfile::with('user')
                ->where('unit_id', $unitId)
                ->where('status', 'APPROVED')
                ->get(),
        ]);
    }

    // ─────────────────────────────────
    // STORE (multiple members)
    // ─────────────────────────────────
    public function store(Request $request)
    {
        $unitId = $this->myUnitId();

        $request->validate([
            'member_profile_ids'   => 'required|array|min:1',
            'member_profile_ids.*' => [
                'exists:member_profiles,id',
                function ($attribute, $value, $fail) use ($unitId) {
                    $belongsToUnit = MemberProfile::where('id', $value)
                        ->where('unit_id', $unitId)
                        ->exists();
                    if (!$belongsToUnit) {
                        $fail('One or more selected members do not belong to your unit.');
                    }
                },
            ],
            'title'                => 'required|string|max:150',
            'type'                 => 'nullable|string|max:50',
            'valid_from'           => 'required|date',
            'valid_until'          => 'required|date|after:valid_from',
            'notes'                => 'nullable|string|max:500',
        ], [
            'member_profile_ids.required' => 'Select at least one member.',
            'member_profile_ids.min'      => 'Select at least one member.',
            'title.max'                   => 'Title cannot exceed 150 characters.',
            'valid_until.after'           => 'Valid until date must be after the valid from date.',
            'notes.max'                   => 'Notes cannot exceed 500 characters.',
        ]);

        $memberIds = array_unique($request->member_profile_ids);

        // All-or-nothing: either every member gets the record or none do
        DB::transaction(function () use ($request, $memberIds) {
            foreach ($memberIds as $memberId) {
                Renewal::create([
                    'member_profile_id' => $memberId,
                    'title'             => $request->title,
                    'type'              => $request->type,
                    'valid_from'        => $request->valid_from,
                    'valid_until'       => $request->valid_until,
                    'notes'             => $request->notes,
                    'created_by'        => auth()->id(),
                ]);
            }
        });

        return back()->with('success', count($memberIds) . ' renewal record(s) created successfully!');
    }

    // ─────────────────────────────────
    // UPDATE
    // ─────────────────────────────────
    public function update(Request $request)
    {
        $unitId = $this->myUnitId();

        $request->validate([
            'id'                 => 'required|exists:renewals,id',
            'member_profile_id'  => [
                'required',
                'exists:member_profiles,id',
                function ($attribute, $value, $fail) use ($unitId) {
                    $belongsToUnit = MemberProfile::where('id', $value)
                        ->where('unit_id', $unitId)
                        ->exists();
                    if (!$belongsToUnit) {
                        $fail('Selected member does not belong to your unit.');
                    }
                },
            ],
            'title'              => 'required|string|max:150',
            'type'               => 'nullable|string|max:50',
            'valid_from'         => 'required|date',
            'valid_until'        => 'required|date|after:valid_from',
            'notes'              => 'nullable|string|max:500',
        ], [
            'title.max'         => 'Title cannot exceed 150 characters.',
            'valid_until.after' => 'Valid until date must be after the valid from date.',
            'notes.max'         => 'Notes cannot exceed 500 characters.',
        ]);

        // Only allow editing renewals that belong to a member in this unit
        $renewal = Renewal::whereHas('memberProfile', fn($q) => $q->where('unit_id', $unitId))
            ->findOrFail($request->id);

        $renewal->update([
            'member_profile_id' => $request->member_profile_id,
            'title'             => $request->title,
            'type'              => $request->type,
            'valid_from'        => $request->valid_from,
            'valid_until'       => $request->valid_until,
            'renewal_date'      => null, // reset so the saving() hook recomputes it
            'notes'             => $request->notes,
        ]);

        return back()->with('success', 'Renewal record updated successfully!');
    }

    // ─────────────────────────────────
    // DELETE
    // ─────────────────────────────────
    public function destroy(Request $request)
    {
        $unitId = $this->myUnitId();

        $request->validate(['id' => 'required|exists:renewals,id']);

        $renewal = Renewal::whereHas('memberProfile', fn($q) => $q->where('unit_id', $unitId))
            ->findOrFail($request->id);

        $renewal->delete();

        return back()->with('success', 'Renewal record deleted successfully!');
    }

    // ─────────────────────────────────
    // DETAIL (AJAX for edit modal)
    // ─────────────────────────────────
    public function detail($id)
    {
        $unitId = $this->myUnitId();

        $renewal = Renewal::whereHas('memberProfile', fn($q) => $q->where('unit_id', $unitId))
            ->find($id);

        if (!$renewal) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'id'                => $renewal->id,
            'member_profile_id' => $renewal->member_profile_id,
            'title'             => $renewal->title,
            'type'              => $renewal->type,
            'valid_from_raw'    => $renewal->valid_from->format('Y-m-d'),
            'valid_until_raw'   => $renewal->valid_until->format('Y-m-d'),
            'notes'             => $renewal->notes,
            'status'            => $renewal->status,
        ]);
    }
}