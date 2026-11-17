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
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Unit</th>
            <th>Month</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Paid Date</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($dues as $due)
            <tr>
              <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
              <td>
                <div class="mn">{{ $due->memberProfile->user->name ?? '—' }}</div>
                <div class="me">{{ $due->memberProfile->user->email ?? '' }}</div>
              </td>
              <td style="color:var(--text-dim)">{{ $due->memberProfile->unit->name ?? '—' }}</td>
              <td>{{ $due->due_date->format('M Y') }}</td>
              <td>₹{{ number_format($due->amount, 2) }}</td>
              <td>
                <span class="badge {{ $due->status === 'PAID' ? 'bap' : ($due->status === 'OVERDUE' ? 'brj' : 'bpe') }}">
                  {{ $due->status }}
                </span>
              </td>
              <td style="color:var(--text-dim)">{{ $due->paid_date?->format('d M Y') ?? '—' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" style="text-align:center;color:var(--text-dim);padding:40px;">
                <div style="font-size:32px;margin-bottom:10px;">🧾</div>
                <div style="font-size:13px;font-weight:600;">No dues generated yet for this scheme in your unit</div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection