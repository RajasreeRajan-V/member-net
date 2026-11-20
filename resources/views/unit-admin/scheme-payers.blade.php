@extends('unit-admin.layout')
@section('title', 'Scheme Dues')
@section('page_title', $scheme->title . ' — Member Dues')

@section('content')

  <div class="card">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-piggy-bank"></i>
        {{ $scheme->title }} — ₹{{ number_format($scheme->amount, 2) }}/month
      </div>
      <a href="{{ route('unit-admin.schemes') }}" class="btn bo bsm">
        <i class="fas fa-arrow-left"></i>Back to Schemes
      </a>
    </div>

    @if(session('success'))
      <div style="margin:12px 18px 0;padding:10px 14px;border-radius:8px;background:rgba(52,211,153,0.1);border:1px solid var(--green,#10b981);color:var(--green,#10b981);font-size:12px;">
        <i class="fas fa-check-circle" style="margin-right:6px;"></i>{{ session('success') }}
      </div>
    @endif

    <div style="padding:12px 16px;border-bottom:1px solid var(--border);
                display:flex;gap:24px;flex-wrap:wrap;font-size:12px;color:var(--text-dim);">
      <div>
        <strong style="color:var(--text)">Duration:</strong>
        {{ $scheme->duration_months }} months
      </div>
      <div>
        <strong style="color:var(--text)">Status:</strong>
        <span style="color: {{ $scheme->is_active ? 'var(--green)' : 'var(--red)' }};">
          {{ $scheme->is_active ? 'ACTIVE' : 'INACTIVE' }}
        </span>
      </div>
      <div>
        <strong style="color:var(--text)">Total Dues (your unit):</strong>
        {{ $dues->count() }}
      </div>
      <div>
        <strong style="color:var(--text)">Paid:</strong>
        {{ $dues->where('status', 'PAID')->count() }}
      </div>
    </div>

    <div class="tw">
      <table style="min-width: 850px;">
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Type</th>
            <th>Month</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Payment Details</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($dues as $due)
            @php
              $isTp = !empty($due->third_party_member_id);
              $payerName = $isTp ? ($due->thirdPartyMember->name ?? '—') : ($due->memberProfile->user->name ?? '—');
              $payerSubtitle = $isTp ? (($due->thirdPartyMember->phone ?? '') . ($due->thirdPartyMember->organization ? ' • ' . $due->thirdPartyMember->organization : '')) : ($due->memberProfile->user->email ?? $due->memberProfile->user->phone ?? '');
            @endphp
            <tr>
              <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
              <td>
                <div class="mn" style="font-weight:600;{{ $isTp ? 'color:#F59E0B;' : '' }}">{{ $payerName }}</div>
                <div class="me">{{ $payerSubtitle }}</div>
              </td>
              <td>
                @if($isTp)
                  <span class="badge" style="background:rgba(245,158,11,0.15);color:#F59E0B;border:1px solid rgba(245,158,11,0.3);font-size:10px;padding:2px 8px;">Third-Party</span>
                @else
                  <span class="badge" style="background:rgba(79,142,247,0.12);color:var(--accent);font-size:10px;padding:2px 8px;">Registered</span>
                @endif
              </td>
              <td>{{ $due->due_date->format('M Y') }}</td>
              <td style="font-weight:600;">₹{{ number_format($due->amount, 2) }}</td>
              <td>
                <span class="badge {{ $due->status === 'PAID' ? 'bap' : ($due->status === 'OVERDUE' ? 'brj' : 'bpe') }}">
                  {{ $due->status }}
                </span>
              </td>
              <td>
                @if($due->status === 'PAID')
                  <div style="font-size:12px;font-weight:600;color:var(--green,#10b981);">
                    <i class="fas fa-check" style="margin-right:4px;"></i>{{ $due->paid_date?->format('d M Y') }}
                  </div>
                  @if($due->payment_method)
                    <div style="font-size:11px;color:var(--text-dim);">Method: {{ $due->payment_method }}</div>
                  @endif
                @else
                  <span style="color:var(--text-dim);font-size:11px;">Unpaid</span>
                @endif
              </td>
              <td>
                @if($due->status !== 'PAID')
                  <button type="button" class="btn bp bsm" onclick="openRecordPaymentModal({{ $due->id }}, '{{ addslashes($payerName) }}', '{{ $due->due_date->format('M Y') }}', '{{ number_format($due->amount, 2) }}')" style="font-size:11px;padding:4px 8px;">
                    <i class="fas fa-check-circle"></i> Mark Paid
                  </button>
                @else
                  <span style="font-size:11px;color:var(--text-dim);"><i class="fas fa-lock" style="margin-right:4px;"></i>Settled</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" style="text-align:center;color:var(--text-dim);padding:40px;">
                <div style="font-size:32px;margin-bottom:10px;">🧾</div>
                <div style="font-size:13px;font-weight:600;">No dues generated yet for this scheme in your unit</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ══ RECORD PAYMENT MODAL ══ --}}
  <div class="mo" id="recordPaymentModal">
    <div class="md" style="width:420px;max-width:96vw;">
      <div class="mh">
        <div class="mt2"><i class="fas fa-receipt" style="margin-right:6px;color:var(--accent);"></i>Record Scheme Payment</div>
        <div class="mc3" onclick="cm('recordPaymentModal')"><i class="fas fa-times"></i></div>
      </div>
      <form action="{{ route('unit-admin.schemes.payers.record-payment') }}" method="POST">
        @csrf
        <input type="hidden" name="due_id" id="pay_due_id">
        <div style="padding:14px 18px 0;">
          <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:14px;">
            <div style="font-size:11px;color:var(--text-dim);">Member:</div>
            <div id="pay_member_name" style="font-weight:700;font-size:14px;color:var(--text);margin-top:2px;">—</div>
            <div style="display:flex;justify-content:space-between;margin-top:8px;font-size:12px;">
              <div>Due Month: <b id="pay_month">—</b></div>
              <div>Amount: <b id="pay_amount" style="color:var(--accent);">₹0.00</b></div>
            </div>
          </div>
        </div>
        <div class="fgrid" style="padding:0 18px;">
          <div class="fg s2">
            <label>Payment Method <span style="color:var(--red)">*</span></label>
            <select name="payment_method" required>
              <option value="Cash">Cash</option>
              <option value="UPI">UPI / GPay / PhonePe</option>
              <option value="Bank Transfer">Bank Transfer / NEFT</option>
              <option value="Cheque">Cheque</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="fg s2">
            <label>Paid Date <span style="color:var(--red)">*</span></label>
            <input type="date" name="paid_date" value="{{ date('Y-m-d') }}" required>
          </div>
          <div class="fg s2">
            <label>Notes / Reference (optional)</label>
            <input type="text" name="notes" placeholder="e.g. Receipt #1234 or UPI Ref">
          </div>
        </div>
        <div class="ff">
          <button type="button" class="btn bo" onclick="cm('recordPaymentModal')">Cancel</button>
          <button type="submit" class="btn bp"><i class="fas fa-check"></i> Confirm Payment</button>
        </div>
      </form>
    </div>
  </div>

@endsection

@section('scripts')
<script>
function openRecordPaymentModal(dueId, memberName, month, amount) {
  document.getElementById('pay_due_id').value = dueId;
  document.getElementById('pay_member_name').textContent = memberName;
  document.getElementById('pay_month').textContent = month;
  document.getElementById('pay_amount').textContent = '₹' + amount;
  om('recordPaymentModal');
}
</script>
@endsection
