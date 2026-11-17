@extends('layouts.district-admin')
@section('title', 'Fee Payers')
@section('page_title', 'Other Fee — Payers')

@section('content')

  <div class="card">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-receipt"></i>
        {{ $fee->title }} — ₹{{ number_format($fee->amount, 2) }}
      </div>
      <a href="{{ route('district-admin.other-fees') }}" class="btn bo bsm">
        <i class="fas fa-arrow-left"></i>Back to Fees
      </a>
    </div>

    <div style="padding:12px 16px;border-bottom:1px solid var(--border);
                display:flex;gap:24px;flex-wrap:wrap;font-size:12px;color:var(--text-dim);">
      <div>
        <strong style="color:var(--text)">Due Date:</strong>
        {{ $fee->due_date ? $fee->due_date->format('d M Y') : '—' }}
      </div>
      <div>
        <strong style="color:var(--text)">Status:</strong>
        <span style="color: {{ $fee->is_active ? 'var(--green)' : 'var(--red)' }};">
          {{ $fee->is_active ? 'ACTIVE' : 'INACTIVE' }}
        </span>
      </div>
      <div>
        <strong style="color:var(--text)">Total Payers (your district):</strong>
        {{ $payments->count() }}
      </div>
      <div>
        <strong style="color:var(--text)">Paid:</strong>
        {{ $payments->where('status', 'PAID')->count() }}
      </div>
    </div>

    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Unit</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Paid On</th>
          </tr>
        </thead>
        <tbody>
          @forelse($payments as $p)
            <tr>
              <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
              <td>
                <div class="mn">{{ $p->memberProfile->user->name ?? '—' }}</div>
                <div class="me">{{ $p->memberProfile->user->email ?? '' }}</div>
              </td>
              <td style="color:var(--text-dim)">
                {{ $p->memberProfile->unit->name ?? '—' }}
              </td>
              <td>₹{{ number_format($p->amount ?? $fee->amount, 2) }}</td>
              <td>
                <span class="badge {{ $p->status == 'PAID' ? 'bac' : 'bpe' }}">
                  {{ $p->status }}
                </span>
              </td>
              <td style="color:var(--text-dim)">
                {{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d M Y') : '—' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" style="text-align:center;color:var(--text-dim);padding:40px;">
                <div style="font-size:32px;margin-bottom:10px;">🧾</div>
                <div style="font-size:13px;font-weight:600;">No members in your district have paid this fee yet</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection