@extends('layouts.admin')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')

{{-- ── STAT CARDS (clickable) ── --}}
<div class="sg">

  <a href="{{ route('admin.units') }}" class="sc" style="--ca:#22d3ee;text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(34,211,238,0.15)">
        <i class="fas fa-building" style="color:#22d3ee"></i>
      </div>
      <span class="str tn">● Total</span>
    </div>
    <div class="sv">{{ $totalUnits }}</div>
    <div class="sl2">Total Units</div>
  </a>

  <a href="{{ route('admin.unit-admins') }}" class="sc" style="--ca:var(--purple);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(167,139,250,0.15)">
        <i class="fas fa-user-shield" style="color:var(--purple)"></i>
      </div>
      <span class="str tn">● Total</span>
    </div>
    <div class="sv">{{ $totalUnitAdmins }}</div>
    <div class="sl2">Unit Admins</div>
  </a>

  <a href="{{ route('admin.members') }}" class="sc" style="--ca:var(--green);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(52,211,153,0.15)">
        <i class="fas fa-users" style="color:var(--green)"></i>
      </div>
      <span class="str tu">● All</span>
    </div>
    <div class="sv">{{ $totalMembers }}</div>
    <div class="sl2">Total Members</div>
  </a>

  <a href="{{ route('admin.members') }}?status=PENDING" class="sc" style="--ca:var(--orange);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(251,146,60,0.15)">
        <i class="fas fa-clock" style="color:var(--orange)"></i>
      </div>
      <span class="str" style="background:rgba(251,146,60,0.15);color:var(--orange);">● Pending</span>
    </div>
    <div class="sv">{{ $pendingMembers }}</div>
    <div class="sl2">Pending Approval</div>
  </a>

  <a href="{{ route('admin.members') }}?status=APPROVED" class="sc" style="--ca:var(--accent);text-decoration:none;">
    <div class="st">
      <div class="si" style="background:rgba(79,142,247,0.15)">
        <i class="fas fa-user-check" style="color:var(--accent)"></i>
      </div>
      <span class="str tu">● Approved</span>
    </div>
    <div class="sv">{{ $approvedMembers }}</div>
    <div class="sl2">Approved Members</div>
  </a>


</div>

