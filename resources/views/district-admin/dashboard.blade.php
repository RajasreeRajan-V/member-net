@extends('layouts.district-admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

{{-- ══ DISTRICT IDENTITY BANNER ══ --}}
<div style="background:linear-gradient(135deg,rgba(245,200,66,0.12),rgba(251,146,60,0.08));
            border:1px solid rgba(245,200,66,0.15);border-radius:12px;
            padding:18px 22px;margin-bottom:18px;
            display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
  <div style="width:46px;height:46px;border-radius:11px;
              background:linear-gradient(135deg,#f5c842,#fb923c);
              display:flex;align-items:center;justify-content:center;
              font-size:20px;flex-shrink:0;">🗺️</div>
  <div style="flex:1;min-width:0;">
    <div style="font-family:'Instrument Serif',serif;font-size:19px;color:var(--text);">
      {{ $district->name ?? 'Your District' }}
    </div>
    <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">
            @unless(\App\Models\State::fixed())
        {{ $district->state->name ?? '' }}
        &nbsp;·&nbsp;
      @endunless
      <i class="fas fa-calendar" style="color:var(--gold);"></i>
      {{ now()->format('d M Y') }}
    </div>
  </div>
</div>

{{-- ══ STAT CARDS ══ --}}
<div class="sg">

  <a href="{{ route('district-admin.units') }}" class="sc" style="--ca:var(--accent);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(79,142,247,0.15)">
        <i class="fas fa-building" style="color:var(--accent)"></i>
      </div>
    </div>
    <div class="sv">{{ $totalUnits }}</div>
    <div class="sl2">Total Units</div>
  </a>

  <a href="{{ route('district-admin.unit-admins') }}" class="sc" style="--ca:var(--purple);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(167,139,250,0.15)">
        <i class="fas fa-user-shield" style="color:var(--purple)"></i>
      </div>
    </div>
    <div class="sv">{{ $totalUnitAdmins }}</div>
    <div class="sl2">Unit Admins</div>
  </a>

  <a href="{{ route('district-admin.members') }}" class="sc" style="--ca:var(--green);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(52,211,153,0.15)">
        <i class="fas fa-users" style="color:var(--green)"></i>
      </div>
    </div>
    <div class="sv">{{ $totalMembers }}</div>
    <div class="sl2">Total Members</div>
  </a>

  <a href="{{ route('district-admin.members') }}?status=PENDING" class="sc" style="--ca:var(--orange);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(251,146,60,0.15)">
        <i class="fas fa-clock" style="color:var(--orange)"></i>
      </div>
    </div>
    <div class="sv">{{ $pendingMembers }}</div>
    <div class="sl2">Pending Approval</div>
  </a>

</div>

{{-- ══ SECOND ROW: APPROVED / REJECTED ══ --}}
<div class="sg" style="grid-template-columns: repeat(2, 1fr);">

  <a href="{{ route('district-admin.members') }}?status=APPROVED" class="sc" style="--ca:var(--accent);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(79,142,247,0.15)">
        <i class="fas fa-user-check" style="color:var(--accent)"></i>
      </div>
    </div>
    <div class="sv">{{ $approvedMembers }}</div>
    <div class="sl2">Approved Members</div>
  </a>

  <a href="{{ route('district-admin.members') }}?status=REJECTED" class="sc" style="--ca:var(--red);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(248,113,113,0.15)">
        <i class="fas fa-times-circle" style="color:var(--red)"></i>
      </div>
    </div>
    <div class="sv">{{ $rejectedMembers }}</div>
    <div class="sl2">Rejected Members</div>
  </a>

</div>

{{-- ══ TOP UNITS + RECENT MEMBERS ══ --}}
<div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;margin-bottom:18px;">

  {{-- TOP UNITS ══ --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-trophy"></i>Top Units</div>
      <a href="{{ route('district-admin.units') }}" class="ca">View all →</a>
    </div>
    <div style="padding:10px 14px;display:flex;flex-direction:column;gap:8px;">
      @forelse($topUnits as $unit)
        <div style="display:flex;justify-content:space-between;align-items:center;
                    padding:8px 10px;background:rgba(255,255,255,0.03);
                    border:1px solid var(--border);border-radius:8px;">
          <span style="font-size:12px;font-weight:500;">{{ $unit->name }}</span>
          <span class="badge bli">{{ $unit->member_profiles_count }} members</span>
        </div>
      @empty
        <div class="empty-state" style="padding:20px;">
          <p style="font-size:11px;">No Units yet.</p>
        </div>
      @endforelse
    </div>
  </div>

  {{-- RECENT MEMBERS ══ --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-users"></i>Recent Members</div>
      <a href="{{ route('district-admin.members') }}" class="ca">View all →</a>
    </div>
    <div class="tw">
      <table>
        <thead>
          <tr><th>Member</th><th>Unit</th><th>Status</th><th>Registered</th></tr>
        </thead>
        <tbody>
          @forelse($recentMembers as $m)
          <tr>
            <td>
              <div class="mc2">
                @if($m->photo)
                  <img src="{{ asset('storage/' . $m->photo) }}"
                       style="width:28px;height:28px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                @else
                  <div class="av2" style="background:rgba(245,200,66,0.15);color:var(--gold);">
                    {{ strtoupper(substr($m->user->name ?? '?', 0, 1)) }}
                  </div>
                @endif
                <div>
                  <div class="mn">{{ $m->user->name ?? '—' }}</div>
                  <div class="me">{{ $m->user->email ?? '' }}</div>
                </div>
              </div>
            </td>
            <td>{{ $m->unit->name ?? '—' }}</td>
            <td>
              <span class="badge
                @if($m->status == 'PENDING') bpe
                @elseif($m->status == 'APPROVED') bap
                @elseif($m->status == 'ACTIVE') bac
                @elseif($m->status == 'REJECTED') brj
                @else bex @endif">
                {{ $m->status }}
              </span>
            </td>
            <td style="color:var(--text-dim);">{{ $m->created_at->format('d M Y') }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="4">
              <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No members in your district yet.</p>
              </div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

{{-- ══ PENDING APPROVAL ALERT (if any) ══ --}}
@if($pendingMembers > 0)
<div style="background:rgba(251,146,60,0.08);border:1px solid rgba(251,146,60,0.25);
            border-radius:10px;padding:14px 18px;margin-bottom:18px;
            display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
  <div style="width:36px;height:36px;border-radius:8px;background:rgba(251,146,60,0.15);
              display:flex;align-items:center;justify-content:center;flex-shrink:0;">
    <i class="fas fa-exclamation-triangle" style="color:var(--orange);font-size:15px;"></i>
  </div>
  <div style="flex:1;min-width:0;">
    <div style="font-size:13px;font-weight:600;color:var(--orange);">
      {{ $pendingMembers }} member{{ $pendingMembers > 1 ? 's' : '' }} waiting for approval across your district
    </div>
    <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">
      Review and approve or reject pending applications
    </div>
  </div>
  <a href="{{ route('district-admin.members') }}?status=PENDING" class="btn bsm"
     style="background:rgba(251,146,60,0.15);color:var(--orange);border:1px solid rgba(251,146,60,0.3);">
    <i class="fas fa-arrow-right"></i> Review Now
  </a>
</div>
@endif

@endsection