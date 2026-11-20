@extends('layouts.district-admin')
@section('title', 'Members')
@section('page_title', 'Members')

@section('content')

{{-- FILTER BAR --}}
<div class="card">
  <form method="GET" action="{{ route('district-admin.members') }}">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:14px 18px;">
      <div class="fg" style="margin:0;flex:1;min-width:120px;">
        <label>Status</label>
        <select name="status">
          <option value="">All Status</option>
          <option value="PENDING"  {{ ($status ?? '') === 'PENDING'  ? 'selected' : '' }}>Pending</option>
          <option value="APPROVED" {{ ($status ?? '') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
          <option value="ACTIVE"   {{ ($status ?? '') === 'ACTIVE'   ? 'selected' : '' }}>Active</option>
          <option value="REJECTED" {{ ($status ?? '') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
          <option value="EXPIRED"  {{ ($status ?? '') === 'EXPIRED'  ? 'selected' : '' }}>Expired</option>
        </select>
      </div>
      <div class="fg" style="margin:0;flex:1;min-width:120px;">
        <label>Unit</label>
        <select name="unit_id">
          <option value="">All Units</option>
          @foreach($units as $unit)
            <option value="{{ $unit->id }}" {{ ($unit_id ?? '') == $unit->id ? 'selected' : '' }}>
              {{ $unit->name }}
            </option>
          @endforeach
        </select>
      </div>
      <div style="display:flex;gap:6px;">
        <button type="submit" class="btn bp bsm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('district-admin.members') }}" class="btn bo bsm"><i class="fas fa-times"></i> Clear</a>
      </div>
    </div>
  </form>
</div>

{{-- MEMBERS TABLE --}}
<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-users"></i>
      District Members
      <span style="font-size:10px;color:var(--text-dim);font-weight:400;">({{ $members->total() }})</span>
    </div>
    <button class="btn bp bsm" onclick="resetAddMemberModal(); om('addMemberM')">
      <i class="fas fa-plus"></i> Add Member
    </button>
  </div>

  @if($members->isEmpty())
    <div class="empty-state">
      <i class="fas fa-users"></i>
      <p>No members found</p>
      <button class="btn bp bsm" style="margin-top:10px;" onclick="resetAddMemberModal(); om('addMemberM')">
        <i class="fas fa-plus"></i> Add First Member
      </button>
    </div>
  @else
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>Member</th>
            <th>Phone</th>
            <th>Unit</th>
            <th>Membership ID</th>
            <th>Member Net</th>
            <th class="hide-mobile">Organization</th>
            <th>Status</th>
            <th class="hide-mobile">Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($members as $m)
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
                  <div class="me">{{ $m->user->email ?: 'No email' }}</div>
                </div>
              </div>
            </td>
            <td style="color:var(--text-dim);">{{ $m->user->phone }}</td>
            <td><span class="utag">{{ $m->unit->name ?? '—' }}</span></td>
                        <td style="color:var(--text-dim);white-space:nowrap;">{{ $m->membership_id ?? '—' }}</td>
            <td>
              @if($m->is_fumma_member)
                <span class="badge bap">Member Net</span>
              @else
                <span style="color:var(--text-dim);font-size:11px;">—</span>
              @endif
            </td>
            <td class="hide-mobile">{{ Str::limit($m->organization, 20) }}</td>
            <td>
              @if($m->status === 'PENDING')
                <span class="badge bpe">PENDING</span>
              @elseif($m->status === 'APPROVED')
                <span class="badge bap">APPROVED</span>
              @elseif($m->status === 'ACTIVE')
                <span class="badge bac">ACTIVE</span>
              @elseif($m->status === 'REJECTED')
                <span class="badge brj">REJECTED</span>
              @else
                <span class="badge bex">{{ $m->status }}</span>
              @endif
            </td>
            <td class="hide-mobile" style="color:var(--text-dim);">
              {{ $m->created_at->format('d M Y') }}
            </td>
            <td>
              <div class="abr">
                {{-- VIEW --}}
                <button type="button" class="ab vw" title="View Details"
                        onclick="viewMember({{ $m->id }})">
                  <i class="fas fa-eye"></i>
                </button>

                {{-- APPROVE --}}
                @if(in_array($m->status, ['PENDING', 'REJECTED']))
                  <form method="POST" action="{{ route('district-admin.members.approve') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="id" value="{{ $m->id }}">
                    <button type="submit" class="ab app" title="Approve"
                            onclick="return confirm({{ json_encode('Approve ' . $m->user->name . '?') }})">
                      <i class="fas fa-check"></i>
                    </button>
                  </form>
                @endif

                {{-- REJECT (opens modal so a reason can be entered) --}}
                @if(in_array($m->status, ['PENDING', 'APPROVED']))
                  <button type="button" class="ab rej" title="Reject"
                          onclick="openReject({{ $m->id }}, {{ json_encode($m->user->name) }})">
                    <i class="fas fa-times"></i>
                  </button>
                @endif

                {{-- DELETE (opens password-confirm modal) --}}
                <button type="button" class="ab del" title="Delete"
                        onclick="openDelete({{ $m->id }}, {{ json_encode($m->user->name) }})">
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- PAGINATION --}}
    @if($members->hasPages())
      <div style="padding:12px 16px;border-top:1px solid var(--border);
                  display:flex;align-items:center;justify-content:space-between;
                  flex-wrap:wrap;gap:8px;">
        <span style="font-size:11px;color:var(--text-dim);">
          Showing {{ $members->firstItem() }}–{{ $members->lastItem() }}
          of {{ $members->total() }} members
        </span>
        {{ $members->appends(request()->query())->links('pagination.custom') }}
      </div>
    @endif
  @endif
</div>


{{-- ══ ADD MEMBER MODAL ══ --}}
<div id="addMemberM" class="mo">
  <div class="md" style="width:580px">
    <div class="mh">
      <span class="mt2">
        <i class="fas fa-user-plus" style="color:var(--accent);margin-right:6px;"></i>
        Add New Member
      </span>
      <div class="mc3" onclick="resetAddMemberModal(); cm('addMemberM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.members.store') }}"
          enctype="multipart/form-data" autocomplete="off" id="addMemberForm">
      @csrf

      @if ($errors->any())
        <div id="addMemberError" style="margin:14px 18px 0;padding:10px 12px;
                    background:rgba(239,68,68,0.1);
                    border:1px solid rgba(239,68,68,0.3);
                    border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div style="padding:14px 18px 0;font-size:10px;font-weight:700;
                  text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Personal Info
      </div>
      <div class="fgrid">
        <div class="fg">
          <label>Full Name</label>
          <input type="text" name="name" id="addMemberName" maxlength="100"
                 placeholder="Rajesh Kumar" value="{{ old('name') }}" autocomplete="off"
                 pattern="[A-Za-z]+([\s'\-][A-Za-z]+)*"
                 title="Name can only contain letters, spaces, hyphens and apostrophes."
                 oninput="this.value=this.value.replace(/[^A-Za-z\s'\-]/g,'')"
                 required>
        </div>
        <div class="fg">
          <label>Email <span style="color:var(--text-dim);font-weight:400;">(optional)</span></label>
          <input type="email" name="email" id="addMemberEmail" maxlength="150"
                 placeholder="rajesh@gmail.com" value="{{ old('email') }}" autocomplete="off"
                 pattern="[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}"
                 title="Enter a valid email with a domain, e.g. name@example.com">
        </div>
        <div class="fg">
          <label>Phone</label>
          <input type="text" name="phone" id="addMemberPhone" placeholder="9845002001"
                 maxlength="10" value="{{ old('phone') }}" autocomplete="off"
                 oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                 required>
        </div>
        <div class="fg">
          <label>Date of Birth</label>
          <input type="date" name="dob" id="addMemberDob" value="{{ old('dob') }}" required>
        </div>
        <div class="fg">
          <label>Organization</label>
          <input type="text" name="organization" id="addMemberOrg" maxlength="150"
                 placeholder="Calicut University" value="{{ old('organization') }}"
                 autocomplete="off" required>
        </div>
        <div class="fg s2">
          <label>Address</label>
          <textarea name="address" id="addMemberAddress" maxlength="500" rows="2"
                    placeholder="House name, street, area, PIN"
                    autocomplete="off"
                    required>{{ old('address') }}</textarea>
        </div>
        <div class="fg s2">
          <label>Photo</label>
          <input type="file" name="photo" id="addMemberPhoto"
                 accept="image/jpg,image/jpeg,image/png"
                 style="padding:6px 11px;" required>
        </div>
      </div>

      <div style="padding:6px 18px 0;font-size:10px;font-weight:700;
                  text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
        Unit
      </div>
      <div class="fgrid c1">
        <div class="fg">
          <label>Unit</label>
          <select name="unit_id" id="addMemberUnit" required>
            <option value="">Select Unit</option>
            @foreach($units as $unit)
              <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                {{ $unit->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div style="margin:8px 18px 14px;padding:10px 12px;
            background:rgba(79,142,247,0.07);border-radius:8px;
            font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        Member will be added to your district
        <b style="color:var(--accent)">{{ optional($units->first()?->district)->name }}</b>
        and <b style="color:var(--green, #34d399)">approved immediately</b>.
        A Membership ID is generated, and the login details are emailed now (if an email is given).
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="resetAddMemberModal(); cm('addMemberM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-user-plus"></i>Create Member
        </button>
      </div>
    </form>
  </div>
</div>


{{-- ══ REJECT MEMBER MODAL ══ --}}
<div id="rejectMemberM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">❌ Reject Member</span>
      <div class="mc3" onclick="resetRejectMemberModal(); cm('rejectMemberM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.members.reject') }}" id="rejectMemberForm">
      @csrf
      <input type="hidden" name="id" id="rejectMemberId">
      <div class="fgrid c1">
        <div class="fg">
          <label>Member Name</label>
          <input type="text" id="rejectMemberName" disabled style="opacity:0.6">
        </div>
        <div class="fg">
          <label>Reason (optional)</label>
          <textarea name="reason" id="rejectMemberReason" placeholder="Enter rejection reason..." maxlength="500"></textarea>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetRejectMemberModal(); cm('rejectMemberM')">Cancel</button>
        <button type="submit" class="btn bda">
          <i class="fas fa-times"></i>Reject Member
        </button>
      </div>
    </form>
  </div>
</div>


{{-- ══ DELETE MEMBER MODAL (password confirmation) ══ --}}
<div id="deleteMemberM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">🗑️ Delete Member</span>
      <div class="mc3" onclick="resetDeleteMemberModal(); cm('deleteMemberM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('district-admin.members.delete') }}" id="deleteMemberForm" autocomplete="off">
      @csrf
      @method('DELETE')
      <input type="hidden" name="id" id="deleteMemberId">

      <div style="margin:14px 18px 0;padding:10px 12px;
                  background:rgba(239,68,68,0.1);
                  border:1px solid rgba(239,68,68,0.3);
                  border-radius:8px;font-size:12px;color:#f87171;">
        <i class="fas fa-exclamation-triangle"></i>
        You are about to permanently delete
        <b id="deleteMemberNameText"></b>.
        Their login will be removed and their details moved to the deleted-members archive.
        This cannot be undone.
      </div>

      <div class="fgrid c1">
        <div class="fg">
          <label>Enter your password to confirm</label>
          <input type="password" name="password" id="deleteMemberPassword"
                 placeholder="Your password" autocomplete="new-password" required>
        </div>
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="resetDeleteMemberModal(); cm('deleteMemberM')">Cancel</button>
        <button type="submit" class="btn bda">
          <i class="fas fa-trash"></i>Delete Member
        </button>
      </div>
    </form>
  </div>
</div>


{{-- ══ MEMBER DETAIL MODAL ══ --}}
<div class="mo" id="memberModal">
  <div class="md md-lg">
    <div class="mh">
      <span class="mt2"><i class="fas fa-user" style="color:var(--accent);margin-right:6px;"></i>Member Details</span>
      <div class="mc3" onclick="cm('memberModal')"><i class="fas fa-times"></i></div>
    </div>
    <div id="memberModalBody" style="padding:20px">Loading...</div>
    <div class="ff">
      <button class="btn bo" onclick="cm('memberModal')">Close</button>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
  @if ($errors->any())
    document.addEventListener('DOMContentLoaded', function () {
      om('addMemberM');
    });
  @endif

  // url() helper keeps the app's base path (e.g. /eca-api/public)
  const MEMBER_DETAIL_BASE = "{{ url('/district-admin/members') }}";

  // ── Escape values before putting them into innerHTML ──
  function esc(v) {
    return String(v ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function resetAddMemberModal() {
    const errBox = document.getElementById('addMemberError');
    if (errBox) errBox.remove();
    const form = document.getElementById('addMemberForm');
    if (form) form.reset();

    // form.reset() restores old('...') defaults, so clear fields explicitly
    ['addMemberName', 'addMemberEmail', 'addMemberPhone',
     'addMemberDob', 'addMemberOrg', 'addMemberAddress'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    const unitSel = document.getElementById('addMemberUnit');
    if (unitSel) unitSel.value = '';
  }

  function resetRejectMemberModal() {
    const form = document.getElementById('rejectMemberForm');
    if (form) form.reset();
  }

  function openReject(id, name) {
    resetRejectMemberModal();
    document.getElementById('rejectMemberId').value = id;
    document.getElementById('rejectMemberName').value = name;
    om('rejectMemberM');
  }

  // ── DELETE MEMBER (password confirmation) ──
  function resetDeleteMemberModal() {
    const form = document.getElementById('deleteMemberForm');
    if (form) form.reset();
    document.getElementById('deleteMemberPassword').value = '';
  }

  function openDelete(id, name) {
    resetDeleteMemberModal();
    document.getElementById('deleteMemberId').value = id;
    document.getElementById('deleteMemberNameText').textContent = name;
    om('deleteMemberM');
    setTimeout(() => document.getElementById('deleteMemberPassword').focus(), 100);
  }

  // ── VIEW MEMBER (address spans the full row) ──
  function viewMember(id) {
    document.getElementById('memberModalBody').innerHTML = 'Loading...';
    om('memberModal');

    fetch(`${MEMBER_DETAIL_BASE}/${id}/detail`, { headers: { 'Accept': 'application/json' } })
      .then(r => {
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
      })
      .then(d => {
        document.getElementById('memberModalBody').innerHTML = `
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            ${field('Name', d.name)}
            ${field('Email', d.email)}
            ${field('Phone', d.phone)}
            ${field('Date of Birth', d.dob ? String(d.dob).slice(0, 10) : '')}
            ${field('Organization', d.organization)}
            ${field('Unit', d.unit_name)}
            <div style="grid-column:1 / -1;">
              ${field('Address', d.address)}
            </div>
            ${field('Membership ID', d.membership_id)}
            ${field('Status', d.status)}
            ${field('Joined Date', d.joined_date)}
            ${field('Expiry Date', d.expiry_date)}
            ${field('Registered On', d.registered_on)}
          </div>
        `;
      })
      .catch(err => {
        document.getElementById('memberModalBody').innerHTML =
          '<div style="text-align:center;color:var(--red);">Failed to load member details.</div>';
        console.error('Member load failed:', err);
      });
  }

  function field(label, value) {
    return `
      <div>
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;
                    letter-spacing:0.5px;margin-bottom:3px;">${esc(label)}</div>
        <div style="font-size:12.5px;font-weight:500;white-space:pre-line;word-break:break-word;">${value ? esc(value) : '—'}</div>
      </div>
    `;
  }
</script>
@endsection