{{-- ── CHARTS ROW ── --}}
<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-bottom:18px;">

  {{-- LINE CHART — Monthly Registrations --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-chart-line"></i> Member Registrations (Last 6 Months)</div>
    </div>
    <div style="padding:18px;height:220px;position:relative;">
      <canvas id="lineChart"></canvas>
    </div>
  </div>

  {{-- DONUT CHART — Membership Type --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-chart-pie"></i> Membership Type</div>
    </div>
    <div style="padding:18px;height:220px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;">
      <canvas id="donutChart" style="max-height:160px;"></canvas>
      <div style="display:flex;gap:14px;font-size:11px;">
        <span style="color:var(--gold);"><span style="font-weight:700;">{{ $lifeCount }}</span> Life</span>
        <span style="color:var(--accent);"><span style="font-weight:700;">{{ $annualCount }}</span> Annual</span>
      </div>
    </div>
  </div>

</div>

{{-- ── SECOND ROW: Status Bar + Top Units ── --}}
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

  {{-- TOP UNITS TABLE --}}
  <div class="card" style="margin:0">
    <div class="ch">
      <div class="ct"><i class="fas fa-trophy"></i> Top Units by Members</div>
    </div>
    <div style="padding:14px 18px;">
      @forelse($topUnits as $i => $unit)
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:11px;">
        <div style="width:20px;height:20px;border-radius:50%;
                    background:{{ ['rgba(79,142,247,0.2)','rgba(52,211,153,0.2)','rgba(245,200,66,0.2)','rgba(167,139,250,0.2)','rgba(251,146,60,0.2)'][$i] }};
                    display:flex;align-items:center;justify-content:center;
                    font-size:9px;font-weight:700;
                    color:{{ ['var(--accent)','var(--green)','var(--gold)','var(--purple)','var(--orange)'][$i] }};
                    flex-shrink:0;">
          {{ $i+1 }}
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            {{ $unit->name }}
          </div>
          <div style="height:4px;background:rgba(255,255,255,0.06);border-radius:2px;margin-top:4px;overflow:hidden;">
            <div style="height:100%;border-radius:2px;width:{{ $topUnits->first()->member_profiles_count > 0 ? round(($unit->member_profiles_count / $topUnits->first()->member_profiles_count) * 100) : 0 }}%;
                        background:{{ ['var(--accent)','var(--green)','var(--gold)','var(--purple)','var(--orange)'][$i] }};
                        transition:width 0.8s ease;"></div>
          </div>
        </div>
        <div style="font-size:12px;font-weight:700;color:var(--text);flex-shrink:0;">
          {{ $unit->member_profiles_count }}
        </div>
      </div>
      @empty
      <div style="text-align:center;color:var(--text-dim);padding:20px;font-size:12px;">No data yet</div>
      @endforelse
    </div>
  </div>

</div>

{{-- ── RECENT MEMBERS TABLE ── --}}
<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-users"></i>Recent Members</div>
    <a class="ca" href="{{ route('admin.members') }}">View all →</a>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Member</th>
          <th>Unit</th>
          <th>Type</th>
          <th>Status</th>
          <th>Registered</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentMembers as $member)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td>
            <div class="mc2">
              <div class="av2" style="background:rgba(79,142,247,0.2);color:var(--accent)">
                {{ strtoupper(substr($member->user->name,0,2)) }}
              </div>
              <div>
                <div class="mn">{{ $member->user->name }}</div>
                <div class="me">{{ $member->user->email }}</div>
              </div>
            </div>
          </td>
          <td><span class="utag">{{ $member->unit->name }}</span></td>
          <td>
            <span class="badge {{ $member->membership_type == 'LIFE' ? 'bli' : 'ban' }}">
              {{ $member->membership_type }}
            </span>
          </td>
          <td>
            <span class="badge
              @if($member->status=='PENDING') bpe
              @elseif($member->status=='APPROVED') bap
              @elseif($member->status=='REJECTED') brj
              @else bex @endif">
              {{ $member->status }}
            </span>
          </td>
          <td style="color:var(--text-dim)">{{ $member->created_at->format('d M Y') }}</td>
        </tr>
        @empty
        <tr>
          <td colspan="6" style="text-align:center;color:var(--text-dim);padding:30px;">No members yet</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8896b3';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

// ── Monthly data from controller ──
const monthlyRaw = @json($monthlyData);
const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

// Build last 6 months labels + fill zeros
const labels = [];
const counts = [];
for (let i = 5; i >= 0; i--) {
  const d = new Date();
  d.setMonth(d.getMonth() - i);
  labels.push(monthNames[d.getMonth()] + ' ' + d.getFullYear());
  const match = monthlyRaw.find(r => r.month === d.getMonth()+1 && r.year === d.getFullYear());
  counts.push(match ? match.count : 0);
}

// ── LINE CHART ──
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels,
    datasets: [{
      label: 'Registrations',
      data: counts,
      borderColor: '#4f8ef7',
      backgroundColor: 'rgba(79,142,247,0.08)',
      borderWidth: 2,
      pointBackgroundColor: '#4f8ef7',
      pointRadius: 4,
      pointHoverRadius: 6,
      tension: 0.4,
      fill: true,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { color: 'rgba(255,255,255,0.04)' } },
      y: { grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }
});

// ── DONUT CHART ──
new Chart(document.getElementById('donutChart'), {
  type: 'doughnut',
  data: {
    labels: ['Life', 'Annual'],
    datasets: [{
      data: [{{ $lifeCount }}, {{ $annualCount }}],
      backgroundColor: ['rgba(245,200,66,0.8)', 'rgba(79,142,247,0.8)'],
      borderColor: ['#f5c842', '#4f8ef7'],
      borderWidth: 2,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed } }
    },
    cutout: '68%',
  }
});

// ── BAR CHART ──
new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: ['Pending', 'Approved', 'Rejected'],
datasets: [{
  label: 'Members',
  data: [{{ $pendingMembers }}, {{ $approvedMembers }}, {{ $rejectedMembers }}],
  backgroundColor: [
    'rgba(251,146,60,0.7)',
    'rgba(52,211,153,0.7)',
    'rgba(248,113,113,0.7)',
  ],
  borderColor: [
    'var(--orange)', 'var(--green)', 'var(--red)'
  ],
  borderWidth: 1,
  borderRadius: 5,
}]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false } },
      y: { grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true, ticks: { stepSize: 1 } }
    }
  }
});
</script>
@endsection