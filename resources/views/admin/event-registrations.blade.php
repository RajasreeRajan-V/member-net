@extends('layouts.admin')
@section('title', 'Event Registrations')
@section('page_title', 'Event Registrations')

@section('content')

{{-- EVENT SUMMARY CARD --}}
<div class="card" style="margin-bottom:18px;">
  <div style="padding:20px;">
    <div style="display:flex;align-items:flex-start;
                justify-content:space-between;
                flex-wrap:wrap;gap:12px;">
      <div>
        <div style="font-family:'Instrument Serif',serif;
                    font-size:22px;margin-bottom:6px;">
          {{ $event->title }}
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
          <span style="font-size:11px;color:var(--text-dim);">
            <i class="fas fa-calendar"
               style="color:var(--accent)"></i>
            {{ $event->event_date->format('d M Y') }}
            {{ $event->event_time }}
          </span>
          <span style="font-size:11px;color:var(--text-dim);">
            <i class="fas fa-map-marker-alt"
               style="color:var(--accent)"></i>
            {{ $event->location ?? 'Online' }}
          </span>
          <span class="badge
            {{ $event->ticket_type == 'FREE' ? 'bap' : 'bpe' }}">
            {{ $event->ticket_type }}
            @if($event->ticket_type == 'PAID')
              ₹{{ number_format($event->ticket_price, 0) }}
            @endif
          </span>
          <span class="badge
            @if($event->status=='UPCOMING') bac
            @elseif($event->status=='ONGOING') bap
            @elseif($event->status=='COMPLETED') bex
            @else brj @endif">
            {{ $event->status }}
          </span>
        </div>
      </div>

      {{-- STATS --}}
      <div style="display:flex;gap:16px;flex-wrap:wrap;">
        <div style="text-align:center;">
          <div style="font-family:'Instrument Serif',serif;
                      font-size:28px;color:var(--accent);">
            {{ $event->registered_count }}
          </div>
          <div style="font-size:10px;color:var(--text-dim);">
            Registered
          </div>
        </div>
        <div style="text-align:center;">
          <div style="font-family:'Instrument Serif',serif;
                      font-size:28px;color:var(--green);">
            {{ $event->total_seats }}
          </div>
          <div style="font-size:10px;color:var(--text-dim);">
            Total Seats
          </div>
        </div>
        <div style="text-align:center;">
          <div style="font-family:'Instrument Serif',serif;
                      font-size:28px;color:var(--orange);">
            {{ max(0, $event->total_seats - $event->registered_count) }}
          </div>
          <div style="font-size:10px;color:var(--text-dim);">
            Remaining
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- REGISTRATIONS TABLE --}}
<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-users"></i>
      Registrations
      <span style="font-size:10px;color:var(--text-dim);
                   font-weight:400;margin-left:4px;">
        ({{ $registrations->count() }} total)
      </span>
    </div>
    <a href="{{ route('admin.events') }}"
       class="btn bo bsm">
      <i class="fas fa-arrow-left"></i>Back to Events
    </a>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Member</th>
          <th>Ticket No.</th>
          <th>Membership ID</th>
          <th>Unit</th>
          <th>District</th>
          <th>Payment</th>
          <th>Registered At</th>
        </tr>
      </thead>
      <tbody>
        @forelse($registrations as $reg)
        <tr>
          <td style="color:var(--text-dim)">
            {{ $loop->iteration }}
          </td>
          <td>
            <div class="mc2">
              <div class="av2"
                style="background:rgba(79,142,247,0.2);
                       color:var(--accent)">
                {{ strtoupper(substr($reg->user->name, 0, 2)) }}
              </div>
              <div>
                <div class="mn">{{ $reg->user->name }}</div>
                <div class="me">{{ $reg->user->email }}</div>
              </div>
            </div>
          </td>
          <td>
            <span style="font-size:10px;padding:2px 7px;
                         background:rgba(79,142,247,0.1);
                         color:var(--accent-2);
                         border-radius:4px;
                         border:1px solid rgba(79,142,247,0.2);
                         font-family:monospace;">
              {{ $reg->ticket_number }}
            </span>
          </td>
          <td style="color:var(--text-dim)">
            {{ $reg->memberProfile->membership_id ?? '—' }}
          </td>
          <td>
            <span class="utag">
              {{ $reg->memberProfile->unit->name }}
            </span>
          </td>
          <td style="color:var(--text-dim)">
            {{ $reg->memberProfile->district->name }}
          </td>
          <td>
            <span class="badge
              @if($reg->payment_status=='FREE') bap
              @elseif($reg->payment_status=='PAID') bac
              @else bpe @endif">
              {{ $reg->payment_status }}
              @if($reg->payment_status == 'PAID')
                ₹{{ number_format($reg->payment_amount, 0) }}
              @endif
            </span>
          </td>
          <td style="color:var(--text-dim)">
            {{ $reg->registered_at
              ? \Carbon\Carbon::parse($reg->registered_at)
                  ->format('d M Y h:i A')
              : $reg->created_at->format('d M Y') }}
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="8"
            style="text-align:center;
                   color:var(--text-dim);
                   padding:40px;">
            <div style="font-size:32px;margin-bottom:10px;">🎫</div>
            <div style="font-size:13px;font-weight:600;">
              No registrations yet
            </div>
            <div style="font-size:11px;margin-top:4px;">
              Members will appear here after registering
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection