<?php
namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\FundraisingCampaign;
use App\Models\FundraisingDonation;
use App\Models\MemberProfile;
use App\Models\DistrictAdmin;
use App\Models\UnitAdmin;
use Illuminate\Http\Request;

class FundraisingController extends Controller
{
    // ─────────────────────────────────
    // HELPER: campaigns visible to the logged-in member
    // = global (super admin) + own district admin's + own unit admin's
    // ─────────────────────────────────
    private function visibleCampaignsQuery(Request $request)
    {
        $profile = MemberProfile::where('user_id', $request->user()->id)->first();

        // Every district/unit admin in the system (their campaigns are NOT global)
        $scopedAdminIds = DistrictAdmin::pluck('user_id')
            ->merge(UnitAdmin::pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        // Only this member's own district admin / unit admin
        $myDistrictAdminIds = $profile
            ? DistrictAdmin::where('district_id', $profile->district_id)->pluck('user_id')->all()
            : [];

        $myUnitAdminIds = $profile
            ? UnitAdmin::where('unit_id', $profile->unit_id)->pluck('user_id')->all()
            : [];

        return FundraisingCampaign::where(function ($q) use ($scopedAdminIds, $myDistrictAdminIds, $myUnitAdminIds) {
            // Global: created by anyone who is NOT a district/unit admin (i.e. super admin)
            $q->whereNotIn('created_by', $scopedAdminIds)
              // Own district's admin
              ->orWhereIn('created_by', $myDistrictAdminIds)
              // Own unit's admin
              ->orWhereIn('created_by', $myUnitAdminIds);
        });
    }

    // ─────────────────────────────────
    // GET CAMPAIGNS (member protected)
    // GET /api/fundraising/campaigns?page=1&limit=20
    // Requires: Authorization: Bearer {token}
    // ─────────────────────────────────
    public function getCampaigns(Request $request)
    {
        $user = auth()->user();

        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $campaigns = $this->visibleCampaignsQuery($request)
            ->where('status', 'ACTIVE')                           // hide completed / cancelled
            ->whereColumn('raised_amount', '<', 'target_amount')  // hide once goal is met
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        // ── This member's donations across ALL campaigns in one query ──
        $myDonations = FundraisingDonation::where('user_id', $user->id)
            ->where('status', 'SUCCESS')
            ->get()
            ->groupBy('campaign_id');

        $items = $campaigns->getCollection()->map(function ($c) use ($myDonations) {
            $donationsForThisCampaign = $myDonations->get($c->id);
            $myTotal = $donationsForThisCampaign
                ? $donationsForThisCampaign->sum('amount')
                : 0;

            return [
                'id'                => 'c' . $c->id,
                'title'             => (string) ($c->title ?? ''),
                'description'       => (string) ($c->description ?? ''),
                'image'             => $c->image ? (string) asset('storage/' . $c->image) : '',
                'raised'            => (string) $c->raised_amount,
                'target'            => (string) $c->target_amount,
                'end_date'          => $c->end_date ? $c->end_date->format('Y-m-d') : '',
                'status'            => (string) ($c->status ?? ''),
                'has_donated'       => $myTotal > 0 ? '1' : '0',
                'my_donated_amount' => (string) $myTotal,
            ];
        });

        return ApiResponse::success([
            'items'       => $items,
            'page'        => (string) $campaigns->currentPage(),
            'total_pages' => (string) $campaigns->lastPage(),
        ], 'Campaigns fetched successfully.');
    }
}