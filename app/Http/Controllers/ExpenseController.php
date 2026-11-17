<?php
namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Expense;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    // ─────────────────────────────────
    // HELPER
    // Restricts any Expense query to only what this logged-in member is
    // allowed to see: expenses with unit_id = NULL (global, created by the
    // super admin) OR unit_id = the member's own unit (created by their
    // unit admin). Without this, every query below would return every
    // unit's expenses mixed together.
    // ─────────────────────────────────
    private function scopeToMemberUnit($query)
    {
        $unitId = MemberProfile::where('user_id', Auth::id())->value('unit_id');

        return $query->where(function ($q) use ($unitId) {
            $q->whereNull('unit_id');
            if ($unitId) {
                $q->orWhere('unit_id', $unitId);
            }
        });
    }

    // ─────────────────────────────────
    // POST /expenses/overview
    // ─────────────────────────────────
    public function overview(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date = Carbon::parse($request->date);

        $todaysExpense = $this->scopeToMemberUnit(
            Expense::whereDate('expense_date', $date)
        )->sum('amount');

        $thisMonthExpense = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $date->year)
                ->whereMonth('expense_date', $date->month)
        )->sum('amount');

        $thisYearExpense = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $date->year)
        )->sum('amount');

        $totalTransactions = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $date->year)
        )->count();

        return ApiResponse::success([
            'todaysExpense'      => (string) $todaysExpense,
            'thisMonthExpense'   => (string) $thisMonthExpense,
            'thisYearExpense'    => (string) $thisYearExpense,
            'totalTransactions'  => (string) $totalTransactions,
        ], 'Success');
    }

    // ─────────────────────────────────
    // POST /expenses/daily?page=1&limit=20
    // ─────────────────────────────────
    public function daily(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $date    = Carbon::parse($request->date);
        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $query = $this->scopeToMemberUnit(
            Expense::whereDate('expense_date', $date)
        )->orderBy('expense_time');

        $totalExpense = (clone $query)->sum('amount');
        $paginated    = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(fn ($e) => [
            'id'       => 'e' . $e->id,
            'title'    => $e->title,
            'category' => $e->category,
            'amount'   => (string) $e->amount,
            'date'     => $e->expense_date->format('Y-m-d'),
            'time'     => $e->expense_time ? Carbon::parse($e->expense_time)->format('h:i A') : '',
            'status'   => $e->status,
            'paidTo'   => $e->paid_to,
            'scope'    => $e->unit_id ? 'unit' : 'global',
        ]);

        return ApiResponse::success([
            'date'               => $date->format('Y-m-d'),
            'totalExpense'       => (string) $totalExpense,
            'totalTransactions'  => (string) $paginated->total(),
            'pagination' => [
                'currentPage'  => (string) $paginated->currentPage(),
                'totalPages'   => (string) $paginated->lastPage(),
                'totalRecords' => (string) $paginated->total(),
            ],
            'expenses' => $items,
        ], 'Success');
    }

    // ─────────────────────────────────
    // POST /expenses/monthly-summary
    // ─────────────────────────────────
    public function monthlySummary(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer',
        ]);
        $month = (int) $request->month;
        $year  = (int) $request->year;

        $base = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $year)->whereMonth('expense_date', $month)
        );

        $totalExpense      = (clone $base)->sum('amount');
        $totalTransactions = (clone $base)->count();

        $categoryTotals = (clone $base)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['category' => $r->category, 'total' => (string) $r->total]);

        return ApiResponse::success([
            'month'             => (string) $month,
            'year'              => (string) $year,
            'totalExpense'      => (string) $totalExpense,
            'totalTransactions' => (string) $totalTransactions,
            'categoryTotals'    => $categoryTotals,
        ], 'Success');
    }

    // ─────────────────────────────────
    // POST /expenses/daily-breakdown?page=1&limit=31
    // ─────────────────────────────────
    public function dailyBreakdown(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer',
        ]);
        $month   = (int) $request->month;
        $year    = (int) $request->year;
        $perPage = max(1, (int) $request->query('limit', 31));
        $page    = max(1, (int) $request->query('page', 1));

        $rows = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $year)->whereMonth('expense_date', $month)
        )
            ->selectRaw('expense_date as date, SUM(amount) as total')
            ->groupBy('expense_date')
            ->orderByDesc('expense_date')
            ->get();

        $totalRecords = $rows->count();
        $totalPages   = max(1, (int) ceil($totalRecords / $perPage));
        $sliced       = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $breakdown = $sliced->map(fn ($r) => [
            'date'  => Carbon::parse($r->date)->format('Y-m-d'),
            'total' => (string) $r->total,
        ]);

        return ApiResponse::success([
            'pagination' => [
                'currentPage'  => (string) $page,
                'totalPages'   => (string) $totalPages,
                'totalRecords' => (string) $totalRecords,
            ],
            'breakdown' => $breakdown,
        ], 'Success');
    }

    // ─────────────────────────────────
    // POST /expenses/yearly-summary
    // ─────────────────────────────────
    public function yearlySummary(Request $request)
    {
        $request->validate(['year' => 'required|integer']);
        $year = (int) $request->year;

        $base = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $year)
        );

        $totalExpense      = (clone $base)->sum('amount');
        $totalTransactions = (clone $base)->count();

        $monthlyTotals = (clone $base)
            ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($r) => ['month' => (string) $r->month, 'total' => (string) $r->total]);

        return ApiResponse::success([
            'year'              => (string) $year,
            'totalExpense'      => (string) $totalExpense,
            'totalTransactions' => (string) $totalTransactions,
            'monthlyTotals'     => $monthlyTotals,
        ], 'Success');
    }

    // ─────────────────────────────────
    // POST /expenses/month-wise-list?page=1&limit=12
    // ─────────────────────────────────
    public function monthWiseList(Request $request)
    {
        $request->validate(['year' => 'required|integer']);
        $year    = (int) $request->year;
        $perPage = max(1, (int) $request->query('limit', 12));
        $page    = max(1, (int) $request->query('page', 1));

        $rows = $this->scopeToMemberUnit(
            Expense::whereYear('expense_date', $year)
        )
            ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
            ->groupBy('month')
            ->orderByDesc('month')
            ->get();

        $totalRecords = $rows->count();
        $totalPages   = max(1, (int) ceil($totalRecords / $perPage));
        $sliced       = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $months = $sliced->map(fn ($r) => [
            'month' => (string) $r->month,
            'year'  => (string) $year,
            'total' => (string) $r->total,
        ]);

        return ApiResponse::success([
            'pagination' => [
                'currentPage'  => (string) $page,
                'totalPages'   => (string) $totalPages,
                'totalRecords' => (string) $totalRecords,
            ],
            'months' => $months,
        ], 'Success');
    }

    // ─────────────────────────────────
    // GET /expenses/year-range
    // ─────────────────────────────────
    public function yearRange()
    {
        $minDate = $this->scopeToMemberUnit(Expense::query())->min('expense_date');
        $maxDate = $this->scopeToMemberUnit(Expense::query())->max('expense_date');

        return ApiResponse::success([
            'minYear' => (string) ($minDate ? Carbon::parse($minDate)->year : now()->year),
            'maxYear' => (string) ($maxDate ? Carbon::parse($maxDate)->year : now()->year),
        ], 'Success');
    }
}