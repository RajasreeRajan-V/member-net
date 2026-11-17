@extends('unit-admin.layout')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

{{-- ══ UNIT IDENTITY BANNER ══ --}}
<div style="background:linear-gradient(135deg,rgba(79,142,247,0.12),rgba(124,58,237,0.08));
            border:1px solid rgba(79,142,247,0.15);border-radius:12px;
            padding:18px 22px;margin-bottom:18px;
            display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
  <div style="width:46px;height:46px;border-radius:11px;
              background:linear-gradient(135deg,#4f8ef7,#7c3aed);
              display:flex;align-items:center;justify-content:center;
              font-size:20px;flex-shrink:0;">🏢</div>
  <div style="flex:1;min-width:0;">
    <div style="font-family:'Instrument Serif',serif;font-size:19px;color:var(--text);">
      {{ $unit->name ?? 'Your Unit' }}
    </div>
    <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">
            {{ $unit->district->name ?? '' }}
      @unless(\App\Models\State::fixed())
        · {{ $unit->district->state->name ?? '' }}
      @endunless
      &nbsp;·&nbsp;
      <i class="fas fa-calendar" style="color:var(--accent);"></i>
      {{ now()->format('d M Y') }}
    </div>
  </div>
  <a href="{{ route('unit-admin.reports') }}" class="btn bp bsm">
    <i class="fas fa-chart-bar"></i> View Reports
  </a>
</div>

{{-- ══ STAT CARDS (clickable, admin-dashboard style) ══ --}}
<div class="sg">

  <a href="{{ route('unit-admin.members') }}" class="sc" style="--ca:var(--accent);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(79,142,247,0.15)">
        <i class="fas fa-users" style="color:var(--accent)"></i>
      </div>
      <span class="str tn">● All</span>
    </div>
    <div class="sv">{{ $totalMembers }}</div>
    <div class="sl2">Total Members</div>
  </a>

  <a href="{{ route('unit-admin.members') }}?status=PENDING" class="sc" style="--ca:var(--orange);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(251,146,60,0.15)">
        <i class="fas fa-clock" style="color:var(--orange)"></i>
      </div>
      <span class="str" style="background:rgba(251,146,60,0.15);color:var(--orange);">● Pending</span>
    </div>
    <div class="sv">{{ $pendingMembers }}</div>
    <div class="sl2">Pending Approval</div>
  </a>

  <a href="{{ route('unit-admin.members') }}?status=APPROVED" class="sc" style="--ca:var(--accent);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(79,142,247,0.15)">
        <i class="fas fa-user-check" style="color:var(--accent)"></i>
      </div>
      <span class="str tu">● Approved</span>
    </div>
    <div class="sv">{{ $approvedMembers }}</div>
    <div class="sl2">Approved (Unpaid)</div>
  </a>

 

  <a href="{{ route('unit-admin.members') }}?status=REJECTED" class="sc" style="--ca:var(--red);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(248,113,113,0.15)">
        <i class="fas fa-times-circle" style="color:var(--red)"></i>
      </div>
      <span class="str" style="background:rgba(248,113,113,0.15);color:var(--red);">● Rejected</span>
    </div>
    <div class="sv">{{ $rejectedMembers }}</div>
    <div class="sl2">Rejected Members</div>
  </a>

  <a href="{{ route('unit-admin.events') }}" class="sc" style="--ca:var(--purple);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(167,139,250,0.15)">
        <i class="fas fa-calendar-alt" style="color:var(--purple)"></i>
      </div>
      <span class="str" style="background:rgba(167,139,250,0.15);color:var(--purple);">● Events</span>
    </div>
    <div class="sv">{{ $upcomingEvents->count() }}</div>
    <div class="sl2">Upcoming Events</div>
  </a>

  <a href="{{ route('unit-admin.committees') }}" class="sc" style="--ca:#22d3ee;text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(34,211,238,0.15)">
        <i class="fas fa-users-gear" style="color:#22d3ee"></i>
      </div>
      <span class="str tu">● {{ $activeCommittees }} active</span>
    </div>
    <div class="sv">{{ $committeesCount }}</div>
    <div class="sl2">Committees</div>
  </a>

  <a href="{{ route('unit-admin.schemes') }}" class="sc" style="--ca:var(--gold);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(245,200,66,0.15)">
        <i class="fas fa-piggy-bank" style="color:var(--gold)"></i>
      </div>
      <span class="str tu">● {{ $activeSchemes }} active</span>
    </div>
    <div class="sv">{{ $schemesCount }}</div>
    <div class="sl2">Schemes</div>
  </a>

</div>

{{-- ══ CHARTS ROW ══ --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:18px;">

  {{-- LINE CHART — Monthly Registrations --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-chart-line"></i> Monthly Registrations — Last 6 Months</div>
      <a href="{{ route('unit-admin.reports') }}" class="ca">Full report →</a>
    </div>
    <div style="padding:18px;height:220px;position:relative;">
      <canvas id="lineChart"></canvas>
    </div>
  </div>

  {{-- STATUS DONUT --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-chart-pie"></i> Member Status</div>
    </div>
    <div style="padding:14px 18px;display:flex;flex-direction:column;align-items:center;gap:10px;">
      <div style="width:140px;height:140px;position:relative;">
        <canvas id="statusDonut"></canvas>
        <div style="position:absolute;inset:0;display:flex;flex-direction:column;
                    align-items:center;justify-content:center;pointer-events:none;">
          <div style="font-family:'Instrument Serif',serif;font-size:24px;color:var(--text);">{{ $totalMembers }}</div>
          <div style="font-size:9px;color:var(--text-dim);">TOTAL</div>
        </div>
      </div>
      <div style="width:100%;display:flex;flex-direction:column;gap:5px;">
        @foreach([
          ['Active',   'var(--green)',  $activeMembers,   route('unit-admin.members').'?status=ACTIVE'],
          ['Approved', 'var(--accent)', $approvedMembers, route('unit-admin.members').'?status=APPROVED'],
          ['Pending',  'var(--orange)', $pendingMembers,  route('unit-admin.members').'?status=PENDING'],
          ['Rejected', 'var(--red)',    $rejectedMembers, route('unit-admin.members').'?status=REJECTED'],
        ] as [$lbl,$col,$cnt,$href])
        <a href="{{ $href }}"
           style="display:flex;align-items:center;justify-content:space-between;
                  font-size:11px;text-decoration:none;padding:4px 6px;border-radius:6px;transition:background 0.2s;"
           onmouseover="this.style.background='rgba(255,255,255,0.05)'"
           onmouseout="this.style.background='transparent'">
          <div style="display:flex;align-items:center;gap:6px;">
            <div style="width:8px;height:8px;border-radius:2px;background:{{ $col }};flex-shrink:0;"></div>
            <span style="color:var(--text-dim);">{{ $lbl }}</span>
          </div>
          <b style="color:{{ $col }};">{{ $cnt }}</b>
        </a>
        @endforeach
      </div>
    </div>
  </div>

</div>

{{-- ══ STATUS BAR CHART + UNIT PROGRAMS ══ --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px;">

  {{-- STATUS BAR CHART --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-chart-bar"></i> Members by Status</div>
    </div>
    <div style="padding:18px;height:200px;">
      <canvas id="barChart"></canvas>
    </div>
  </div>

  {{-- UNIT PROGRAMS (replaces the old Life/Annual plan split) --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-layer-group"></i> Unit Programs</div>
    </div>
    <div style="padding:14px 18px;display:flex;flex-direction:column;gap:8px;">

      <a href="{{ route('unit-admin.committees') }}"
         style="display:flex;justify-content:space-between;align-items:center;
                padding:10px 12px;border-radius:8px;text-decoration:none;
                border:1px solid rgba(34,211,238,0.2);background:rgba(34,211,238,0.05);transition:all 0.2s;"
         onmouseover="this.style.background='rgba(34,211,238,0.12)'"
         onmouseout="this.style.background='rgba(34,211,238,0.05)'">
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fas fa-users-gear" style="color:#22d3ee;font-size:14px;"></i>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text);">Committees</div>
            <div style="font-size:10px;color:var(--text-dim);">{{ $activeCommittees }} active of {{ $committeesCount }}</div>
          </div>
        </div>
        <i class="fas fa-arrow-right" style="color:#22d3ee;font-size:11px;"></i>
      </a>

      <a href="{{ route('unit-admin.schemes') }}"
         style="display:flex;justify-content:space-between;align-items:center;
                padding:10px 12px;border-radius:8px;text-decoration:none;
                border:1px solid rgba(245,200,66,0.2);background:rgba(245,200,66,0.05);transition:all 0.2s;"
         onmouseover="this.style.background='rgba(245,200,66,0.12)'"
         onmouseout="this.style.background='rgba(245,200,66,0.05)'">
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fas fa-piggy-bank" style="color:var(--gold);font-size:14px;"></i>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text);">Schemes</div>
            <div style="font-size:10px;color:var(--text-dim);">{{ $activeSchemes }} active of {{ $schemesCount }}</div>
          </div>
        </div>
        <i class="fas fa-arrow-right" style="color:var(--gold);font-size:11px;"></i>
      </a>

      <a href="{{ route('unit-admin.other-fees') }}"
         style="display:flex;justify-content:space-between;align-items:center;
                padding:10px 12px;border-radius:8px;text-decoration:none;
                border:1px solid rgba(79,142,247,0.2);background:rgba(79,142,247,0.05);transition:all 0.2s;"
         onmouseover="this.style.background='rgba(79,142,247,0.12)'"
         onmouseout="this.style.background='rgba(79,142,247,0.05)'">
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fas fa-receipt" style="color:var(--accent);font-size:14px;"></i>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text);">Other Fees</div>
            <div style="font-size:10px;color:var(--text-dim);">{{ $activeOtherFees }} active of {{ $otherFeesCount }}</div>
          </div>
        </div>
        <i class="fas fa-arrow-right" style="color:var(--accent);font-size:11px;"></i>
      </a>

      <a href="{{ route('unit-admin.fundraising') }}"
         style="display:flex;justify-content:space-between;align-items:center;
                padding:10px 12px;border-radius:8px;text-decoration:none;
                border:1px solid rgba(167,139,250,0.2);background:rgba(167,139,250,0.05);transition:all 0.2s;"
         onmouseover="this.style.background='rgba(167,139,250,0.12)'"
         onmouseout="this.style.background='rgba(167,139,250,0.05)'">
        <div style="display:flex;align-items:center;gap:10px;">
          <i class="fas fa-hand-holding-heart" style="color:var(--purple);font-size:14px;"></i>
          <div>
            <div style="font-size:12px;font-weight:600;color:var(--text);">Fundraising</div>
            <div style="font-size:10px;color:var(--text-dim);">{{ $activeFundraising }} active of {{ $fundraisingCount }}</div>
          </div>
        </div>
        <i class="fas fa-arrow-right" style="color:var(--purple);font-size:11px;"></i>
      </a>

    </div>
  </div>

</div>

{{-- ══ REVENUE + RECENT MEMBERS + UPCOMING EVENTS ══ --}}
<div style="display:grid;grid-template-columns:1fr 2fr 1fr;gap:16px;margin-bottom:18px;">

  {{-- REVENUE CARD ══ --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-indian-rupee-sign"></i> Revenue</div>
    </div>
    <div style="padding:20px 18px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;">
      <div style="width:52px;height:52px;border-radius:12px;background:rgba(245,200,66,0.15);
                  display:flex;align-items:center;justify-content:center;margin-bottom:6px;">
        <i class="fas fa-indian-rupee-sign" style="color:var(--gold);font-size:20px;"></i>
      </div>
      <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--gold);">
        ₹{{ number_format($totalRevenue) }}
      </div>
      <div style="font-size:11px;color:var(--text-dim);">Total Revenue Collected</div>
      <a href="{{ route('unit-admin.reports') }}" class="ca" style="margin-top:6px;">Full report →</a>
    </div>
  </div>

  {{-- RECENT MEMBERS TABLE ══ --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-users"></i> Recent Members</div>
      <a href="{{ route('unit-admin.members') }}" class="ca">View all →</a>
    </div>
    @if($recentMembers->isEmpty())
      <div class="empty-state">
        <i class="fas fa-users"></i>
        <p>No members in your Unit yet</p>
      </div>
    @else
      <div class="tw">
        <table>
          <thead>
            <tr>
              <th>Member</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            @foreach($recentMembers as $m)
            <tr>
              <td>
                <div class="mc2">
                  @if($m->photo)
                    <img src="{{ asset('storage/'.$m->photo) }}"
                         style="width:28px;height:28px;border-radius:6px;object-fit:cover;flex-shrink:0;">
                  @else
                    <div class="av2" style="background:rgba(79,142,247,0.15);color:#7eb8ff;">
                      {{ strtoupper(substr($m->user->name, 0, 1)) }}
                    </div>
                  @endif
                  <div>
                    <div class="mn">{{ $m->user->name }}</div>
                    <div class="me">{{ $m->user->email }}</div>
                  </div>
                </div>
              </td>
              <td>
                @if($m->status === 'PENDING')
                  <span class="badge bpe">PENDING</span>
                @elseif($m->status === 'APPROVED')
                  <span class="badge bap">APPROVED</span>
                @elseif($m->status === 'ACTIVE')
                  <span class="badge bap">ACTIVE</span>
                @elseif($m->status === 'REJECTED')
                  <span class="badge brj">REJECTED</span>
                @else
                  <span class="badge bac">{{ $m->status }}</span>
                @endif
              </td>
              <td>
                @if($m->status === 'PENDING')
                  <div class="abr">
                    <form method="POST" action="{{ route('unit-admin.members.approve') }}" style="display:inline;">
                      @csrf
                      <input type="hidden" name="id" value="{{ $m->id }}">
                      <button type="submit" class="ab app" title="Approve"
                              onclick="return confirm('Approve {{ addslashes($m->user->name) }}?')">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                    <form method="POST" action="{{ route('unit-admin.members.reject') }}" style="display:inline;">
                      @csrf
                      <input type="hidden" name="id" value="{{ $m->id }}">
                      <button type="submit" class="ab rej" title="Reject"
                              onclick="return confirm('Reject {{ addslashes($m->user->name) }}?')">
                        <i class="fas fa-times"></i>
                      </button>
                    </form>
                  </div>
                @else
                  <span style="font-size:10px;color:var(--text-dim);">—</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  {{-- UPCOMING EVENTS ══ --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-calendar-alt"></i> Upcoming Events</div>
      <a href="{{ route('unit-admin.events') }}" class="ca">All →</a>
    </div>
    @if($upcomingEvents->isEmpty())
      <div class="empty-state" style="padding:24px;">
        <i class="fas fa-calendar-alt" style="font-size:22px;margin-bottom:8px;opacity:0.3;display:block;text-align:center;color:var(--text-dim);"></i>
        <p style="text-align:center;font-size:11px;color:var(--text-dim);">No upcoming events</p>
      </div>
    @else
      <div style="padding:10px 14px;display:flex;flex-direction:column;gap:8px;">
        @foreach($upcomingEvents as $ev)
        <div style="padding:10px 12px;background:rgba(255,255,255,0.03);
                    border:1px solid var(--border);border-radius:8px;transition:all 0.2s;"
             onmouseover="this.style.background='rgba(255,255,255,0.06)'"
             onmouseout="this.style.background='rgba(255,255,255,0.03)'">
          <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:5px;">
            <div style="font-size:12px;font-weight:600;line-height:1.3;color:var(--text);">
              {{ Str::limit($ev->title, 26) }}
            </div>
            @if($ev->ticket_type === 'FREE')
              <span class="badge bap" style="flex-shrink:0;font-size:9px;">Free</span>
            @else
              <span class="badge bpe" style="flex-shrink:0;font-size:9px;">₹{{ number_format($ev->ticket_price) }}</span>
            @endif
          </div>
          <div style="font-size:10px;color:var(--text-dim);margin-bottom:8px;">
            <i class="fas fa-calendar" style="color:var(--accent);"></i>
            {{ \Carbon\Carbon::parse($ev->event_date)->format('d M Y') }}
            &nbsp;·&nbsp;
            <i class="fas fa-clock" style="color:var(--accent);"></i>
            {{ \Carbon\Carbon::parse($ev->event_time)->format('g:i A') }}
          </div>
          <a href="{{ route('unit-admin.events.registrations', $ev->id) }}"
             class="btn bo bsm" style="font-size:10px;width:100%;justify-content:center;">
            <i class="fas fa-list-ul"></i> Registrations
          </a>
        </div>
        @endforeach
      </div>
    @endif
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
      {{ $pendingMembers }} member{{ $pendingMembers > 1 ? 's' : '' }} waiting for your approval
    </div>
    <div style="font-size:11px;color:var(--text-dim);margin-top:2px;">
      Review and approve or reject pending applications
    </div>
  </div>
  <a href="{{ route('unit-admin.members') }}?status=PENDING" class="btn bsm"
     style="background:rgba(251,146,60,0.15);color:var(--orange);border:1px solid rgba(251,146,60,0.3);">
    <i class="fas fa-arrow-right"></i> Review Now
  </a>
</div>
@endif

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8896b3';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// ── Monthly data ──
const monthlyRaw = @json($monthlyData);
const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const labels = [], counts = [];
for (let i = 5; i >= 0; i--) {
  const d = new Date();
  d.setMonth(d.getMonth() - i);
  labels.push(monthNames[d.getMonth()] + ' ' + d.getFullYear());
  const match = monthlyRaw.find(r => r.month === d.getMonth()+1 && r.year === d.getFullYear());
  counts.push(match ? match.count : 0);
}

// Line chart
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: { labels, datasets: [{
    label: 'New Members',
    data: counts,
    borderColor: '#4f8ef7',
    backgroundColor: 'rgba(79,142,247,0.08)',
    borderWidth: 2.5,
    pointBackgroundColor: '#4f8ef7',
    pointRadius: 4, pointHoverRadius: 7,
    tension: 0.4, fill: true
  }]},
  options: { responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false },
      tooltip: { callbacks: { label: ctx => ` ${ctx.raw} new members` } }
    },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.04)' } },
      y: { grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }
});

// Status donut
new Chart(document.getElementById('statusDonut'), {
  type: 'doughnut',
  data: {
    labels: ['Active','Approved','Pending','Rejected'],
    datasets: [{
      data: [{{ $activeMembers }}, {{ $approvedMembers }}, {{ $pendingMembers }}, {{ $rejectedMembers }}],
      backgroundColor: ['rgba(52,211,153,0.85)','rgba(79,142,247,0.85)','rgba(251,146,60,0.85)','rgba(248,113,113,0.85)'],
      borderColor: ['#34d399','#4f8ef7','#fb923c','#f87171'],
      borderWidth: 2
    }]
  },
  options: { responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } }, cutout: '68%'
  }
});

// Status bar chart (matches admin dashboard)
new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: ['Pending', 'Approved', 'Active', 'Rejected'],
    datasets: [{
      label: 'Members',
      data: [{{ $pendingMembers }}, {{ $approvedMembers }}, {{ $activeMembers }}, {{ $rejectedMembers }}],
      backgroundColor: [
        'rgba(251,146,60,0.7)',
        'rgba(79,142,247,0.7)',
        'rgba(52,211,153,0.7)',
        'rgba(248,113,113,0.7)',
      ],
      borderColor: ['var(--orange)', 'var(--accent)', 'var(--green)', 'var(--red)'],
      borderWidth: 1,
      borderRadius: 5,
    }]
  },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }
});
</script>
@endsection