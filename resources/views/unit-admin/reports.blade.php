@extends('unit-admin.layout')
@section('title', 'Reports')
@section('page_title', 'Reports')

@section('content')

{{-- ══ REPORT BUTTONS HUB ══ --}}
<div class="card" style="margin-bottom:20px;">
  <div class="ch">
    <div class="ct"><i class="fas fa-chart-bar"></i> Report Categories</div>
    <span style="font-size:11px;color:var(--text-dim);">{{ $unit->name ?? '' }}</span>
  </div>
  <div style="padding:18px;display:flex;flex-wrap:wrap;gap:10px;">
    <button class="rpt-btn active" onclick="showReport('status')" id="btn-status">
      <i class="fas fa-toggle-on"></i> Member by Status
    </button>
    <button class="rpt-btn" onclick="showReport('pending')" id="btn-pending">
      <i class="fas fa-clock"></i> Pending Members
    </button>
    <button class="rpt-btn" onclick="showReport('monthly')" id="btn-monthly">
      <i class="fas fa-calendar-alt"></i> Monthly Report
    </button>
    <button class="rpt-btn" onclick="showReport('fundraising')" id="btn-fundraising">
      <i class="fas fa-hand-holding-heart"></i> Fundraising
    </button>
    <button class="rpt-btn" onclick="showReport('committees')" id="btn-committees">
      <i class="fas fa-users-rectangle"></i> Committees
    </button>
    <button class="rpt-btn" onclick="showReport('otherfees')" id="btn-otherfees">
      <i class="fas fa-receipt"></i> Other Fees
    </button>
    <button class="rpt-btn" onclick="showReport('schemes')" id="btn-schemes">
      <i class="fas fa-piggy-bank"></i> Schemes
    </button>
  </div>
</div>

{{-- ── 1. MEMBER BY STATUS ── --}}
<div class="rpt-section" id="rpt-status">
  <div class="card">
    <div class="ch"><div class="ct"><i class="fas fa-toggle-on"></i> Member by Status</div></div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div class="fg" style="margin:0;min-width:120px;flex:1;">
        <label>Status</label>
        <select id="sts_status">
          <option value="">All Status</option>
          <option value="PENDING">Pending</option>
          <option value="APPROVED">Approved</option>
          <option value="ACTIVE">Active</option>
          <option value="REJECTED">Rejected</option>
        </select>
      </div>
      <div class="fg" style="margin:0;min-width:120px;flex:1;"><label>From Date</label><input type="date" id="sts_from"></div>
      <div class="fg" style="margin:0;min-width:120px;flex:1;"><label>To Date</label><input type="date" id="sts_to"></div>
      <div style="display:flex;gap:6px;">
        <button class="btn bp" onclick="loadReport('status')"><i class="fas fa-search"></i> Search</button>
        <button class="btn bo" onclick="resetReport('status')"><i class="fas fa-times"></i></button>
      </div>
    </div>

    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
      @foreach([
        ['PENDING',  'var(--orange)', $pendingMembers,  'fa-clock'],
        ['APPROVED', 'var(--green)',  $approvedMembers, 'fa-check-circle'],
        ['ACTIVE',   'var(--accent)', $activeMembers,   'fa-user-check'],
        ['REJECTED', 'var(--red)',    $rejectedMembers, 'fa-times-circle'],
      ] as [$lbl,$col,$cnt,$ico])
      <div style="flex:1;min-width:130px;background:rgba(255,255,255,0.03);border:1px solid var(--border);border-radius:8px;padding:12px;cursor:pointer;"
           onclick="document.getElementById('sts_status').value='{{ $lbl }}';loadReport('status');">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
          <i class="fas {{ $ico }}" style="color:{{ $col }};font-size:13px;"></i>
          <span style="font-size:10px;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.5px;font-weight:700;">{{ $lbl }}</span>
        </div>
        <div style="font-family:'Instrument Serif',serif;font-size:24px;color:{{ $col }};">{{ $cnt }}</div>
      </div>
      @endforeach
    </div>

    <div id="sts_stats" class="rpt-stats" style="display:none;"></div>
    <div id="sts_body" class="rpt-body">@include('unit-admin.partials.report-initial')</div>
    <div id="sts_footer" class="rpt-footer" style="display:none;">
      <span style="font-size:11px;color:var(--text-dim);"><i class="fas fa-info-circle"></i> Showing filtered status results</span>
      <button class="btn bp" onclick="downloadReport('status')"><i class="fas fa-download"></i> Download CSV</button>
    </div>
  </div>
</div>

{{-- ── 2. PENDING MEMBERS ── --}}
<div class="rpt-section" id="rpt-pending" style="display:none;">
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-clock"></i> Pending Members</div>
      <span style="font-size:11px;color:var(--orange);">{{ $pendingMembers }} awaiting approval</span>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:flex-end;">
      <button class="btn bo bsm" onclick="loadReport('pending')" title="Reload the list to pick up any new pending members">
        <i class="fas fa-sync-alt"></i> Refresh
      </button>
    </div>
    <div id="pen_stats" class="rpt-stats" style="display:none;"></div>
    <div id="pen_body" class="rpt-body">@include('unit-admin.partials.report-initial')</div>
    <div id="pen_footer" class="rpt-footer" style="display:none;">
      <span style="font-size:11px;color:var(--text-dim);"><i class="fas fa-info-circle"></i> Pending members only</span>
      <button class="btn bp" onclick="downloadReport('pending')"><i class="fas fa-download"></i> Download CSV</button>
    </div>
  </div>
</div>

{{-- ── 3. MONTHLY REPORT ── --}}
<div class="rpt-section" id="rpt-monthly" style="display:none;">
  <div class="card">
    <div class="ch"><div class="ct"><i class="fas fa-calendar-alt"></i> Monthly Registration Report</div></div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div class="fg" style="margin:0;min-width:100px;flex:1;">
        <label>Month</label>
        <select id="mon_month">
          <option value="">All Months</option>
          @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mi => $mn)
            <option value="{{ $mi+1 }}">{{ $mn }}</option>
          @endforeach
        </select>
      </div>
      <div class="fg" style="margin:0;min-width:90px;flex:1;">
        <label>Year</label>
        <select id="mon_year">
          <option value="">All Years</option>
          @for($y = now()->year; $y >= now()->year-4; $y--)<option value="{{ $y }}">{{ $y }}</option>@endfor
        </select>
      </div>
      <div class="fg" style="margin:0;min-width:110px;flex:1;">
        <label>Status</label>
        <select id="mon_status">
          <option value="">All</option>
          <option value="PENDING">Pending</option>
          <option value="APPROVED">Approved</option>
          <option value="ACTIVE">Active</option>
          <option value="REJECTED">Rejected</option>
        </select>
      </div>
      <div class="fg" style="margin:0;min-width:120px;"><label>From Date</label><input type="date" id="mon_from"></div>
      <div class="fg" style="margin:0;min-width:120px;"><label>To Date</label><input type="date" id="mon_to"></div>
      <div style="display:flex;gap:6px;">
        <button class="btn bp" onclick="loadReport('monthly')"><i class="fas fa-search"></i> Search</button>
        <button class="btn bo" onclick="resetReport('monthly')"><i class="fas fa-times"></i></button>
      </div>
    </div>

    <div style="padding:14px 18px;border-bottom:1px solid var(--border);height:180px;position:relative;">
      <canvas id="monthlyMiniChart"></canvas>
    </div>

    <div id="mon_stats" class="rpt-stats" style="display:none;"></div>
    <div id="mon_body" class="rpt-body">@include('unit-admin.partials.report-initial')</div>
    <div id="mon_footer" class="rpt-footer" style="display:none;">
      <span style="font-size:11px;color:var(--text-dim);"><i class="fas fa-info-circle"></i> Monthly registration results</span>
      <button class="btn bp" onclick="downloadReport('monthly')"><i class="fas fa-download"></i> Download CSV</button>
    </div>
  </div>
</div>

