<?php
namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\MemberProfile;
use App\Models\Scheme;
use App\Models\SchemeFeeDue;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SchemeFeeController extends Controller
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

    // Same lazy-sync pattern as FeePaymentController — kept here too so this
    // controller's endpoints work standalone regardless of call order.
    private function syncSchemeFees(MemberProfile $profile): void
    {
        $monthYear = Carbon::now()->format('Y-m');
        $today     = Carbon::today();

        Scheme::where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->whereHas('members', fn ($q) => $q->where('member_profiles.id', $profile->id))
            ->get()
            ->each(function ($scheme) use ($profile, $monthYear) {
                $exists = SchemeFeeDue::where('scheme_id', $scheme->id)
                    ->where('member_profile_id', $profile->id)
                    ->where('month_year', $monthYear)
                    ->exists();

                if (!$exists) {
                    SchemeFeeDue::create([
                        'scheme_id'         => $scheme->id,
                        'member_profile_id' => $profile->id,
                        'amount'            => $scheme->amount,
                        'due_date'          => Carbon::now()->startOfMonth()->addDays(9),
                        'status'            => 'DUE',
                        'month_year'        => $monthYear,
                    ]);
                }
            });

        SchemeFeeDue::where('member_profile_id', $profile->id)
            ->where('status', 'DUE')
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => 'OVERDUE']);
    }

    // ─────────────────────────────────
    // GET /api/schemes/my-schemes
    // ─────────────────────────────────
    public function mySchemes()
    {
        $profile = $this->profileOrFail();
        $this->syncSchemeFees($profile);

        $schemeIds = SchemeFeeDue::where('member_profile_id', $profile->id)
            ->distinct()->pluck('scheme_id');

        // ── CHANGED: only return schemes that are still active ──
        $schemes = Scheme::whereIn('id', $schemeIds)
            ->where('is_active', true)
            ->get();

        $items = $schemes->map(function ($scheme) use ($profile) {
            $dues = SchemeFeeDue::where('scheme_id', $scheme->id)
                ->where('member_profile_id', $profile->id)
                ->get();

            $monthsPaid  = $dues->where('status', 'PAID')->count();
            $totalMonths = $scheme->duration_months;

            if ($monthsPaid >= $totalMonths) {
                $status      = 'COMPLETED';
                $nextDueDate = '';
            } else {
                $nextDue = $dues->whereIn('status', ['DUE', 'OVERDUE'])
                    ->sortBy('due_date')
                    ->first();

                if ($nextDue) {
                    $status      = $nextDue->status; // DUE or OVERDUE
                    $nextDueDate = $nextDue->due_date->format('Y-m-d');
                } else {
                    // Every due generated so far is paid, next month's due isn't created yet
                    $status      = 'PAID';
                    $nextDueDate = '';
                }
            }

            return [
                'scheme_id'      => 's' . $scheme->id,
                'title'          => $scheme->title,
                'monthly_amount' => (string) $scheme->amount,
                'total_months'   => (string) $totalMonths,
                'months_paid'    => (string) $monthsPaid,
                'next_due_date'  => $nextDueDate,
                'status'         => $status,
            ];
        });

        return ApiResponse::success(['items' => $items], 'Schemes fetched successfully.');
    }

    // ─────────────────────────────────
    // POST /api/schemes/overview?page=1&limit=20
    // body: { "scheme_id": "s1" }
    // ─────────────────────────────────
    public function overview(Request $request)
    {
        $request->validate(['scheme_id' => 'required|string']);

        $profile = $this->profileOrFail();
        $this->syncSchemeFees($profile);

        $schemeId = (int) substr($request->scheme_id, 1); // strip "s"

        // ── CHANGED: reject if scheme is inactive ──
        $scheme = Scheme::where('id', $schemeId)->where('is_active', true)->first();

        if (!$scheme) {
            return ApiResponse::error('Scheme not found or no longer active.', 404);
        }

        $perPage = (int) $request->query('limit', 20);
        $page    = (int) $request->query('page', 1);

        $totalPaid = SchemeFeeDue::where('scheme_id', $scheme->id)
            ->where('member_profile_id', $profile->id)
            ->where('status', 'PAID')->sum('amount');

        $pendingDues = SchemeFeeDue::where('scheme_id', $scheme->id)
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])->sum('amount');

        $dueItemsCount = SchemeFeeDue::where('scheme_id', $scheme->id)
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])->count();

        $currentInstallment = SchemeFeeDue::where('scheme_id', $scheme->id)
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->orderBy('due_date')
            ->first();

        $installments = SchemeFeeDue::where('scheme_id', $scheme->id)
            ->where('member_profile_id', $profile->id)
            ->orderBy('due_date')
            ->paginate($perPage, ['*'], 'page', $page);

        $installmentItems = $installments->getCollection()->map(fn ($d) => [
            'id'        => 'si' . $d->id,
            'title'     => 'Installment - ' . Carbon::parse($d->due_date)->format('M Y'),
            'amount'    => (string) $d->amount,
            'due_date'  => $d->due_date->format('Y-m-d'),
            'status'    => $d->status,
            'paid_date' => $d->paid_date?->format('Y-m-d'),
        ]);

        return ApiResponse::success([
            'scheme_id' => 's' . $scheme->id,
            'title'     => $scheme->title,
            'summary'   => [
                'total_paid'      => (string) $totalPaid,
                'pending_dues'    => (string) $pendingDues,
                'due_items_count' => (string) $dueItemsCount,
            ],
            'current_installment' => $currentInstallment ? [
                'id'       => 'si' . $currentInstallment->id,
                'amount'   => (string) $currentInstallment->amount,
                'due_date' => $currentInstallment->due_date->format('Y-m-d'),
                'status'   => $currentInstallment->status,
            ] : '',
            'installments' => [
                'items'       => $installmentItems,
                'page'        => (string) $installments->currentPage(),
                'total_pages' => (string) $installments->lastPage(),
            ],
        ], 'Scheme overview fetched successfully.');
    }

    // ─────────────────────────────────
    // GET /api/schemes/dues
    // ─────────────────────────────────
    public function dues()
    {
        $profile = $this->profileOrFail();
        $this->syncSchemeFees($profile);

        // ── CHANGED: exclude dues belonging to deactivated schemes ──
        $dues = SchemeFeeDue::with('scheme')
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->whereHas('scheme', fn ($q) => $q->where('is_active', true))
            ->orderBy('due_date')
            ->get();

        $items = $dues->map(fn ($d) => [
            'scheme_id'    => 's' . $d->scheme_id,
            'scheme_title' => $d->scheme?->title ?? "",
            'id'           => 'si' . $d->id,
            'title'        => 'Installment - ' . Carbon::parse($d->due_date)->format('M Y'),
            'amount'       => (string) $d->amount,
            'due_date'     => $d->due_date->format('Y-m-d'),
        ]);

        return ApiResponse::success([
            'total_due' => (string) $dues->sum('amount'),
            'items'     => $items,
        ], 'Scheme dues fetched successfully.');
    }

    // ─────────────────────────────────
    // GET /api/schemes/payments/history?page=1&limit=20
    // ─────────────────────────────────
    // NOTE: left untouched intentionally — past payments should remain
    // visible in history even if the scheme is later deactivated, since
    // this is a historical record, not an active obligation.
    // ─────────────────────────────────
    public function paymentsHistory(Request $request)
    {
        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $paginated = PaymentTransaction::where('user_id', auth()->id())
            ->where('type', 4)
            ->where('status', 'SUCCESS')
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginated->getCollection()->map(function ($tx) {
            $dueId = (int) substr($tx->reference_id, 2); // strip "si"
            $due   = SchemeFeeDue::with('scheme')->find($dueId);

            return [
                'id'           => 'txn_' . $tx->id,
                'scheme_id'    => $due ? 's' . $due->scheme_id : null,
                'scheme_title' => $due?->scheme?->title ?? "",
                'title'        => $tx->title,
                'method'       => $tx->method,
                'date'         => $tx->paid_date->format('Y-m-d'),
                'amount'       => (string) $tx->amount,
            ];
        });

        return ApiResponse::success([
            'items'       => $items,
            'page'        => (string) $paginated->currentPage(),
            'total_pages' => (string) $paginated->lastPage(),
        ], 'Scheme payment history fetched successfully.');
    }
}