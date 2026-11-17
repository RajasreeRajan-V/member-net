<?php
namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\MemberProfile;
use App\Models\Committee;
use Illuminate\Http\Request;

class CommitteeController extends Controller
{
    private function profileOrFail()
    {
        $profile = MemberProfile::where('user_id', auth()->id())->first();
        if (!$profile) abort(response()->json([
            'status' => '0', 'status_code' => '404',
            'message' => 'Member profile not found.', 'data' => (object)[],
        ], 404));
        return $profile;
    }

    // ─────────────────────────────────
    // GET /api/committees/my-committees?page=1&limit=20
    // ─────────────────────────────────
    public function myCommittees(Request $request)
    {
        $profile = $this->profileOrFail();

        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        // Only committees this member was explicitly assigned to (via committee_members)
        $paginated = Committee::where('is_active', true)
            ->whereHas('members', fn ($q) => $q->where('member_profiles.id', $profile->id))
            ->with(['members' => fn ($q) => $q->where('member_profiles.id', $profile->id)])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(function ($committee) {
            $role = $committee->members->first()?->pivot->role ?? '';

            return [
                'id'          => 'com' . $committee->id,
                'name'        => $committee->name,
                'role'        => $role,
                'start_date'  => $committee->start_date->format('Y-m-d'),
                'end_date'    => $committee->end_date?->format('Y-m-d') ?? '', // optional
                'description' => $committee->description ?? '',
            ];
        });

        return ApiResponse::success([
            'total' => (string) $paginated->total(),
            'items' => $items,
        ], 'My committees fetched successfully.');
    }
}