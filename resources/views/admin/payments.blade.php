@extends('layouts.admin')
@section('title', 'Payments')
@section('page_title', 'Payments')

@section('content')

{{-- ══ STAT CARDS ══ --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:18px;">

  <div class="sc" style="--ca:var(--gold);">
    <div class="st">
      <div class="si" style="background:rgba(245,200,66,0.15);">
        <i class="fas fa-indian-rupee-sign" style="color:var(--gold);font-size:14px;"></i>
      </div>
      <span class="str" style="background:rgba(245,200,66,0.15);color:var(--gold);">● Total</span>
    </div>
    <div class="sv" style="font-size:22px;">₹{{ number_format($totalRevenue) }}</div>
    <div class="sl2">Total Revenue</div>
  </div>

  <div class="sc" style="--ca:var(--accent);">
    <div class="st">
      <div class="si" style="background:rgba(79,142,247,0.15);">
        <i class="fas fa-id-card" style="color:var(--accent);font-size:14px;"></i>
      </div>
      <span class="str tu">● Membership</span>
    </div>
    <div class="sv" style="font-size:22px;">₹{{ number_format($memberRevenue) }}</div>
    <div class="sl2">Membership Revenue</div>
  </div>

  <div class="sc" style="--ca:var(--purple);">
    <div class="st">
      <div class="si" style="background:rgba(167,139,250,0.15);">
        <i class="fas fa-calendar-alt" style="color:var(--purple);font-size:14px;"></i>
      </div>
      <span class="str" style="background:rgba(167,139,250,0.15);color:var(--purple);">● Events</span>
    </div>
    <div class="sv" style="font-size:22px;">₹{{ number_format($eventRevenue) }}</div>
    <div class="sl2">Event Revenue</div>
  </div>

  <div class="sc" style="--ca:var(--orange);">
    <div class="st">
      <div class="si" style="background:rgba(251,146,60,0.15);">
        <i class="fas fa-rotate" style="color:var(--orange);font-size:14px;"></i>
      </div>
      <span class="str" style="background:rgba(251,146,60,0.15);color:var(--orange);">● Status</span>
    </div>
    <div style="display:flex;gap:12px;margin-top:6px;">
      <div>
        <div style="font-family:'Instrument Serif',serif;font-size:20px;color:var(--green);">{{ $totalCount }}</div>
        <div style="font-size:10px;color:var(--text-dim);">Success</div>
      </div>
      <div>
        <div style="font-family:'Instrument Serif',serif;font-size:20px;color:var(--orange);">{{ $pendingCount }}</div>
        <div style="font-size:10px;color:var(--text-dim);">Pending</div>
      </div>
      <div>
        <div style="font-family:'Instrument Serif',serif;font-size:20px;color:var(--red);">{{ $failedCount }}</div>
        <div style="font-size:10px;color:var(--text-dim);">Failed</div>
      </div>
    </div>
  </div>

</div>

{{-- ══ FILTER + TABLE ══ --}}
<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-credit-card"></i> All Payments</div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <span id="resultCount" style="font-size:11px;color:var(--text-dim);"></span>
      <button class="btn bo bsm" onclick="toggleFilters()">
        <i class="fas fa-filter"></i> Filters
      </button>
      <button class="btn bo bsm" onclick="downloadCsv()">
        <i class="fas fa-download"></i> Export CSV
      </button>
    </div>
  </div>

  {{-- FILTERS --}}
  <div id="filterBar" style="display:none;padding:12px 18px;border-bottom:1px solid var(--border);
       background:rgba(255,255,255,0.02);">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
      <div class="fg" style="margin:0;min-width:110px;flex:1;">
        <label>Status</label>
        <select id="fStatus" onchange="applyFilters()">
          <option value="">All Status</option>
          <option value="SUCCESS">Success</option>
          <option value="PENDING">Pending</option>
          <option value="FAILED">Failed</option>
        </select>
      </div>
      <div class="fg" style="margin:0;min-width:110px;flex:1;">
        <label>Type</label>
        <select id="fType" onchange="applyFilters()">
          <option value="">All Types</option>
          <option value="MEMBERSHIP">Membership</option>
          <option value="EVENT">Event</option>
          <option value="RENEWAL">Renewal</option>
        </select>
      </div>
      <div class="fg" style="margin:0;min-width:130px;">
        <label>From Date</label>
        <input type="date" id="fDateFrom" onchange="applyFilters()">
      </div>
      <div class="fg" style="margin:0;min-width:130px;">
        <label>To Date</label>
        <input type="date" id="fDateTo" onchange="applyFilters()">
      </div>
      <div class="fg" style="margin:0;min-width:150px;flex:2;">
        <label>Search</label>
        <input type="text" id="fSearch" placeholder="Name, email, txn ID, membership ID..."
               oninput="applyFilters()">
      </div>
      <button class="btn bo bsm" onclick="clearFilters()">
        <i class="fas fa-times"></i> Clear
      </button>
    </div>
  </div>

  {{-- QUICK CHIPS --}}
  <div style="padding:10px 18px;border-bottom:1px solid var(--border);
              display:flex;gap:6px;flex-wrap:wrap;">
    <button class="qchip active" onclick="setChip(this,'','')">All</button>
    <button class="qchip" onclick="setChip(this,'fStatus','SUCCESS')">Success</button>
    <button class="qchip" onclick="setChip(this,'fStatus','PENDING')">Pending</button>
    <button class="qchip" onclick="setChip(this,'fStatus','FAILED')">Failed</button>
    <button class="qchip" onclick="setChip(this,'fType','MEMBERSHIP')">Membership</button>
    <button class="qchip" onclick="setChip(this,'fType','EVENT')">Events</button>
    <button class="qchip" onclick="setChip(this,'fType','RENEWAL')">Renewals</button>
  </div>

  {{-- TABLE --}}
  <div class="tw">
    <table id="paymentsTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Member</th>
          <th>Plan / Event</th>
          <th>Type</th>
          <th>Amount</th>
          <th>Transaction ID</th>
          <th>Membership ID</th>
          <th>Status</th>
          <th>Paid At</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tableBody">
        @forelse($payments as $p)
        <tr class="pay-row"
            data-status="{{ $p->status }}"
            data-type="{{ $p->payment_type ?? 'MEMBERSHIP' }}"
            data-search="{{ strtolower($p->user->name . ' ' . $p->user->email . ' ' . ($p->transaction_id ?? '') . ' ' . ($p->membership_id ?? '')) }}"
            data-date="{{ $p->created_at->format('Y-m-d') }}">
          <td style="color:var(--text-dim);">{{ $loop->iteration }}</td>
          <td>
            <div class="mc2">
              {{-- ── PHOTO with initials fallback ── --}}
              @if($p->memberProfile?->photo)
                <img src="{{ asset('storage/' . $p->memberProfile->photo) }}"
                     alt="{{ $p->user->name }}"
                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;
                            flex-shrink:0;border:1.5px solid rgba(79,142,247,0.25);"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="av2" style="display:none;background:rgba(79,142,247,0.15);color:var(--accent-2);">
                  {{ strtoupper(substr($p->user->name, 0, 2)) }}
                </div>
              @else
                <div class="av2" style="background:rgba(79,142,247,0.15);color:var(--accent-2);">
                  {{ strtoupper(substr($p->user->name, 0, 2)) }}
                </div>
              @endif
              <div>
                <div class="mn">{{ $p->user->name }}</div>
                <div class="me">{{ $p->user->email }}</div>
              </div>
            </div>
          </td>
          <td>
            <div class="mn">{{ $p->plan->name ?? '—' }}</div>
            <div class="me">{{ $p->memberProfile->unit->name ?? '—' }}</div>
          </td>
          <td>
            @php $pt = $p->payment_type ?? 'MEMBERSHIP'; @endphp
            <span class="badge"
              style="background:{{ $pt==='EVENT' ? 'rgba(167,139,250,0.15)' : ($pt==='RENEWAL' ? 'rgba(251,146,60,0.15)' : 'rgba(79,142,247,0.15)') }};
                     color:{{ $pt==='EVENT' ? 'var(--purple)' : ($pt==='RENEWAL' ? 'var(--orange)' : 'var(--accent-2)') }};">
              {{ $pt }}
            </span>
          </td>
          <td style="color:var(--gold);font-weight:600;">
            ₹{{ number_format($p->amount, 0) }}
          </td>
          <td>
            <span style="font-family:monospace;font-size:10px;color:var(--text-dim);">
              {{ $p->transaction_id ?? '—' }}
            </span>
          </td>
          <td>
            @if($p->membership_id)
              <span style="font-family:monospace;font-size:10px;
                           background:rgba(245,200,66,0.1);color:var(--gold);
                           padding:2px 7px;border-radius:4px;">
                {{ $p->membership_id }}
              </span>
            @else
              <span style="color:var(--text-dim);">—</span>
            @endif
          </td>
          <td>
            <span class="badge
              {{ $p->status === 'SUCCESS' ? 'bap' : ($p->status === 'PENDING' ? 'bpe' : 'brj') }}">
              {{ $p->status }}
            </span>
          </td>
          <td style="color:var(--text-dim);font-size:11px;">
            {{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d M Y h:i A') : '—' }}
          </td>
          <td>
            <div class="ab vw" title="View Details"
                 onclick="viewPayment(
                   '{{ addslashes($p->user->name) }}',
                   '{{ $p->user->email }}',
                   '{{ $p->user->phone }}',
                   '{{ $p->plan->name ?? '—' }}',
                   '{{ $p->plan->type ?? '—' }}',
                   '{{ $p->payment_type ?? 'MEMBERSHIP' }}',
                   '{{ number_format($p->amount, 0) }}',
                   '{{ $p->currency ?? 'INR' }}',
                   '{{ $p->status }}',
                   '{{ $p->transaction_id ?? '—' }}',
                   '{{ $p->membership_id ?? '—' }}',
                   '{{ $p->memberProfile->unit->name ?? '—' }}',
                   '{{ $p->memberProfile->district->name ?? '—' }}',
                   '{{ $p->paid_at ? \Carbon\Carbon::parse($p->paid_at)->format('d M Y h:i A') : '—' }}',
                   '{{ $p->created_at->format('d M Y h:i A') }}',
                   '{{ addslashes($p->notes ?? '') }}',
                   '{{ $p->memberProfile?->photo ? asset('storage/' . $p->memberProfile->photo) : '' }}'
                 )">
              <i class="fas fa-eye"></i>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="10" style="text-align:center;color:var(--text-dim);padding:40px;">
            <div style="font-size:28px;margin-bottom:8px;">💳</div>
            <div style="font-size:13px;font-weight:600;">No payments yet</div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- TABLE FOOTER --}}
  <div id="tableFooter" style="padding:10px 18px;border-top:1px solid var(--border);
       display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
    <span id="footerCount" style="font-size:11px;color:var(--text-dim);"></span>
    <span id="footerAmount" style="font-size:12px;font-weight:600;color:var(--gold);"></span>
  </div>
</div>

{{-- ══ VIEW PAYMENT MODAL ══ --}}
<div id="viewPaymentM" class="mo">
  <div class="md" style="width:520px;">
    <div class="mh">
      <span class="mt2">Payment Detail</span>
      <div class="mc3" onclick="cm('viewPaymentM')"><i class="fas fa-times"></i></div>
    </div>
    <div style="padding:18px;">

      {{-- Member section --}}
      <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);
                  border-radius:10px;padding:14px;margin-bottom:12px;">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.8px;color:var(--accent);margin-bottom:10px;">
          Member Info
        </div>
        <div style="display:flex;gap:10px;align-items:center;margin-bottom:10px;">
          {{-- ── MODAL AVATAR — JS-controlled, supports photo ── --}}
          <div id="vAvatar" style="width:40px;height:40px;border-radius:10px;
               background:rgba(79,142,247,0.2);color:var(--accent);
               display:flex;align-items:center;justify-content:center;
               font-size:13px;font-weight:700;flex-shrink:0;overflow:hidden;"></div>
          <div>
            <div id="vName" style="font-size:14px;font-weight:600;"></div>
            <div id="vEmail" style="font-size:11px;color:var(--text-dim);"></div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Phone</div>
            <div id="vPhone" style="font-size:12px;"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Unit</div>
            <div id="vUnit" style="font-size:12px;"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">District</div>
            <div id="vDistrict" style="font-size:12px;"></div>
          </div>
        </div>
      </div>

      {{-- Payment section --}}
      <div style="background:rgba(255,255,255,0.03);border:1px solid var(--border);
                  border-radius:10px;padding:14px;margin-bottom:12px;">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;
                    letter-spacing:0.8px;color:var(--gold);margin-bottom:10px;">
          Payment Info
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Amount</div>
            <div id="vAmount" style="font-size:18px;font-family:'Instrument Serif',serif;color:var(--gold);"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Status</div>
            <div id="vStatus" style="margin-top:3px;"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Plan</div>
            <div id="vPlan" style="font-size:12px;"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Type</div>
            <div id="vType" style="font-size:12px;"></div>
          </div>
          <div style="grid-column:span 2;">
            <div style="font-size:10px;color:var(--text-dim);">Transaction ID</div>
            <div id="vTxn" style="font-size:11px;font-family:monospace;color:var(--text-dim);word-break:break-all;"></div>
          </div>
          <div style="grid-column:span 2;">
            <div style="font-size:10px;color:var(--text-dim);">Membership ID</div>
            <div id="vMid" style="font-size:12px;font-family:monospace;color:var(--gold);"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Paid At</div>
            <div id="vPaidAt" style="font-size:11px;color:var(--text-dim);"></div>
          </div>
          <div>
            <div style="font-size:10px;color:var(--text-dim);">Created At</div>
            <div id="vCreatedAt" style="font-size:11px;color:var(--text-dim);"></div>
          </div>
        </div>
      </div>

      {{-- Notes --}}
      <div id="vNotesWrap" style="display:none;background:rgba(255,255,255,0.02);
           border:1px solid var(--border);border-radius:8px;padding:10px 12px;">
        <div style="font-size:10px;color:var(--text-dim);margin-bottom:4px;">Notes</div>
        <div id="vNotes" style="font-size:12px;color:var(--text-dim);"></div>
      </div>
    </div>
    <div class="ff">
      <button class="btn bo" onclick="cm('viewPaymentM')">Close</button>
    </div>
  </div>
</div>

<style>
.qchip{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;
  border:1px solid var(--border);background:var(--card);color:var(--text-dim);
  font-family:'DM Sans',sans-serif;transition:all 0.2s;}
.qchip:hover,.qchip.active{background:rgba(79,142,247,0.15);color:var(--accent-2);border-color:rgba(79,142,247,0.3);}
</style>

@endsection

@section('scripts')
<script>
const allRows    = Array.from(document.querySelectorAll('.pay-row'));
let activeFilters = { status:'', type:'', search:'', dateFrom:'', dateTo:'' };

function toggleFilters() {
  const bar = document.getElementById('filterBar');
  bar.style.display = bar.style.display === 'none' ? 'block' : 'none';
}

function setChip(el, field, val) {
  document.querySelectorAll('.qchip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
  if (field === 'fStatus') {
    document.getElementById('fStatus').value = val;
    activeFilters.status = val;
    activeFilters.type   = '';
    document.getElementById('fType').value = '';
  } else if (field === 'fType') {
    document.getElementById('fType').value = val;
    activeFilters.type   = val;
    activeFilters.status = '';
    document.getElementById('fStatus').value = '';
  } else {
    activeFilters.status = '';
    activeFilters.type   = '';
    document.getElementById('fStatus').value = '';
    document.getElementById('fType').value   = '';
  }
  renderTable();
}

function applyFilters() {
  activeFilters.status   = document.getElementById('fStatus').value;
  activeFilters.type     = document.getElementById('fType').value;
  activeFilters.search   = document.getElementById('fSearch').value.toLowerCase().trim();
  activeFilters.dateFrom = document.getElementById('fDateFrom').value;
  activeFilters.dateTo   = document.getElementById('fDateTo').value;
  renderTable();
}

function clearFilters() {
  ['fStatus','fType','fSearch','fDateFrom','fDateTo'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
  activeFilters = { status:'', type:'', search:'', dateFrom:'', dateTo:'' };
  document.querySelectorAll('.qchip').forEach(c => c.classList.remove('active'));
  document.querySelector('.qchip').classList.add('active');
  renderTable();
}

function renderTable() {
  let visible = 0;
  let totalAmount = 0;

  allRows.forEach(row => {
    const status   = row.dataset.status;
    const type     = row.dataset.type;
    const search   = row.dataset.search;
    const date     = row.dataset.date;
    const amount   = parseFloat(row.querySelector('td:nth-child(5)').textContent.replace(/[^0-9.]/g,'')) || 0;

    let show = true;
    if (activeFilters.status && status !== activeFilters.status)             show = false;
    if (activeFilters.type   && type   !== activeFilters.type)               show = false;
    if (activeFilters.search && !search.includes(activeFilters.search))      show = false;
    if (activeFilters.dateFrom && date < activeFilters.dateFrom)             show = false;
    if (activeFilters.dateTo   && date > activeFilters.dateTo)               show = false;

    row.style.display = show ? '' : 'none';
    if (show) {
      visible++;
      if (status === 'SUCCESS') totalAmount += amount;
    }
  });

  document.getElementById('resultCount').textContent = visible + ' results';
  document.getElementById('footerCount').textContent = visible + ' payments shown';
  document.getElementById('footerAmount').textContent = 'Shown revenue: ₹' + totalAmount.toLocaleString('en-IN');
}

// ── photo is the new 17th argument ──
function viewPayment(name, email, phone, planName, planType, payType, amount, currency,
                     status, txnId, midId, unit, district, paidAt, createdAt, notes, photo) {

  // ── Avatar: show photo if available, else initials ──
  const avatarEl = document.getElementById('vAvatar');
  if (photo) {
    avatarEl.innerHTML = `<img src="${photo}" alt="${name}"
      style="width:40px;height:40px;border-radius:10px;object-fit:cover;display:block;"
      onerror="this.parentElement.innerHTML='${name.substring(0,2).toUpperCase()}';
               this.parentElement.style.background='rgba(79,142,247,0.2)';">`;
    avatarEl.style.background = 'transparent';
    avatarEl.style.padding    = '0';
  } else {
    avatarEl.textContent      = name.substring(0,2).toUpperCase();
    avatarEl.style.background = 'rgba(79,142,247,0.2)';
  }

  document.getElementById('vName').textContent     = name;
  document.getElementById('vEmail').textContent    = email;
  document.getElementById('vPhone').textContent    = phone;
  document.getElementById('vUnit').textContent     = unit;
  document.getElementById('vDistrict').textContent = district;
  document.getElementById('vAmount').textContent   = '₹' + amount;
  document.getElementById('vPlan').textContent     = planName + ' (' + planType + ')';
  document.getElementById('vType').textContent     = payType;
  document.getElementById('vTxn').textContent      = txnId;
  document.getElementById('vMid').textContent      = midId;
  document.getElementById('vPaidAt').textContent   = paidAt;
  document.getElementById('vCreatedAt').textContent= createdAt;

  const sc = { SUCCESS:'bap', PENDING:'bpe', FAILED:'brj' };
  document.getElementById('vStatus').innerHTML =
    `<span class="badge ${sc[status]||'bac'}">${status}</span>`;

  if (notes && notes.trim()) {
    document.getElementById('vNotes').textContent    = notes;
    document.getElementById('vNotesWrap').style.display = 'block';
  } else {
    document.getElementById('vNotesWrap').style.display = 'none';
  }

  om('viewPaymentM');
}

function downloadCsv() {
  const visibleRows = allRows.filter(r => r.style.display !== 'none');
  const headers = ['#','Name','Email','Plan','Type','Amount','Transaction ID','Membership ID','Status','Paid At'];
  const rows = visibleRows.map((r, i) => {
    const cells = r.querySelectorAll('td');
    return [
      i+1,
      r.querySelector('.mn')?.textContent?.trim() ?? '',
      r.querySelector('.me')?.textContent?.trim() ?? '',
      cells[2]?.querySelector('.mn')?.textContent?.trim() ?? '',
      cells[3]?.textContent?.trim() ?? '',
      cells[4]?.textContent?.trim() ?? '',
      cells[5]?.textContent?.trim() ?? '',
      cells[6]?.textContent?.trim() ?? '',
      cells[7]?.textContent?.trim() ?? '',
      cells[8]?.textContent?.trim() ?? '',
    ].map(v => '"' + String(v).replace(/"/g,'""') + '"').join(',');
  });

  const csv  = [headers.join(','), ...rows].join('\n');
  const blob = new Blob([csv], { type:'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'eca_payments_' + new Date().toISOString().slice(0,10) + '.csv';
  a.click();
  URL.revokeObjectURL(url);
}

// Init
renderTable();
</script>
@endsection