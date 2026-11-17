<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Renewal;
use App\Models\MemberProfile;
use App\Models\UnitAdmin;
use App\Models\DistrictAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RenewalController extends Controller
{
    // ─────────────────────────────────
    // LIST ALL RENEWAL RECORDS
    // ─────────────────────────────────
    public function index(Request $request)
    {
        $query = Renewal::with(['memberProfile.user', 'createdBy']);

        if ($request->filled('member_id')) {
            $query->where('member_profile_id', $request->member_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $renewals = $query->latest()->paginate(20)->withQueryString();

        // ── Who created each record? (same lookup as Other Fees / Schemes / Expenses) ──
        $creatorIds = $renewals->pluck('created_by')->filter()->unique();

        $unitAdmins = UnitAdmin::with('unit')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        $districtAdmins = DistrictAdmin::with('district')
            ->whereIn('user_id', $creatorIds)->get()->keyBy('user_id');

        return view('admin.renewals-list', [
            'renewals'       => $renewals,
            'members'        => MemberProfile::with('user')
                ->where('status', 'APPROVED')
                ->get(),
            'unitAdmins'     => $unitAdmins,
            'districtAdmins' => $districtAdmins,
        ]);
    }

    // ─────────────────────────────────
    // STORE
    // ─────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'member_profile_ids'   => 'required|array|min:1',
            'member_profile_ids.*' => 'exists:member_profiles,id',
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
        $request->validate([
            'id'                 => 'required|exists:renewals,id',
            'member_profile_id'  => 'required|exists:member_profiles,id',
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

        $renewal = Renewal::findOrFail($request->id);

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
        $request->validate(['id' => 'required|exists:renewals,id']);
        Renewal::findOrFail($request->id)->delete();
        return back()->with('success', 'Renewal record deleted successfully!');
    }

    // ─────────────────────────────────
    // DETAIL (AJAX for edit modal)
    // ─────────────────────────────────
    public function detail($id)
    {
        $renewal = Renewal::find($id);
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