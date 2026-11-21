<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\State;
use App\Models\Payment;
use App\Models\DistrictAdmin;
use App\Models\District;
use App\Models\FundraisingCampaign;
use App\Models\Committee;
use App\Models\OtherFee;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DistrictAdminReportController extends Controller
{
    private function myDistrictId(): int
    {
        return DistrictAdmin::where('user_id', Auth::id())->value('district_id');
    }

    private function districtUserIds(int $districtId)
    {
        return MemberProfile::where('district_id', $districtId)->pluck('user_id');
    }

    private function filteredMembers(Request $request, int $districtId, array $with)
    {
        $query = MemberProfile::with($with)->where('district_id', $districtId);

        if ($request->filled('status'))    $query->where('status', $request->status);
        if ($request->filled('month'))     $query->whereMonth('created_at', $request->month);
        if ($request->filled('year'))      $query->whereYear('created_at', $request->year);
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        return $query->latest()->get();
    }

    public function index()
    {
        $districtId = $this->myDistrictId();
        $district   = District::with('state')->find($districtId);
        $userId     = Auth::id();

        $districtUserIds = $this->districtUserIds($districtId);

        return view('district-admin.reports', [
            'district' => $district,

            // ── member counts ──
            'totalMembers'    => MemberProfile::where('district_id', $districtId)->count(),
            'activeMembers'   => MemberProfile::where('district_id', $districtId)->where('status', 'ACTIVE')->count(),
            'approvedMembers' => MemberProfile::where('district_id', $districtId)->where('status', 'APPROVED')->count(),
            'pendingMembers'  => MemberProfile::where('district_id', $districtId)->where('status', 'PENDING')->count(),
            'rejectedMembers' => MemberProfile::where('district_id', $districtId)->where('status', 'REJECTED')->count(),

            // ── monthly registrations (last 6 months) ──
            'monthlyData' => MemberProfile::where('district_id', $districtId)
                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get(),

            // ── monthly revenue (last 6 months) ──
            'monthlyRevenue' => Payment::whereIn('user_id', $districtUserIds)->where('status', 'SUCCESS')
                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount) as total')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get(),

            // ── FUNDRAISING (this district admin's own campaigns) ──
            'fundraisingTotal'     => FundraisingCampaign::where('created_by', $userId)->count(),
            'fundraisingActive'    => FundraisingCampaign::where('created_by', $userId)->where('status', 'ACTIVE')->count(),
            'fundraisingCompleted' => FundraisingCampaign::where('created_by', $userId)->where('status', 'COMPLETED')->count(),
            'fundraisingRaised'    => FundraisingCampaign::where('created_by', $userId)->sum('raised_amount'),
            'fundraisingTarget'    => FundraisingCampaign::where('created_by', $userId)->sum('target_amount'),
            'fundraisingCampaigns' => FundraisingCampaign::where('created_by', $userId)->latest()->take(10)->get(),

            // ── COMMITTEES (own) ──
            'committeeTotal'  => Committee::where('created_by', $userId)->count(),
            'committeeActive' => Committee::where('created_by', $userId)->where('is_active', true)->count(),
            'committeeList'   => Committee::where('created_by', $userId)->withCount('members')->latest()->take(10)->get(),

            // ── OTHER FEES (own, payers scoped to this district) ──
            'otherFeeTotal'  => OtherFee::where('created_by', $userId)->count(),
            'otherFeeActive' => OtherFee::where('created_by', $userId)->where('is_active', true)->count(),
            'otherFeeCollected' => OtherFee::where('created_by', $userId)
                ->withCount(['payments as paid_count' => function ($q) use ($districtId) {
                    $q->where('status', 'PAID')->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId));
                }])
                ->get()->sum(fn($f) => $f->paid_count * $f->amount),
            'otherFeeList' => OtherFee::where('created_by', $userId)
                ->withCount(['payments as payments_count' => function ($q) use ($districtId) {
                    $q->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId));
                }])
                ->latest()->take(10)->get(),

            // ── SCHEMES (own, dues/members scoped to this district) ──
            'schemeTotal'  => Scheme::where('created_by', $userId)->count(),
            'schemeActive' => Scheme::where('created_by', $userId)->where('is_active', true)->count(),
            'schemeDuesPaid' => Scheme::where('created_by', $userId)
                ->withCount(['dues as paid_dues' => function ($q) use ($districtId) {
                    $q->where('status', 'PAID')->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId));
                }])->get()->sum('paid_dues'),
            'schemeDuesPending' => Scheme::where('created_by', $userId)
                ->withCount(['dues as pending_dues' => function ($q) use ($districtId) {
                    $q->where('status', '!=', 'PAID')->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId));
                }])->get()->sum('pending_dues'),
            'schemeList' => Scheme::where('created_by', $userId)
                ->withCount([
                    'dues as dues_count' => function ($q) use ($districtId) {
                        $q->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId));
                    },
                    'members as members_count' => function ($q) use ($districtId) {
                        $q->where('district_id', $districtId);
                    },
                ])->latest()->take(10)->get(),
        ]);
    }

    // ─────────────────────────────────
    // AJAX PREVIEW (member reports only)
    // ─────────────────────────────────
    public function preview(Request $request)
    {
        $members = $this->filteredMembers($request, $this->myDistrictId(), ['user', 'unit', 'district', 'state']);

        return response()->json([
            'count'   => $members->count(),
            'members' => $members->map(fn($m) => [
                'name'          => $m->user->name ?? '—',
                'email'         => $m->user->email ?? '',
                'phone'         => $m->user->phone ?? '—',
                'organization'  => $m->organization,
                'unit'          => $m->unit->name ?? '—',
                'membership_id' => $m->membership_id ?? '—',
                'status'        => $m->status,
                'registered_on' => $m->created_at->format('d M Y'),
                'profile_image' => $m->photo ? asset('storage/' . $m->photo) : null,
            ]),
        ]);
    }

    // ─────────────────────────────────
    // CSV DOWNLOAD (member reports only)
    // ─────────────────────────────────
    public function download(Request $request)
    {
        $districtId   = $this->myDistrictId();
        $districtName = District::find($districtId)?->name ?? 'district';

        $members  = $this->filteredMembers($request, $districtId, ['user', 'unit.district.state', 'district.state', 'state']);
        $filename = 'eca_' . str_replace(' ', '_', strtolower($districtName)) . '_report_' . now()->format('d_M_Y') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $showState = !State::fixed(); // hide the State column when Kerala is fixed

        $callback = function () use ($members, $showState) {
            $file = fopen('php://output', 'w');

            // Add UTF-8 BOM so Excel opens it with proper encoding
            fputs($file, "\xEF\xBB\xBF");

            $header = [
                'S.No', 'Name', 'Email', 'Phone',
                'Organization', 'Membership ID', 'Status',
                'Unit', 'District',
            ];
            if ($showState) $header[] = 'State';
            array_push($header, 'Joined Date', 'Expiry Date', 'Registered On');
            fputcsv($file, $header);

            foreach ($members as $i => $m) {
                $joinedDate = $m->joined_date
                    ? (is_string($m->joined_date) ? date('d M Y', strtotime($m->joined_date)) : $m->joined_date->format('d M Y'))
                    : '—';
                $expiryDate = $m->expiry_date
                    ? (is_string($m->expiry_date) ? date('d M Y', strtotime($m->expiry_date)) : $m->expiry_date->format('d M Y'))
                    : '—';
                $registeredOn = $m->created_at
                    ? $m->created_at->format('d M Y')
                    : '—';

                $unitName     = $m->unit?->name ?? '—';
                $districtName = $m->district?->name ?? $m->unit?->district?->name ?? '—';
                $stateName    = $m->state?->name ?? $m->district?->state?->name ?? $m->unit?->district?->state?->name ?? '—';

                $row = [
                    $i + 1,
                    $m->user?->name ?? '—',
                    $m->user?->email ?? '',
                    $m->user?->phone ?? '—',
                    $m->organization ?? '—',
                    $m->membership_id ?? '—',
                    $m->status ?? '—',
                    $unitName,
                    $districtName,
                ];
                if ($showState) $row[] = $stateName;
                array_push($row, $joinedDate, $expiryDate, $registeredOn);
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}