<?php
namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\DistrictAdmin;
use App\Models\UnitAdmin;
use App\Models\MemberProfile;
use App\Models\MonthlyFeeDue;
use App\Models\OtherFee;
use App\Models\OtherFeePayment;
use App\Models\PaymentTransaction;
use App\Models\FundraisingCampaign;
use App\Models\FundraisingDonation;
use App\Models\Scheme;
use App\Models\SchemeFeeDue;
use App\Models\MonthlyFeeSetting;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Carbon\Carbon;

class FeePaymentController extends Controller
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
    // HELPER: is this a "duplicate row" database error?
    // Happens when two parallel requests try to insert the same row.
    // ─────────────────────────────────
    private function isDuplicateError(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) == 1062 || $e->getCode() == '23000';
    }

    // ─────────────────────────────────
    // HELPER: scope for items this member may see
    // = global (super admin) + own district admin + own unit admin
    // Use on any query that has a `created_by` column:
    //   ->where($this->creatorScope($profile))
    // ─────────────────────────────────
    private function creatorScope(MemberProfile $profile): \Closure
    {
        // Every district/unit admin in the system (their items are NOT global)
        $scopedAdminIds = DistrictAdmin::pluck('user_id')
            ->merge(UnitAdmin::pluck('user_id'))
            ->unique()->values()->all();

        // Only this member's own district admin / unit admin
        $myDistrictAdminIds = DistrictAdmin::where('district_id', $profile->district_id)->pluck('user_id')->all();
        $myUnitAdminIds     = UnitAdmin::where('unit_id', $profile->unit_id)->pluck('user_id')->all();

        return function ($q) use ($scopedAdminIds, $myDistrictAdminIds, $myUnitAdminIds) {
            $q->whereNotIn('created_by', $scopedAdminIds)      // global (super admin)
              ->orWhereIn('created_by', $myDistrictAdminIds)   // own district admin
              ->orWhereIn('created_by', $myUnitAdminIds);      // own unit admin
        };
    }

    // ─────────────────────────────────
    // SYNC: monthly due (current month) + flip stale DUE -> OVERDUE
    // ─────────────────────────────────
    private function syncMonthlyDue(MemberProfile $profile): MonthlyFeeDue
    {
        $monthYear = Carbon::now()->format('Y-m');

        $due = MonthlyFeeDue::where('member_profile_id', $profile->id)
            ->where('month_year', $monthYear)
            ->first();

        if (!$due) {
            $setting = MonthlyFeeSetting::current();

            try {
                $due = MonthlyFeeDue::create([
                    'member_profile_id' => $profile->id,
                    'amount'            => $setting->amount,
                    'due_date'          => Carbon::now()->startOfMonth()->addDays($setting->due_day - 1),
                    'status'            => 'DUE',
                    'month_year'        => $monthYear,
                ]);
            } catch (QueryException $e) {
                if (!$this->isDuplicateError($e)) throw $e;

                // A parallel request already created it — just load that one
                $due = MonthlyFeeDue::where('member_profile_id', $profile->id)
                    ->where('month_year', $monthYear)
                    ->first();
            }
        }

        if ($due->status === 'DUE' && Carbon::parse($due->due_date)->isPast()) {
            $due->update(['status' => 'OVERDUE']);
        }

        return $due;
    }

    // ─────────────────────────────────
    // SYNC: other fees (only fees this member may see)
    // ─────────────────────────────────
    private function syncOtherFees(MemberProfile $profile): void
    {
        $existingFeeIds = OtherFeePayment::where('member_profile_id', $profile->id)->pluck('other_fee_id');

        OtherFee::where('is_active', true)
            ->where($this->creatorScope($profile))
            ->whereNotIn('id', $existingFeeIds)
            ->get()
            ->each(function ($fee) use ($profile) {
                try {
                    OtherFeePayment::firstOrCreate(
                        ['other_fee_id' => $fee->id, 'member_profile_id' => $profile->id],
                        ['status' => 'DUE']
                    );
                } catch (QueryException $e) {
                    // A parallel request already created this row — safe to ignore
                    if (!$this->isDuplicateError($e)) throw $e;
                }
            });

        OtherFeePayment::where('member_profile_id', $profile->id)
            ->where('status', 'DUE')
            ->whereHas('otherFee', fn ($q) => $q->whereDate('due_date', '<', Carbon::today()))
            ->update(['status' => 'OVERDUE']);
    }

    // ─────────────────────────────────
    // SYNC: scheme dues (current month) + flip stale DUE -> OVERDUE
    // ─────────────────────────────────
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

                if ($exists) return;

                try {
                    SchemeFeeDue::create([
                        'scheme_id'         => $scheme->id,
                        'member_profile_id' => $profile->id,
                        'amount'            => $scheme->amount,
                        'due_date'          => Carbon::now()->startOfMonth()->addDays(9),
                        'status'            => 'DUE',
                        'month_year'        => $monthYear,
                    ]);
                } catch (QueryException $e) {
                    // A parallel request already created this row — safe to ignore
                    if (!$this->isDuplicateError($e)) throw $e;
                }
            });

        SchemeFeeDue::where('member_profile_id', $profile->id)
            ->where('status', 'DUE')
            ->whereDate('due_date', '<', Carbon::today())
            ->update(['status' => 'OVERDUE']);
    }

    // ─────────────────────────────────
    // GET /api/payments/overview
    // ─────────────────────────────────
    public function overview()
    {
        $profile = $this->profileOrFail();
        $this->syncOtherFees($profile);
        $this->syncSchemeFees($profile);
        $monthlyDue = $this->syncMonthlyDue($profile);
        $feeSetting = MonthlyFeeSetting::current();
        $scope      = $this->creatorScope($profile);

        $totalPaid = PaymentTransaction::where('user_id', auth()->id())
            ->where('status', 'SUCCESS')->sum('amount');

        $pendingMonthly = MonthlyFeeDue::where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])->sum('amount');

        // Only fees this member is allowed to see (also skips deleted fees)
        $pendingOtherPayments = OtherFeePayment::with('otherFee')
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->whereHas('otherFee', fn ($q) => $q->where($scope))
            ->get();

        $pendingOther = $pendingOtherPayments->sum(fn ($p) => (float) $p->otherFee->amount);

        $pendingSchemeDues = SchemeFeeDue::where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->whereHas('scheme')                       // skip dues of deleted schemes
            ->get();

        $pendingScheme = $pendingSchemeDues->sum(fn ($d) => (float) $d->amount);

        $dueItemsCount = MonthlyFeeDue::where('member_profile_id', $profile->id)
                ->whereIn('status', ['DUE', 'OVERDUE'])->count()
            + $pendingOtherPayments->count()
            + $pendingSchemeDues->count();

        return ApiResponse::success([
            'summary' => [
                'total_paid'      => (string) $totalPaid,
                'pending_dues'    => (string) ($pendingMonthly + $pendingOther + $pendingScheme),
                'due_items_count' => (string) $dueItemsCount,
            ],
            'monthly_fee' => [
                'id'       => 'mf' . $monthlyDue->id,
                'title'    => $feeSetting->title,
                'amount'   => (string) $monthlyDue->amount,
                'due_date' => $monthlyDue->due_date->format('Y-m-d'),
                'status'   => $monthlyDue->status,
            ],
        ], 'Payment overview fetched successfully.');
    }

    // ─────────────────────────────────
    // GET /api/fees/other?page=1&limit=20
    // ─────────────────────────────────
    public function otherFees(Request $request)
    {
        $profile = $this->profileOrFail();
        $this->syncOtherFees($profile);
        $scope = $this->creatorScope($profile);

        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $payments = OtherFeePayment::with('otherFee')
            ->where('member_profile_id', $profile->id)
            ->whereHas('otherFee', fn ($q) => $q->where($scope))
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $payments->getCollection()
            ->filter(fn ($p) => $p->otherFee)           // extra safety: never read title on null
            ->map(fn ($p) => [
                'id'        => 'of' . $p->other_fee_id,
                'title'     => $p->otherFee->title,
                'amount'    => (string) $p->otherFee->amount,
                'due_date'  => $p->otherFee->due_date?->format('Y-m-d'),
                'status'    => $p->status,
                'paid_date' => $p->paid_date?->format('Y-m-d'),
            ])
            ->values();

        return ApiResponse::success(['items' => $items], 'Other fees fetched successfully.');
    }

    // ─────────────────────────────────
    // GET /api/fees/scheme?page=1&limit=20
    // ─────────────────────────────────
    public function schemeFees(Request $request)
    {
        $profile = $this->profileOrFail();
        $this->syncSchemeFees($profile);

        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $dues = SchemeFeeDue::with('scheme')
            ->where('member_profile_id', $profile->id)
            ->whereHas('scheme')                        // skip dues of deleted schemes
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $dues->getCollection()
            ->filter(fn ($d) => $d->scheme)
            ->map(fn ($d) => [
                'id'        => 'si' . $d->id,
                'title'     => $d->scheme->title . ' · ' . Carbon::parse($d->due_date)->format('M Y'),
                'amount'    => (string) $d->amount,
                'due_date'  => $d->due_date->format('Y-m-d'),
                'status'    => $d->status,
                'paid_date' => $d->paid_date?->format('Y-m-d'),
            ])
            ->values();

        return ApiResponse::success(['items' => $items], 'Scheme fees fetched successfully.');
    }

    // ─────────────────────────────────
    // GET /api/dues
    // ─────────────────────────────────
    public function dues()
    {
        $profile = $this->profileOrFail();
        $this->syncOtherFees($profile);
        $this->syncSchemeFees($profile);
        $this->syncMonthlyDue($profile);
        $feeSetting = MonthlyFeeSetting::current();
        $scope      = $this->creatorScope($profile);

        $monthlyItems = MonthlyFeeDue::where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->get()
            ->map(fn ($d) => [
                'type'     => 'monthly',
                'id'       => 'mf' . $d->id,
                'title'    => $feeSetting->title,
                'amount'   => (string) $d->amount,
                'due_date' => $d->due_date->format('Y-m-d'),
            ]);

        $otherItems = OtherFeePayment::with('otherFee')
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->whereHas('otherFee', fn ($q) => $q->where($scope))
            ->get()
            ->filter(fn ($p) => $p->otherFee)
            ->map(fn ($p) => [
                'type'     => 'other',
                'id'       => 'of' . $p->other_fee_id,
                'title'    => $p->otherFee->title,
                'amount'   => (string) $p->otherFee->amount,
                'due_date' => $p->otherFee->due_date?->format('Y-m-d'),
            ]);

        $schemeItems = SchemeFeeDue::with('scheme')
            ->where('member_profile_id', $profile->id)
            ->whereIn('status', ['DUE', 'OVERDUE'])
            ->whereHas('scheme')
            ->get()
            ->filter(fn ($d) => $d->scheme)
            ->map(fn ($d) => [
                'type'     => 'scheme',
                'id'       => 'si' . $d->id,
                'title'    => $d->scheme->title . ' · ' . Carbon::parse($d->due_date)->format('M Y'),
                'amount'   => (string) $d->amount,
                'due_date' => $d->due_date->format('Y-m-d'),
            ]);

        $items    = $monthlyItems->concat($otherItems)->concat($schemeItems)->values();
        $totalDue = $items->sum(fn ($i) => (float) $i['amount']);

        return ApiResponse::success([
            'total_due' => (string) $totalDue,
            'items'     => $items,
        ], 'Dues fetched successfully.');
    }

    // ─────────────────────────────────
    // POST /api/payments/confirm
    // ─────────────────────────────────
    public function confirm(Request $request)
    {
        $request->validate([
            'type'                   => 'required|in:1,2,3,4',
            'reference_id'           => 'required|string',
            'amount'                 => 'required|numeric|min:0',
            'payment_method'         => 'required|string',
            'gateway_transaction_id' => 'required|string',
        ]);

        $profile = $this->profileOrFail();
        $type    = (int) $request->type;

        return match ($type) {
            1 => $this->confirmMonthly($request, $profile),
            2 => $this->confirmOther($request, $profile),
            3 => $this->confirmFundraising($request, $profile),
            4 => $this->confirmScheme($request, $profile),
        };
    }

    private function confirmMonthly(Request $request, MemberProfile $profile)
    {
        $id  = (int) substr($request->reference_id, 2); // strip "mf"
        $due = MonthlyFeeDue::where('id', $id)
            ->where('member_profile_id', $profile->id)->firstOrFail();

        if ($due->status === 'PAID') {
            return ApiResponse::error('This due has already been paid.', 400);
        }

        $due->update(['status' => 'PAID', 'paid_date' => Carbon::today()]);

        $title = MonthlyFeeSetting::current()->title . ' · ' . Carbon::parse($due->due_date)->format('M Y');

        $tx = PaymentTransaction::create([
            'user_id'                => auth()->id(),
            'type'                   => 1,
            'reference_id'           => 'mf' . $due->id,
            'title'                  => $title,
            'amount'                 => $due->amount,
            'method'                 => $request->payment_method,
            'gateway_transaction_id' => $request->gateway_transaction_id,
            'status'                 => 'SUCCESS',
            'paid_date'              => Carbon::today(),
        ]);

        return ApiResponse::success([
            'transaction_id' => 'txn_' . $tx->id,
            'type'           => '1',
            'reference_id'   => $tx->reference_id,
            'title'          => $tx->title,
            'amount'         => (string) $tx->amount,
            'method'         => $tx->method,
            'date'           => $tx->paid_date->format('Y-m-d'),
            'paid_date'      => $due->paid_date->format('Y-m-d'),
            'status'         => $due->status,
        ], 'Payment confirmed successfully.');
    }

    private function confirmOther(Request $request, MemberProfile $profile)
    {
        $id    = (int) substr($request->reference_id, 2); // strip "of"
        $scope = $this->creatorScope($profile);

        $payment = OtherFeePayment::with('otherFee')
            ->where('other_fee_id', $id)
            ->where('member_profile_id', $profile->id)
            ->whereHas('otherFee', fn ($q) => $q->where($scope))   // can't pay a fee you shouldn't see
            ->firstOrFail();

        if ($payment->status === 'PAID') {
            return ApiResponse::error('This fee has already been paid.', 400);
        }

        $payment->update(['status' => 'PAID', 'paid_date' => Carbon::today()]);

        $tx = PaymentTransaction::create([
            'user_id'                => auth()->id(),
            'type'                   => 2,
            'reference_id'           => 'of' . $payment->other_fee_id,
            'title'                  => $payment->otherFee->title,
            'amount'                 => $payment->otherFee->amount,
            'method'                 => $request->payment_method,
            'gateway_transaction_id' => $request->gateway_transaction_id,
            'status'                 => 'SUCCESS',
            'paid_date'              => Carbon::today(),
        ]);

        return ApiResponse::success([
            'transaction_id' => 'txn_' . $tx->id,
            'type'           => '2',
            'reference_id'   => $tx->reference_id,
            'title'          => $tx->title,
            'amount'         => (string) $tx->amount,
            'method'         => $tx->method,
            'date'           => $tx->paid_date->format('Y-m-d'),
            'paid_date'      => $payment->paid_date->format('Y-m-d'),
            'status'         => $payment->status,
        ], 'Payment confirmed successfully.');
    }

    private function confirmFundraising(Request $request, MemberProfile $profile)
    {
        $id    = (int) substr($request->reference_id, 1); // strip "c"
        $scope = $this->creatorScope($profile);

        // Can't donate to another district's / unit's campaign
        $campaign = FundraisingCampaign::where('id', $id)
            ->where($scope)
            ->firstOrFail();

        FundraisingDonation::create([
            'campaign_id'            => $campaign->id,
            'user_id'                => auth()->id(),
            'amount'                 => $request->amount,
            'status'                 => 'SUCCESS',
            'payment_method'         => $request->payment_method,
            'gateway_transaction_id' => $request->gateway_transaction_id,
        ]);

        $campaign->raised_amount += $request->amount;
        $campaign->save(); // triggers the saving() event, auto-sets status

        $tx = PaymentTransaction::create([
            'user_id'                => auth()->id(),
            'type'                   => 3,
            'reference_id'           => 'c' . $campaign->id,
            'title'                  => $campaign->title,
            'amount'                 => $request->amount,
            'method'                 => $request->payment_method,
            'gateway_transaction_id' => $request->gateway_transaction_id,
            'status'                 => 'SUCCESS',
            'paid_date'              => Carbon::today(),
        ]);

        return ApiResponse::success([
            'transaction_id'        => 'txn_' . $tx->id,
            'type'                  => '3',
            'reference_id'          => $tx->reference_id,
            'title'                 => $tx->title,
            'amount'                => (string) $tx->amount,
            'method'                => $tx->method,
            'date'                  => $tx->paid_date->format('Y-m-d'),
            'campaign_raised_total' => (string) $campaign->fresh()->raised_amount,
        ], 'Payment confirmed successfully.');
    }

    private function confirmScheme(Request $request, MemberProfile $profile)
    {
        $id  = (int) substr($request->reference_id, 2); // strip "si"
        $due = SchemeFeeDue::with('scheme')
            ->where('id', $id)
            ->where('member_profile_id', $profile->id)
            ->whereHas('scheme')
            ->firstOrFail();

        if ($due->status === 'PAID') {
            return ApiResponse::error('This due has already been paid.', 400);
        }

        $due->update(['status' => 'PAID', 'paid_date' => Carbon::today()]);

        $tx = PaymentTransaction::create([
            'user_id'                => auth()->id(),
            'type'                   => 4,
            'reference_id'           => 'si' . $due->id,
            'title'                  => $due->scheme->title . ' · ' . Carbon::parse($due->due_date)->format('M Y'),
            'amount'                 => $due->amount,
            'method'                 => $request->payment_method,
            'gateway_transaction_id' => $request->gateway_transaction_id,
            'status'                 => 'SUCCESS',
            'paid_date'              => Carbon::today(),
        ]);

        return ApiResponse::success([
            'transaction_id' => 'txn_' . $tx->id,
            'type'           => '4',
            'reference_id'   => $tx->reference_id,
            'title'          => $tx->title,
            'amount'         => (string) $tx->amount,
            'method'         => $tx->method,
            'date'           => $tx->paid_date->format('Y-m-d'),
            'paid_date'      => $due->paid_date->format('Y-m-d'),
            'status'         => $due->status,
        ], 'Payment confirmed successfully.');
    }

    // ─────────────────────────────────
    // POST /api/payments/history?page=1&limit=20
    // body: { "type": 1 }   // 1=all, 2=monthly, 3=other, 4=fundraising, 5=scheme
    // ─────────────────────────────────
    public function history(Request $request)
    {
        $request->validate([
            'type' => 'nullable|integer|in:1,2,3,4,5',
        ]);

        $filter  = (int) $request->input('type', 1);
        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $typeMap = [2 => 1, 3 => 2, 4 => 3, 5 => 4]; // history filter -> PaymentTransaction.type

        $query = PaymentTransaction::where('user_id', auth()->id())
            ->where('status', 'SUCCESS')
            ->latest();

        if ($filter !== 1 && isset($typeMap[$filter])) {
            $query->where('type', $typeMap[$filter]);
        }

        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        $labels = [1 => 'monthly', 2 => 'other', 3 => 'fundraising', 4 => 'scheme'];

        $items = $paginated->getCollection()->map(fn ($tx) => [
            'id'     => 'txn_' . $tx->id,
            'title'  => $tx->title,
            'type'   => $labels[$tx->type],
            'method' => $tx->method,
            'date'   => $tx->paid_date->format('Y-m-d'),
            'amount' => (string) $tx->amount,
        ]);

        return ApiResponse::success([
            'items'       => $items,
            'page'        => (string) $paginated->currentPage(),
            'total_pages' => (string) $paginated->lastPage(),
        ], 'Payment history fetched successfully.');
    }
}