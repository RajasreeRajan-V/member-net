@extends('layouts.admin')
@section('title', 'Scheme Dues')
@section('page_title', $scheme->title . ' — Member Dues')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-piggy-bank"></i> {{ $scheme->title }} — Member Dues</div>
    <a href="{{ route('admin.schemes') }}" class="btn bo bsm">
      <i class="fas fa-arrow-left"></i> Back to Schemes
    </a>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
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
            <td>
              <div class="mc2">
                <div class="av2" style="background:rgba(79,142,247,0.2);display:flex;align-items:center;justify-content:center;color:var(--accent-2);font-weight:700;font-size:10px;">
                  {{ strtoupper(substr($due->memberProfile->user->name ?? '—', 0, 2)) }}
                </div>
                <div>
                  <div class="mn">{{ $due->memberProfile->user->name ?? '—' }}</div>
                  <div class="me">{{ $due->memberProfile->user->email ?? '' }}</div>
                </div>
              </div>
            </td>
            <td><span class="utag">{{ $due->memberProfile->unit->name ?? '—' }}</span></td>
            <td>{{ $due->due_date->format('M Y') }}</td>
            <td>₹{{ number_format($due->amount, 2) }}</td>
            <td>
              <span class="badge {{ $due->status === 'PAID' ? 'bap' : ($due->status === 'OVERDUE' ? 'brj' : 'bpe') }}">
                {{ $due->status }}
              </span>
            </td>
            <td>{{ $due->paid_date?->format('d M Y') ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;color:var(--text-dim);white-space:normal;">No dues generated yet for this scheme.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection