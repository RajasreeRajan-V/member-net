@extends('unit-admin.layout')
@section('title', 'Event Registrations')
@section('page_title', 'Event Registrations')

@section('content')

{{-- BACK LINK --}}
<div style="margin-bottom:14px;">
  <a href="{{ route('unit-admin.events') }}" class="btn bo bsm">
    <i class="fas fa-arrow-left"></i> Back to Events
  </a>
</div>

{{-- EVENT INFO CARD --}}
<div class="card">
  <div style="padding:16px 18px;display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
    @if($event->image)
      <img src="{{ asset('storage/'.$event->image) }}"
           style="width:72px;height:72px;border-radius:9px;object-fit:cover;flex-shrink:0;">
    @else
      <div style="width:72px;height:72px;border-radius:9px;background:rgba(167,139,250,0.12);display:flex;align-items:center;justify-content:center;color:#a78bfa;font-size:22px;flex-shrink:0;">
        <i class="fas fa-calendar-alt"></i>
      </div>
    @endif

    <div style="flex:1;min-width:0;">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px;">
        <div style="font-family:'Instrument Serif',serif;font-size:17px;">{{ $event->title }}</div>
        @if($event->status === 'UPCOMING')
          <span class="badge bup">UPCOMING</span>
        @elseif($event->status === 'ONGOING')
          <span class="badge bog">ONGOING</span>
        @elseif($event->status === 'COMPLETED')
          <span class="badge bcm">COMPLETED</span>
        @elseif($event->status === 'CANCELLED')
          <span class="badge bcl">CANCELLED</span>
        @endif
        @if($event->ticket_type === 'FREE')
          <span class="badge bap">Free</span>
        @else
          <span class="badge bpe">₹{{ number_format($event->ticket_price) }}</span>
        @endif
      </div>
      <div style="display:flex;gap:14px;flex-wrap:wrap;font-size:11px;color:var(--text-dim);">
        <span><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($event->event_date)->format('d M Y') }}</span>
        <span><i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($event->event_time)->format('g:i A') }}</span>
        @if($event->is_online)
          <span style="color:#34d399;"><i class="fas fa-wifi"></i> Online</span>
        @else
          <span><i class="fas fa-map-marker-alt"></i> {{ $event->location }}</span>
        @endif
        <span><i class="fas fa-chair"></i> {{ $event->total_seats }} seats</span>
          {{ $event->total_seats == 0 ? 'Unlimited' : $event->total_seats . ' seats' }}
        </span>
      </div>
    </div>

    {{-- Registrations count badge --}}
    <div style="text-align:center;flex-shrink:0;">
      <div style="font-family:'Instrument Serif',serif;font-size:30px;color:var(--accent);line-height:1;">
        {{ $registrations->count() }}
      </div>
      <div style="font-size:10px;color:var(--text-dim);">Registrations</div>
    </div>
  </div>
</div>

{{-- REGISTRATIONS TABLE --}}
<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-list-ul"></i> Registrations from Your Unit
    </div>
  </div>

  @if($registrations->isEmpty())
    <div class="empty-state">
      <i class="fas fa-user-slash"></i>
      <p>No registrations from your unit for this event</p>
    </div>
  @else
    <div class="tw">
      <table style="min-width: 780px;">
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Phone</th>
            <th>Unit</th>
            <th>Registered On</th>
            <th>Payment</th>
          </tr>
        </thead>
        <tbody>
          @foreach($registrations as $i => $reg)
          <tr>
            <td style="color:var(--text-dim);">{{ $i + 1 }}</td>
            <td>
              <div class="mc2">
                <div class="av2" style="background:rgba(79,142,247,0.15);color:#7eb8ff;">
                  {{ strtoupper(substr($reg->user->name ?? '?', 0, 1)) }}
                </div>
                <div>
                  <div class="mn">{{ $reg->user->name ?? '—' }}</div>
                  <div class="me">{{ $reg->user->email ?? '—' }}</div>
                </div>
              </div>
            </td>
            <td style="color:var(--text-dim);">{{ $reg->user->phone ?? '—' }}</td>
            <td>{{ $reg->memberProfile?->unit?->name ?? '—' }}</td>
            <td style="color:var(--text-dim);">{{ $reg->created_at->format('d M Y') }}</td>
            <td>
              @if($event->ticket_type === 'FREE')
                <span class="badge bap">Free</span>
              @else
                @if(isset($reg->payment_status) && $reg->payment_status === 'PAID')
                  <span class="badge bap">PAID</span>
                @else
                  <span class="badge bpe">PENDING</span>
                @endif
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>

@endsection