{{-- ── 4. FUNDRAISING REPORT ── --}}
<div class="rpt-section" id="rpt-fundraising" style="display:none;">
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-hand-holding-heart"></i> Fundraising Overview</div>
      <a href="{{ route('unit-admin.fundraising') }}" class="btn bo bsm">
        <i class="fas fa-arrow-right"></i> Manage Campaigns
      </a>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
      <div style="flex:1;min-width:130px;background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Total Campaigns</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--green);">{{ $fundraisingTotal }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(79,142,247,0.06);border:1px solid rgba(79,142,247,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Active</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--accent);">{{ $fundraisingActive }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(245,200,66,0.06);border:1px solid rgba(245,200,66,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Completed</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--gold);">{{ $fundraisingCompleted }}</div>
      </div>
      <div style="flex:1;min-width:170px;background:rgba(245,200,66,0.06);border:1px solid rgba(245,200,66,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Raised / Target</div>
        <div style="font-family:'Instrument Serif',serif;font-size:22px;color:var(--gold);">
          ₹{{ number_format($fundraisingRaised) }} / ₹{{ number_format($fundraisingTarget) }}
        </div>
      </div>
    </div>
    <div class="tw">
      <table>
        <thead><tr><th>Campaign</th><th>Raised / Target</th><th>Progress</th><th>Status</th><th>End Date</th></tr></thead>
        <tbody>
          @forelse($fundraisingCampaigns as $c)
            @php $pct = $c->target_amount > 0 ? min(100, round(($c->raised_amount/$c->target_amount)*100)) : 0; @endphp
            <tr>
              <td class="mn">{{ $c->title }}</td>
              <td style="color:var(--text-dim)">₹{{ number_format($c->raised_amount) }} / ₹{{ number_format($c->target_amount) }}</td>
              <td style="color:var(--text-dim)">{{ $pct }}%</td>
              <td><span class="badge {{ $c->status === 'COMPLETED' ? 'bap' : ($c->status === 'CANCELLED' ? 'brj' : 'bpe') }}">{{ $c->status }}</span></td>
              <td style="color:var(--text-dim)">{{ $c->end_date?->format('d M Y') ?? '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--text-dim);">No campaigns yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ── 5. COMMITTEES REPORT ── --}}
<div class="rpt-section" id="rpt-committees" style="display:none;">
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-users-rectangle"></i> Committees Overview</div>
      <a href="{{ route('unit-admin.committees') }}" class="btn bo bsm">
        <i class="fas fa-arrow-right"></i> Manage Committees
      </a>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
      <div style="flex:1;min-width:130px;background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Total Committees</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--green);">{{ $committeeTotal }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(79,142,247,0.06);border:1px solid rgba(79,142,247,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Active</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--accent);">{{ $committeeActive }}</div>
      </div>
    </div>
    <div class="tw">
      <table>
        <thead><tr><th>Committee</th><th>Members</th><th>Start – End</th><th>Status</th></tr></thead>
        <tbody>
          @forelse($committeeList as $c)
            <tr>
              <td class="mn">{{ $c->name }}</td>
              <td style="color:var(--text-dim)">{{ $c->members_count }}</td>
              <td style="color:var(--text-dim)">
                {{ $c->start_date->format('d M Y') }}
                @if($c->end_date) – {{ $c->end_date->format('d M Y') }} @endif
              </td>
              <td><span class="badge {{ $c->is_active ? 'bap' : 'brj' }}">{{ $c->is_active ? 'ACTIVE' : 'INACTIVE' }}</span></td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:var(--text-dim);">No committees yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ── 6. OTHER FEES REPORT ── --}}
<div class="rpt-section" id="rpt-otherfees" style="display:none;">
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-receipt"></i> Other Fees Overview</div>
      <a href="{{ route('unit-admin.other-fees') }}" class="btn bo bsm">
        <i class="fas fa-arrow-right"></i> Manage Fees
      </a>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
      <div style="flex:1;min-width:130px;background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Total Fees</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--green);">{{ $otherFeeTotal }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(79,142,247,0.06);border:1px solid rgba(79,142,247,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Active</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--accent);">{{ $otherFeeActive }}</div>
      </div>
      <div style="flex:1;min-width:150px;background:rgba(245,200,66,0.06);border:1px solid rgba(245,200,66,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Collected (your unit)</div>
        <div style="font-family:'Instrument Serif',serif;font-size:24px;color:var(--gold);">₹{{ number_format($otherFeeCollected) }}</div>
      </div>
    </div>
    <div class="tw">
      <table>
        <thead><tr><th>Fee</th><th>Amount</th><th>Payers</th><th>Due Date</th><th>Status</th></tr></thead>
        <tbody>
          @forelse($otherFeeList as $f)
            <tr>
              <td class="mn">{{ $f->title }}</td>
              <td style="color:var(--text-dim)">₹{{ number_format($f->amount, 2) }}</td>
              <td><a href="{{ route('unit-admin.other-fees.payers', $f->id) }}" class="ca">{{ $f->payments_count }}</a></td>
              <td style="color:var(--text-dim)">{{ $f->due_date?->format('d M Y') ?? '—' }}</td>
              <td><span class="badge {{ $f->is_active ? 'bap' : 'brj' }}">{{ $f->is_active ? 'ACTIVE' : 'INACTIVE' }}</span></td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--text-dim);">No fees yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ── 7. SCHEMES REPORT ── --}}
<div class="rpt-section" id="rpt-schemes" style="display:none;">
  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-piggy-bank"></i> Schemes Overview</div>
      <a href="{{ route('unit-admin.schemes') }}" class="btn bo bsm">
        <i class="fas fa-arrow-right"></i> Manage Schemes
      </a>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:10px;flex-wrap:wrap;">
      <div style="flex:1;min-width:130px;background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Total Schemes</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--green);">{{ $schemeTotal }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(79,142,247,0.06);border:1px solid rgba(79,142,247,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Active</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--accent);">{{ $schemeActive }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(52,211,153,0.06);border:1px solid rgba(52,211,153,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Dues Paid</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--green);">{{ $schemeDuesPaid }}</div>
      </div>
      <div style="flex:1;min-width:130px;background:rgba(251,146,60,0.06);border:1px solid rgba(251,146,60,0.2);border-radius:8px;padding:14px;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;font-weight:700;margin-bottom:4px;">Dues Pending</div>
        <div style="font-family:'Instrument Serif',serif;font-size:26px;color:var(--orange);">{{ $schemeDuesPending }}</div>
      </div>
    </div>
    <div class="tw">
      <table>
        <thead><tr><th>Scheme</th><th>Amount / Month</th><th>Members</th><th>Dues</th><th>Status</th></tr></thead>
        <tbody>
          @forelse($schemeList as $s)
            <tr>
              <td class="mn">{{ $s->title }}</td>
              <td style="color:var(--text-dim)">₹{{ number_format($s->amount, 2) }}</td>
              <td style="color:var(--text-dim)">{{ $s->members_count }}</td>
              <td><a href="{{ route('unit-admin.schemes.payers', $s->id) }}" class="ca">{{ $s->dues_count }}</a></td>
              <td><span class="badge {{ $s->is_active ? 'bap' : 'brj' }}">{{ $s->is_active ? 'ACTIVE' : 'INACTIVE' }}</span></td>
            </tr>
          @empty
            <tr><td colspan="5" style="text-align:center;color:var(--text-dim);">No schemes yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- ══ STYLES ══ --}}
<style>
.rpt-btn {
  display:inline-flex;align-items:center;gap:7px;
  padding:9px 16px;border-radius:8px;
  font-size:12px;font-weight:600;cursor:pointer;
  background:rgba(255,255,255,0.04);
  border:1px solid var(--border);
  color:var(--text-dim);
  font-family:'DM Sans',sans-serif;
  transition:all 0.2s;
}
.rpt-btn:hover { background:rgba(79,142,247,0.1); border-color:rgba(79,142,247,0.3); color:var(--accent-2); }
.rpt-btn.active { background:rgba(79,142,247,0.15); border-color:var(--accent); color:var(--accent-2); }
.rpt-stats { padding:10px 18px; background:rgba(79,142,247,0.05); border-bottom:1px solid var(--border); display:flex;gap:16px;flex-wrap:wrap;align-items:center; }
.rpt-footer { padding:12px 18px; border-top:1px solid var(--border); display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px; }
.av2 { width: 38px; height: 38px; border-radius: 50%; overflow: hidden; flex-shrink: 0; }
</style>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8896b3';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

const monthlyRaw = @json($monthlyData);
const monthlyPayRaw = @json($monthlyRevenue);
const monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const labels6 = [], counts6 = [], pays6 = [];
for (let i = 5; i >= 0; i--) {
  const d = new Date(); d.setMonth(d.getMonth() - i);
  labels6.push(monthNames[d.getMonth()] + ' ' + d.getFullYear());
  const r = monthlyRaw.find(r => r.month===d.getMonth()+1 && r.year===d.getFullYear());
  counts6.push(r ? r.count : 0);
  const p = monthlyPayRaw.find(r => r.month===d.getMonth()+1 && r.year===d.getFullYear());
  pays6.push(p ? parseFloat(p.total) : 0);
}
new Chart(document.getElementById('monthlyMiniChart'), {
  type: 'bar',
  data: {
    labels: labels6,
    datasets: [
      { label: 'Members', data: counts6, backgroundColor: 'rgba(79,142,247,0.25)', borderColor: '#4f8ef7', borderWidth:2, borderRadius:3, yAxisID:'yL' },
      { label: 'Revenue (₹)', data: pays6, backgroundColor: 'rgba(245,200,66,0.2)', borderColor: '#f5c842', borderWidth:2, borderRadius:3, yAxisID:'yR' }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins: { legend: { display:true, position:'top', align:'end', labels:{ font:{size:10}, boxWidth:10 } } },
    scales: {
      x: { grid:{color:'rgba(255,255,255,0.04)'}, ticks:{font:{size:9}} },
      yL: { position:'left', grid:{color:'rgba(255,255,255,0.04)'}, beginAtZero:true, ticks:{stepSize:1,font:{size:9}} },
      yR: { position:'right', grid:{drawOnChartArea:false}, beginAtZero:true, ticks:{callback:v=>'₹'+Number(v).toLocaleString('en-IN'),font:{size:9}} }
    }
  }
});

const reportConfig = {
  status:  { prefix:'sts',  fixedStatus: null },
  pending: { prefix:'pen',  fixedStatus: 'PENDING' },
  monthly: { prefix:'mon',  fixedStatus: null },
};

// ── Tracks which AJAX-backed report tabs have already fetched data,
//    so switching tabs auto-loads results the first time without
//    requiring the admin to click Search manually. ──
const loadedSections = new Set();

function showReport(name) {
  document.querySelectorAll('.rpt-section').forEach(el => el.style.display = 'none');
  document.querySelectorAll('.rpt-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('rpt-' + name).style.display = 'block';
  document.getElementById('btn-' + name).classList.add('active');
  document.getElementById('rpt-' + name).scrollIntoView({ behavior:'smooth', block:'start' });

  if (reportConfig[name] && !loadedSections.has(name)) {
    loadedSections.add(name);
    loadReport(name);
  }
}

function getReportFilters(name) {
  const cfg = reportConfig[name];
  const get = id => { const el = document.getElementById(id); return el ? el.value : ''; };
  const f = {};
  if (cfg.fixedStatus) f.status = cfg.fixedStatus;

  if (name === 'status')  { f.status=get('sts_status'); f.date_from=get('sts_from'); f.date_to=get('sts_to'); }
  if (name === 'monthly') { f.month=get('mon_month'); f.year=get('mon_year'); f.status=get('mon_status'); f.date_from=get('mon_from'); f.date_to=get('mon_to'); }

  return f;
}

function loadReport(name) {
  const f = getReportFilters(name);
  const params = new URLSearchParams();
  Object.entries(f).forEach(([k,v]) => { if (v) params.append(k,v); });

  const pre = reportConfig[name].prefix;
  const bEl = document.getElementById(pre+'_body');
  const sEl = document.getElementById(pre+'_stats');
  const fEl = document.getElementById(pre+'_footer');

  sEl.style.display = 'none';
  fEl.style.display = 'none';
  bEl.innerHTML = '<div style="padding:28px;text-align:center;color:var(--text-dim);"><i class="fas fa-spinner fa-spin" style="font-size:18px;"></i></div>';

  fetch(`{{ url('/unit-admin/reports/preview') }}?${params}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' }
  })
    .then(r => r.json())
    .then(data => {
      if (!data.members || !data.members.length) {
        bEl.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-dim);"><i class="fas fa-search" style="font-size:24px;margin-bottom:10px;opacity:0.4;display:block;"></i><div style="font-size:13px;font-weight:600;">No members found</div><div style="font-size:11px;margin-top:4px;">Try adjusting your filters</div></div>';
        return;
      }

      sEl.style.display = 'flex';
      sEl.innerHTML = `
        <span style="font-size:12px;color:var(--text-dim);">
          <i class="fas fa-users" style="color:var(--accent);"></i>
          Found <b style="color:var(--accent);font-size:14px;">${data.count}</b> members
        </span>
        <div style="flex:1;display:flex;gap:5px;flex-wrap:wrap;">
          ${Object.entries(f).filter(([,v])=>v).map(([k,v])=>`<span style="background:rgba(79,142,247,0.12);color:var(--accent);border:1px solid rgba(79,142,247,0.2);border-radius:20px;padding:2px 10px;font-size:10px;font-weight:600;">${k.replace('_',' ')}: ${v}</span>`).join('')}
        </div>`;

      fEl.style.display = 'flex';

      const sc = { PENDING:'rgba(251,146,60,0.15)',APPROVED:'rgba(52,211,153,0.15)',ACTIVE:'rgba(79,142,247,0.15)',REJECTED:'rgba(248,113,113,0.15)' };
      const tc = { PENDING:'var(--orange)',APPROVED:'var(--green)',ACTIVE:'var(--accent)',REJECTED:'var(--red)' };

      bEl.innerHTML = `
        <div class="tw">
          <table>
            <thead>
              <tr>
                <th>#</th><th>Member</th><th>Phone</th>
                <th class="hide-mobile">Organization</th>
                <th>Status</th><th>Registered</th>
              </tr>
            </thead>
            <tbody>
              ${data.members.map((m,i) => `
                <tr>
                  <td style="color:var(--text-dim)">${i+1}</td>
                  <td>
                    <div class="mc2">
                      <div class="av2">
  ${m.profile_image
    ? `<img src="${m.profile_image}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`
    : `<span style="background:rgba(79,142,247,0.2);color:var(--accent);display:flex;align-items:center;justify-content:center;width:100%;height:100%;border-radius:50%;">
        ${m.name.substring(0,2).toUpperCase()}
      </span>`
  }
</div>
                      <div><div class="mn">${m.name}</div><div class="me">${m.email}</div></div>
                    </div>
                  </td>
                  <td style="color:var(--text-dim)">${m.phone}</td>
                  <td class="hide-mobile" style="color:var(--text-dim)">${m.organization??'—'}</td>
                  <td><span style="background:${sc[m.status]??'rgba(255,255,255,0.05)'};color:${tc[m.status]??'var(--text-dim)'};padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600;">${m.status}</span></td>
                  <td style="color:var(--text-dim)">${m.registered_on}</td>
                </tr>`).join('')}
            </tbody>
          </table>
        </div>`;
    })
    .catch(() => {
      bEl.innerHTML = '<div style="padding:24px;text-align:center;color:var(--red);">Failed to load report.</div>';
    });
}

function resetReport(name) {
  const pre = reportConfig[name].prefix;
  document.getElementById(pre+'_stats').style.display = 'none';
  document.getElementById(pre+'_footer').style.display = 'none';
  document.getElementById(pre+'_body').innerHTML = `
    <div style="padding:36px;text-align:center;color:var(--text-dim);">
      <i class="fas fa-filter" style="font-size:28px;margin-bottom:10px;opacity:0.3;display:block;"></i>
      <div style="font-size:13px;font-weight:600;margin-bottom:4px;">Set filters and click Search</div>
      <div style="font-size:11px;">Results will appear here</div>
    </div>`;
  document.querySelectorAll(`[id^="${pre}_"]`).forEach(el => {
    if (el.tagName === 'SELECT') el.value = '';
    if (el.tagName === 'INPUT')  el.value = '';
  });
}

function downloadReport(name) {
  const f = getReportFilters(name);
  const params = new URLSearchParams();
  Object.entries(f).forEach(([k,v]) => { if (v) params.append(k,v); });
  window.location.href = `{{ url('/unit-admin/reports/download') }}?${params}`;
}

// ── Auto-load the default tab's data on page load, since "Member by Status"
//    is shown active immediately but its body starts as an empty placeholder. ──
document.addEventListener('DOMContentLoaded', function () {
  loadReport('status');
  loadedSections.add('status');
});
</script>
@endsection