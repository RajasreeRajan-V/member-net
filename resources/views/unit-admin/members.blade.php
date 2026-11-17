@extends('unit-admin.layout')
@section('title', 'Members')
@section('page_title', 'Members')

@section('content')

{{-- FILTER BAR --}}
<div class="card">
  <form method="GET" action="{{ route('unit-admin.members') }}">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:14px 18px;">
      <div class="fg" style="margin:0;flex:1;min-width:120px;">
        <label>Status</label>
        <select name="status">
          <option value="">All Status</option>
          <option value="PENDING"  {{ ($status ?? '') === 'PENDING'  ? 'selected' : '' }}>Pending</option>
          <option value="APPROVED" {{ ($status ?? '') === 'APPROVED' ? 'selected' : '' }}>Approved</option>
          <option value="ACTIVE"   {{ ($status ?? '') === 'ACTIVE'   ? 'selected' : '' }}>Active</option>
          <option value="REJECTED" {{ ($status ?? '') === 'REJECTED' ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>
      <div style="display:flex;gap:6px;">
        <button type="submit" class="btn bp bsm"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('unit-admin.members') }}" class="btn bo bsm"><i class="fas fa-times"></i> Clear</a>
      </div>
    </div>
  </form>
</div>

{{-- MEMBERS TABLE --}}
<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-users"></i>
      Unit Members
      <span style="font-size:10px;color:var(--text-dim);font-weight:400;">({{ $members->total() }})</span>
    </div>
    {{-- ADD MEMBER BUTTON --}}
    <button class="btn bp bsm" onclick="om('addMemberM')">
      <i class="fas fa-plus"></i> Add Member
    </button>
  </div>

  @if($members->isEmpty())
    <div class="empty-state">
      <i class="fas fa-users"></i>
      <p>No members found</p>
      <button class="btn bp bsm" style="margin-top:10px;" onclick="om('addMemberM')">
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
            <th>Membership ID</th>
            <th>FUMMA</th>
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
                        <td style="color:var(--text-dim);white-space:nowrap;">{{ $m->membership_id ?? '—' }}</td>
            <td>
              @if($m->is_fumma_member)
                <span class="badge bap">FUMMA</span>
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
                <span class="badge bap">ACTIVE</span>
              @elseif($m->status === 'REJECTED')
                <span class="badge brj">REJECTED</span>
              @else
                <span class="badge bac">{{ $m->status }}</span>
              @endif
            </td>
            <td class="hide-mobile" style="color:var(--text-dim);">
              {{ $m->created_at->format('d M Y') }}
            </td>
            <td>
              <div class="abr">
                {{-- VIEW --}}
                <button class="ab vw" title="View Details"
                        onclick="viewMember({{ $m->id }})">
                  <i class="fas fa-eye"></i>
                </button>

                {{-- APPROVE --}}
                @if(in_array($m->status, ['PENDING', 'REJECTED']))
                  <form method="POST" action="{{ route('unit-admin.members.approve') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="id" value="{{ $m->id }}">
                    <button type="submit" class="ab app" title="Approve"
                            onclick="return confirm({{ json_encode('Approve ' . $m->user->name . '?') }})">
                      <i class="fas fa-check"></i>
                    </button>
                  </form>
                @endif

                {{-- REJECT --}}
                @if(in_array($m->status, ['PENDING', 'APPROVED', 'ACTIVE']))
                  <form method="POST" action="{{ route('unit-admin.members.reject') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="id" value="{{ $m->id }}">
                    <button type="submit" class="ab rej" title="Reject"
                            onclick="return confirm({{ json_encode('Reject ' . $m->user->name . '?') }})">
                      <i class="fas fa-times"></i>
                    </button>
                  </form>
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
      <div class="mc3" onclick="closeAndReset('addMemberM','addMemberForm')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('unit-admin.members.store') }}"
      enctype="multipart/form-data" autocomplete="off" id="addMemberForm">
      @csrf

      @if ($errors->any())
        <div style="margin:14px 18px 0;padding:10px 12px;
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
          <input type="text" name="name" placeholder="Rajesh Kumar"
                 value="{{ old('name') }}" autocomplete="off" required>
        </div>
        <div class="fg">
          <label>Email <span style="color:var(--text-dim);font-weight:400;">(optional)</span></label>
          <input type="email" name="email" maxlength="150"
                 placeholder="rajesh@gmail.com" value="{{ old('email') }}"
                 pattern="[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}"
                 title="Enter a valid email with a domain, e.g. name@example.com"
                 autocomplete="off">
        </div>
        <div class="fg">
          <label>Phone</label>
          <input type="text" name="phone" placeholder="9845002001"
                 maxlength="10"
                 value="{{ old('phone') }}"
                 autocomplete="off"
                 oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                 required>
        </div>
        <div class="fg">
          <label>Date of Birth</label>
          <input type="date" name="dob" value="{{ old('dob') }}" required>
        </div>
        <div class="fg">
          <label>Organization</label>
          <input type="text" name="organization"
                 placeholder="Calicut University" value="{{ old('organization') }}"
                 autocomplete="off" required>
        </div>
        <div class="fg s2">
          <label>Address</label>
          <textarea name="address" maxlength="500" rows="2"
                    placeholder="House name, street, area, PIN"
                    autocomplete="off"
                    required>{{ old('address') }}</textarea>
        </div>
        <div class="fg s2">
          <label>Photo</label>
          <input type="file" name="photo"
                 accept="image/jpg,image/jpeg,image/png"
                 style="padding:6px 11px;" required>
        </div>
      </div>

      <div style="margin:8px 18px 14px;padding:10px 12px;
            background:rgba(79,142,247,0.07);border-radius:8px;
            font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        Member will be automatically assigned to
        <b style="color:var(--accent)">{{ $myUnit->name }}</b> unit
        and <b style="color:var(--green, #34d399)">approved immediately</b>.
        A Membership ID is generated, and the login details are emailed now (if an email is given).
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="closeAndReset('addMemberM','addMemberForm')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-user-plus"></i>Create Member
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
    <form method="POST" action="{{ route('unit-admin.members.delete') }}" id="deleteMemberForm" autocomplete="off">
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
    <div id="memberModalBody" style="padding:20px">
      Loading...
    </div>
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

  // url() keeps the app's base path (e.g. /eca-api/public)
  const MEMBER_DETAIL_BASE = "{{ url('/unit-admin/members') }}";
  
  const SHOW_STATE = {{ \App\Models\State::fixed() ? 'false' : 'true' }};

  // ── Escape values before putting them into innerHTML ──
  function esc(v) {
    return String(v ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
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
            ${field('District', d.district_name)}
            ${SHOW_STATE ? field('State', d.state_name) : ''}
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

  function closeAndReset(modalId, formId) {
    cm(modalId);
    const form = document.getElementById(formId);
    if (form) form.reset();

    // form.reset() restores old('...') defaults, so clear fields explicitly
    if (form) {
      form.querySelectorAll('input[type="text"], input[type="email"], input[type="date"], textarea')
        .forEach(el => { el.value = ''; });
    }
  }
</script>
@endsection