<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use App\Models\Renewal;
use App\Models\MemberProfile;
use App\Models\DistrictAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DistrictRenewalController extends Controller
{
    private function myDistrictId(): int
    {
        return DistrictAdmin::where('user_id', Auth::id())->value('district_id');
    }

    private function memberRule(int $districtId): array
    {
        return [
            'required',
            'exists:member_profiles,id',
            function ($attribute, $value, $fail) use ($districtId) {
                $ok = MemberProfile::where('id', $value)->where('district_id', $districtId)->exists();
                if (!$ok) $fail('Selected member does not belong to your district.');
            },
        ];
    }

    // Shared fields (everything except the member field)
    private function baseRules(): array
    {
        return [
            'title'       => 'required|string|max:150',
            'type'        => 'nullable|string|max:50',
            'valid_from'  => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'notes'       => 'nullable|string|max:500',
        ];
    }

    // Single member (used by update)
    private function rules(int $districtId): array
    {
        return ['member_profile_id' => $this->memberRule($districtId)] + $this->baseRules();
    }

    // Multiple members (used by store)
    private function storeRules(int $districtId): array
    {
        return [
            'member_profile_ids'   => 'required|array|min:1',
            'member_profile_ids.*' => [
                'exists:member_profiles,id',
                function ($attribute, $value, $fail) use ($districtId) {
                    $ok = MemberProfile::where('id', $value)->where('district_id', $districtId)->exists();
                    if (!$ok) $fail('One or more selected members do not belong to your district.');
                },
            ],
        ] + $this->baseRules();
    }

    private function messages(): array
    {
        return [
            'member_profile_ids.required' => 'Select at least one member.',
            'member_profile_ids.min'      => 'Select at least one member.',
            'title.max'                   => 'Title cannot exceed 150 characters.',
            'valid_until.after'           => 'Valid until date must be after the valid from date.',
            'notes.max'                   => 'Notes cannot exceed 500 characters.',
        ];
    }

    public function index(Request $request)
    {
        $districtId = $this->myDistrictId();

        $query = Renewal::with(['memberProfile.user', 'memberProfile.unit'])
            ->whereHas('memberProfile', fn($q) => $q->where('district_id', $districtId));

        if ($request->filled('member_id')) $query->where('member_profile_id', $request->member_id);
        if ($request->filled('type'))      $query->where('type', $request->type);

        return view('district-admin.renewals-manage', [
            'renewals' => $query->latest()->paginate(20)->withQueryString(),
            'members'  => MemberProfile::with(['user', 'unit'])
                ->where('district_id', $districtId)
                ->where('status', 'APPROVED')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->storeRules($this->myDistrictId()), $this->messages());

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
                    'created_by'        => Auth::id(),
                ]);
            }
        });

        return back()->with('success', count($memberIds) . ' renewal record(s) created successfully!');
    }

    public function update(Request $request)
    {
        $districtId = $this->myDistrictId();

        $request->validate(['id' => 'required|exists:renewals,id'] + $this->rules($districtId), $this->messages());

        $renewal = Renewal::whereHas('memberProfile', fn($q) => $q->where('district_id', $districtId))
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

    public function destroy(Request $request)
    {
        $districtId = $this->myDistrictId();

        $request->validate(['id' => 'required|exists:renewals,id']);

        Renewal::whereHas('memberProfile', fn($q) => $q->where('district_id', $districtId))
            ->findOrFail($request->id)
            ->delete();

        return back()->with('success', 'Renewal record deleted successfully!');
    }

    public function detail($id)
    {
        $renewal = Renewal::whereHas('memberProfile', fn($q) => $q->where('district_id', $this->myDistrictId()))
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