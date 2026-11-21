<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\District;
use App\Models\Unit;
use App\Models\State;
use App\Models\Payment;
use App\Models\FundraisingCampaign;
use App\Models\Committee;
use App\Models\OtherFee;
use App\Models\Scheme;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // ── Revenue now comes from the Payment model (actual transactions), not plans ──
        $totalRevenue   = Payment::where('status', 'SUCCESS')->sum('amount');
        $memberRevenue  = Payment::where('status', 'SUCCESS')->where('payment_type', 'MEMBERSHIP')->sum('amount');
        $eventRevenue   = Payment::where('status', 'SUCCESS')->where('payment_type', 'EVENT')->sum('amount');
        $renewalRevenue = Payment::where('status', 'SUCCESS')->where('payment_type', 'RENEWAL')->sum('amount');

        return view('admin.reports', [
            // ── location dropdowns ──
                        'fixedState' => State::fixed(),
            'states'     => State::all(),
            'districts'  => State::fixed()
                ? District::where('state_id', State::fixed()->id)->get()
                : District::all(),
            'units'      => State::fixed()
                ? Unit::whereHas('district', fn($d) => $d->where('state_id', State::fixed()->id))->get()
                : Unit::all(),

            // ── member counts ──
            'totalMembers'    => MemberProfile::count(),
            'activeMembers'   => MemberProfile::where('status', 'ACTIVE')->count(),
            'approvedMembers' => MemberProfile::where('status', 'APPROVED')->count(),
            'pendingMembers'  => MemberProfile::where('status', 'PENDING')->count(),
            'rejectedMembers' => MemberProfile::where('status', 'REJECTED')->count(),
            'lifeMembers'     => MemberProfile::where('membership_type', 'LIFE')->count(),
            'annualMembers'   => MemberProfile::where('membership_type', 'ANNUAL')->count(),

            // ── revenue totals (from actual Payment records) ──
            'totalRevenue'   => $totalRevenue,
            'memberRevenue'  => $memberRevenue,
            'eventRevenue'   => $eventRevenue,
            'renewalRevenue' => $renewalRevenue,

            // ── monthly registrations (last 6 months) ──
            'monthlyData' => MemberProfile::selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get(),

            // ── monthly revenue (last 6 months, from Payment) ──
            'monthlyRevenue' => Payment::where('status', 'SUCCESS')
                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, SUM(amount) as total')
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get(),

            // ── top units / districts (member counts only, no plan revenue) ──
            'topUnits'     => Unit::withCount('memberProfiles')
                ->orderByDesc('member_profiles_count')->take(5)->get(),
            'topDistricts' => District::withCount('memberProfiles')
                ->orderByDesc('member_profiles_count')->take(5)->get(),

            // ── FUNDRAISING ──
            'fundraisingTotal'     => FundraisingCampaign::count(),
            'fundraisingActive'    => FundraisingCampaign::where('status', 'ACTIVE')->count(),
            'fundraisingCompleted' => FundraisingCampaign::where('status', 'COMPLETED')->count(),
            'fundraisingRaised'    => FundraisingCampaign::sum('raised_amount'),
            'fundraisingTarget'    => FundraisingCampaign::sum('target_amount'),
            'fundraisingCampaigns' => FundraisingCampaign::latest()->take(10)->get(),

            // ── COMMITTEES ──
            'committeeTotal'  => Committee::count(),
            'committeeActive' => Committee::where('is_active', true)->count(),
            'committeeList'   => Committee::withCount('members')->latest()->take(10)->get(),

            // ── OTHER FEES ──
            'otherFeeTotal'     => OtherFee::count(),
            'otherFeeActive'    => OtherFee::where('is_active', true)->count(),
            'otherFeeCollected' => OtherFee::withCount(['payments as paid_count' => fn($q) => $q->where('status', 'PAID')])
                ->get()->sum(fn($f) => $f->paid_count * $f->amount),
            'otherFeeList' => OtherFee::withCount('payments')->latest()->take(10)->get(),

            // ── SCHEMES ──
            'schemeTotal'  => Scheme::count(),
            'schemeActive' => Scheme::where('is_active', true)->count(),
            'schemeDuesPaid' => Scheme::withCount(['dues as paid_dues' => fn($q) => $q->where('status', 'PAID')])
                ->get()->sum('paid_dues'),
            'schemeDuesPending' => Scheme::withCount(['dues as pending_dues' => fn($q) => $q->where('status', '!=', 'PAID')])
                ->get()->sum('pending_dues'),
            'schemeList' => Scheme::withCount(['dues', 'members'])->latest()->take(10)->get(),
        ]);
    }

    // ─────────────────────────────────
    // AJAX PREVIEW (member reports only)
    // ─────────────────────────────────
    public function preview(Request $request)
    {
        $query = MemberProfile::with(['user', 'unit.district.state', 'district.state', 'state']);

        if ($request->filled('state_id'))        $query->where('state_id', $request->state_id);
        if ($request->filled('district_id'))     $query->where('district_id', $request->district_id);
        if ($request->filled('unit_id'))         $query->where('unit_id', $request->unit_id);
        if ($request->filled('status'))          $query->where('status', $request->status);
        if ($request->filled('membership_type')) $query->where('membership_type', $request->membership_type);
        if ($request->filled('month'))           $query->whereMonth('created_at', $request->month);
        if ($request->filled('year'))            $query->whereYear('created_at', $request->year);
        if ($request->filled('date_from'))       $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))         $query->whereDate('created_at', '<=', $request->date_to);

        $members = $query->latest()->get();

        return response()->json([
            'count'   => $members->count(),
            'members' => $members->map(fn($m) => [
                'name'            => $m->user?->name ?? '—',
                'email'           => $m->user?->email ?? '',
                'phone'           => $m->user?->phone ?? '—',
                'organization'    => $m->organization ?? '—',
                'unit'            => $m->unit?->name ?? '—',
                'district'        => $m->district?->name ?? $m->unit?->district?->name ?? '—',
                'state'           => $m->state?->name ?? $m->district?->state?->name ?? $m->unit?->district?->state?->name ?? '—',
                'membership_type' => $m->membership_type ?? '—',
                'membership_id'   => $m->membership_id ?? '—',
                'status'          => $m->status ?? '—',
                'registered_on'   => $m->created_at?->format('d M Y') ?? '—',
                'profile_image'   => $m->photo ? asset('storage/' . $m->photo) : null,
            ]),
        ]);
    }

    // ─────────────────────────────────
    // CSV DOWNLOAD (member reports only)
    // ─────────────────────────────────
    public function download(Request $request)
    {
        $query = MemberProfile::with(['user', 'unit.district.state', 'district.state', 'state']);

        if ($request->filled('status'))          $query->where('status', $request->status);
        if ($request->filled('membership_type')) $query->where('membership_type', $request->membership_type);
        if ($request->filled('district_id'))     $query->where('district_id', $request->district_id);
        if ($request->filled('unit_id'))         $query->where('unit_id', $request->unit_id);
        if ($request->filled('state_id'))        $query->where('state_id', $request->state_id);
        if ($request->filled('month'))           $query->whereMonth('created_at', $request->month);
        if ($request->filled('year'))            $query->whereYear('created_at', $request->year);
        if ($request->filled('date_from'))       $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))         $query->whereDate('created_at', '<=', $request->date_to);

        $members  = $query->latest()->get();
        $filename = 'eca_members_report_' . now()->format('d_M_Y') . '.csv';

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
                'Organization', 'Designation', 'Experience',
                'Membership Type', 'Membership ID',
                'Status', 'Unit', 'District',
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
                    $m->designation ?? '—',
                    ($m->experience !== null && $m->experience !== '') ? ($m->experience . ' yrs') : '—',
                    $m->membership_type ?? '—',
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