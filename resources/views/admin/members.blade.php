@extends('layouts.admin')
@section('title', 'Members')
@section('page_title', 'Members')

@section('content')

  {{-- True only when the ADD MEMBER form failed validation (so a failed delete/other form doesn't pop the Add modal) --}}
  @php $addMemberFailed = $errors->any() && old('phone') !== null; @endphp

  <div class="card">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-users"></i>All Members
      </div>
      <button class="btn bp bsm" onclick="resetAddMemberModal(); om('addMemberM')">
        <i class="fas fa-plus"></i>Add Member
      </button>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.members') }}"
          style="display:flex;gap:8px;padding:12px 16px;
                 border-bottom:1px solid var(--border);
                 flex-wrap:wrap;align-items:center;">
      <select name="status" style="padding:4px 10px;font-size:11px;
                 border-radius:20px;border:1px solid var(--border);
                 background:transparent;color:var(--text-dim);
                 width:auto;cursor:pointer;">
        <option value="">All Status</option>
        <option value="PENDING"  {{ $status == 'PENDING'  ? 'selected' : '' }}>Pending</option>
        <option value="APPROVED" {{ $status == 'APPROVED' ? 'selected' : '' }}>Approved</option>
        <option value="ACTIVE"   {{ $status == 'ACTIVE'   ? 'selected' : '' }}>Active</option>
        <option value="REJECTED" {{ $status == 'REJECTED' ? 'selected' : '' }}>Rejected</option>
        <option value="EXPIRED"  {{ $status == 'EXPIRED'  ? 'selected' : '' }}>Expired</option>
      </select>

      <select name="district_id" style="padding:4px 10px;font-size:11px;
                 border-radius:20px;border:1px solid var(--border);
                 background:transparent;color:var(--text-dim);
                 width:auto;cursor:pointer;">
        <option value="">All Districts</option>
        @foreach($districts as $district)
          <option value="{{ $district->id }}"
            {{ $district_id == $district->id ? 'selected' : '' }}>
            {{ $district->name }}
          </option>
        @endforeach
      </select>

      <button type="submit" class="btn bp bsm">
        <i class="fas fa-filter"></i>Filter
      </button>
      <a href="{{ route('admin.members') }}" class="btn bo bsm">
        <i class="fas fa-times"></i>Clear
      </a>
    </form>

    {{-- TABLE --}}
    <div class="tw">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Phone</th>
            <th>Unit</th>
            <th>District</th>
            <th>Membership ID</th>
            <th>FUMMA</th>
            <th>Status</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($members as $member)
            <tr>
              <td style="color:var(--text-dim)">
                {{ ($members->currentPage() - 1) * $members->perPage() + $loop->iteration }}
              </td>
              <td>
                <div class="mc2">
                  @if($member->photo)
                    <img src="{{ asset('storage/' . $member->photo) }}"
                         style="width:32px;height:32px;border-radius:6px;object-fit:cover;">
                  @else
                    <div class="av2" style="background:rgba(79,142,247,0.2);color:var(--accent)">
                      {{ strtoupper(substr($member->user->name, 0, 2)) }}
                    </div>
                  @endif
                  <div>
                    <div class="mn">{{ $member->user->name }}</div>
                    <div class="me">{{ $member->user->email ?: 'No email' }}</div>
                  </div>
                </div>
              </td>
              <td style="color:var(--text-dim)">{{ $member->user->phone }}</td>
              <td><span class="utag">{{ $member->unit->name }}</span></td>
              <td style="color:var(--text-dim)">{{ $member->district->name }}</td>
              <td style="color:var(--text-dim)">{{ $member->membership_id ?? '—' }}</td>
<td>
  @if($member->is_fumma_member)
    <span class="badge bap">FUMMA</span>
  @else
    <span style="color:var(--text-dim);font-size:11px;">—</span>
  @endif
</td>
              <td>
                <span class="badge
                  @if($member->status == 'PENDING')  bpe
                  @elseif($member->status == 'APPROVED') bap
                  @elseif($member->status == 'ACTIVE')   bac
                  @elseif($member->status == 'REJECTED') brj
                  @else bex @endif">
                  {{ $member->status }}
                </span>
              </td>
              <td style="color:var(--text-dim)">
                {{ $member->created_at->format('d M Y') }}
              </td>
              <td>
                <div class="abr">
                  {{-- VIEW --}}
                  <div class="ab vw" onclick="viewMember({{ $member->id }})" title="View Detail">
                    <i class="fas fa-eye"></i>
                  </div>

                  {{-- EDIT --}}
                  <div class="ab vw" onclick="editMember({{ $member->id }})" title="Edit Member">
                    <i class="fas fa-pen"></i>
                  </div>

                  {{-- APPROVE --}}
                  @if(in_array($member->status, ['PENDING', 'REJECTED']))
                    <form method="POST" action="{{ route('admin.members.approve') }}"
                          style="display:inline"
                          onsubmit="return confirm({{ json_encode('Approve ' . $member->user->name . '?') }})">
                      @csrf
                      <input type="hidden" name="id" value="{{ $member->id }}">
                      <button type="submit" class="ab app" title="Approve">
                        <i class="fas fa-check"></i>
                      </button>
                    </form>
                  @endif

                  {{-- REJECT --}}
                  @if(in_array($member->status, ['PENDING', 'APPROVED']))
                    <div class="ab rej" title="Reject"
                         onclick="openReject({{ $member->id }}, {{ json_encode($member->user->name) }})">
                      <i class="fas fa-times"></i>
                    </div>
                  @endif

                  {{-- DELETE (opens password-confirm modal) --}}
                  <div class="ab rej" title="Delete"
                       onclick="openDeleteMember({{ $member->id }}, {{ json_encode($member->user->name) }})">
                    <i class="fas fa-trash"></i>
                  </div>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10"
                  style="text-align:center;color:var(--text-dim);padding:30px;">
                No members found
              </td>
            </tr>
          @endforelse
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
  </div>


  {{-- ══ ADD MEMBER MODAL ══ --}}
  <div id="addMemberM" class="mo">
    <div class="md" style="width:580px">
      <div class="mh">
        <span class="mt2">Add New Member</span>
        <div class="mc3" onclick="resetAddMemberModal(); cm('addMemberM')">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.members.store') }}"
            enctype="multipart/form-data" id="addMemberForm">
        @csrf

        @if ($addMemberFailed)
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
                   value="{{ old('name') }}"
                   placeholder="Rajesh Kumar"
                   pattern="[A-Za-z]+([\s'\-][A-Za-z]+)*"
                   title="Name can only contain letters, spaces, hyphens and apostrophes."
                   oninput="this.value=this.value.replace(/[^A-Za-z\s'\-]/g,'')"
                   required>
          </div>
          <div class="fg">
            <label>Email <span style="color:var(--text-dim);font-weight:400;">(optional)</span></label>
            <input type="email" name="email" id="addMemberEmail" maxlength="150"
                   value="{{ old('email') }}"
                   placeholder="rajesh@gmail.com"
                   pattern="[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}"
                   title="Enter a valid email with a domain, e.g. name@example.com">
          </div>
          <div class="fg">
            <label>Phone</label>
            <input type="text" name="phone" id="addMemberPhone" placeholder="9845002001"
                   maxlength="10"
                   value="{{ old('phone') }}"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                   required>
          </div>
          <div class="fg">
            <label>Date of Birth</label>
            <input type="date" name="dob" id="addMemberDob" value="{{ old('dob') }}" required>
          </div>
          <div class="fg">
            <label>Shop Name</label>
            <input type="text" name="organization" id="addMemberOrg" maxlength="150"
                   value="{{ old('organization') }}"
                   placeholder="Fathima Stores" required>
          </div>
          <div class="fg s2">
            <label>Address</label>
            <textarea name="address" id="addMemberAddress" maxlength="500" rows="2"
                      placeholder="House name, street, area, PIN"
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
          Location
        </div>
                <div class="fgrid">
          @if($fixedState)
            {{-- Kerala is fixed: hidden state, districts already loaded --}}
            <input type="hidden" name="state_id" id="memberStateId" value="{{ $fixedState->id }}">
            <div class="fg">
              <label>District</label>
              <select id="memberDistrict" name="district_id"
                      onchange="loadMemberUnits(this.value)" required>
                <option value="">Select District</option>
                @foreach($districts as $d)
                  <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="fg">
              <label>Unit</label>
              <select id="memberUnit" name="unit_id" required>
                <option value="">Select District First</option>
              </select>
            </div>
          @else
            <div class="fg">
              <label>State</label>
              <select id="addMemberState" onchange="loadMemberDistricts(this.value)" required>
                <option value="">Select State</option>
                @foreach($states as $state)
                  <option value="{{ $state->id }}">{{ $state->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="fg">
              <label>District</label>
              <select id="memberDistrict" name="district_id"
                      onchange="loadMemberUnits(this.value)" required>
                <option value="">Select State First</option>
              </select>
            </div>
            <div class="fg">
              <label>Unit</label>
              <select id="memberUnit" name="unit_id" required>
                <option value="">Select District First</option>
              </select>
            </div>
            <input type="hidden" name="state_id" id="memberStateId">
          @endif
        </div>

        <div style="margin:0 18px 14px;padding:10px 12px;
                    background:rgba(79,142,247,0.07);border-radius:8px;
                    font-size:11px;color:var(--text-dim);">
          <i class="fas fa-info-circle" style="color:var(--accent)"></i>
          Member will be created with
          <b style="color:var(--orange)">PENDING</b> status.
           Login details are emailed once the member is approved (if an email is given).
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


  {{-- ══ EDIT MEMBER MODAL ══ --}}
  <div id="editMemberM" class="mo">
    <div class="md" style="width:520px">
      <div class="mh">
        <span class="mt2">Edit Member</span>
        <div class="mc3" onclick="resetEditMemberModal(); cm('editMemberM')">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.members.update') }}"
            enctype="multipart/form-data" id="editMemberForm">
        @csrf
        <input type="hidden" name="id" id="editMemberId">

        <div style="padding:14px 18px 0;font-size:10px;font-weight:700;
                    text-transform:uppercase;letter-spacing:1px;color:var(--accent);">
          Member Info
        </div>
        <div class="fgrid">
          <div class="fg">
            <label>Full Name</label>
            <input type="text" name="name" id="editMemberName" maxlength="100"
                   pattern="[A-Za-z]+([\s'\-][A-Za-z]+)*"
                   title="Name can only contain letters, spaces, hyphens and apostrophes."
                   oninput="this.value=this.value.replace(/[^A-Za-z\s'\-]/g,'')"
                   required>
          </div>
          <div class="fg">
            <label>Phone</label>
            <input type="text" name="phone" id="editMemberPhone" maxlength="10"
                   oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                   required>
          </div>
          <div class="fg">
            <label>Date of Birth</label>
            <input type="date" name="dob" id="editMemberDob" required>
          </div>
          <div class="fg">
            <label>Shop Name</label>
            <input type="text" name="organization" id="editMemberOrg" maxlength="150" required>
          </div>
          <div class="fg s2">
            <label>Address</label>
            <textarea name="address" id="editMemberAddress" maxlength="500" rows="2"
                      placeholder="House name, street, area, PIN" required></textarea>
          </div>
          <div class="fg s2">
            <label>Current Photo</label>
            <div id="editMemberCurrentPhoto" style="margin-bottom:8px;">
              <span style="font-size:11px;color:var(--text-dim);">No photo uploaded</span>
            </div>
            <label id="editMemberPhotoLabel">Replace Photo (leave blank to keep current)</label>
            <input type="file" name="photo" id="editMemberPhoto"
                   accept="image/jpg,image/jpeg,image/png"
                   style="padding:6px 11px;">
          </div>
        </div>

        <div class="ff">
          <button type="button" class="btn bo" onclick="resetEditMemberModal(); cm('editMemberM')">Cancel</button>
          <button type="submit" class="btn bp">
            <i class="fas fa-save"></i>Save Changes
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
        <div class="mc3" onclick="resetRejectMemberModal(); cm('rejectMemberM')">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.members.reject') }}" id="rejectMemberForm">
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


  {{-- ══ DELETE MEMBER MODAL (password confirm) ══ --}}
  <div id="deleteMemberM" class="mo">
    <div class="md">
      <div class="mh">
        <span class="mt2">🗑️ Delete Member</span>
        <div class="mc3" onclick="resetDeleteMemberModal(); cm('deleteMemberM')">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <form method="POST" action="{{ route('admin.members.delete') }}" id="deleteMemberForm">
        @csrf
        @method('DELETE')
        <input type="hidden" name="id" id="deleteMemberId">
        <div class="fgrid c1">
          <div class="fg">
            <label>Member</label>
            <input type="text" id="deleteMemberName" disabled style="opacity:0.6">
          </div>
          <div style="padding:10px 12px;background:rgba(239,68,68,0.1);
                      border:1px solid rgba(239,68,68,0.3);border-radius:8px;
                      font-size:12px;color:#f87171;">
            <i class="fas fa-exclamation-triangle"></i>
            This permanently deletes the member and their login account.
            This cannot be undone.
          </div>
          <div class="fg">
            <label>Enter your login password to confirm</label>
            <input type="password" name="password" id="deleteMemberPassword"
                   placeholder="Your password" autocomplete="current-password" required>
          </div>
        </div>
        <div class="ff">
          <button type="button" class="btn bo"
                  onclick="resetDeleteMemberModal(); cm('deleteMemberM')">Cancel</button>
          <button type="submit" class="btn bda">
            <i class="fas fa-trash"></i>Delete Member
          </button>
        </div>
      </form>
    </div>
  </div>


  {{-- ══ VIEW MEMBER MODAL ══ --}}
  <div id="viewMemberM" class="mo">
    <div class="md" style="width:560px">
      <div class="mh">
        <span class="mt2">Member Detail</span>
        <div class="mc3" onclick="cm('viewMemberM')">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <div id="memberContent" style="padding:20px">Loading...</div>
      <div class="ff">
        <button class="btn bo" onclick="cm('viewMemberM')">Close</button>
      </div>
    </div>
  </div>

@endsection

@section('scripts')
<script>
// ── Escape values before putting them into innerHTML ──
function esc(v) {
  return String(v ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// ── Reset "Add Member" modal ──
const KERALA_FIXED = {{ $fixedState ? 'true' : 'false' }};

// ── Reset "Add Member" modal ──
function resetAddMemberModal() {
  const errBox = document.getElementById('addMemberError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addMemberForm');
  if (form) form.reset();

  // form.reset() restores old('...') defaults, so clear text fields explicitly
  ['addMemberName', 'addMemberEmail', 'addMemberPhone',
   'addMemberDob', 'addMemberOrg', 'addMemberAddress'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });

  const districtSel = document.getElementById('memberDistrict');
  const unitSel     = document.getElementById('memberUnit');

  if (unitSel) unitSel.innerHTML = '<option value="">Select District First</option>';

  if (KERALA_FIXED) {
    // Keep the Kerala district list and the hidden Kerala state_id
    if (districtSel) districtSel.value = '';
  } else {
    if (districtSel) districtSel.innerHTML = '<option value="">Select State First</option>';
    const stateHidden = document.getElementById('memberStateId');
    if (stateHidden) stateHidden.value = '';
  }
}

// ── Reset "Edit Member" modal ──
function resetEditMemberModal() {
  const form = document.getElementById('editMemberForm');
  if (form) form.reset();

  document.getElementById('editMemberId').value = '';
  document.getElementById('editMemberAddress').value = '';
  document.getElementById('editMemberCurrentPhoto').innerHTML =
    '<span style="font-size:11px;color:var(--text-dim);">No photo uploaded</span>';

  // photo is only required when the member has no photo yet (set in editMember())
  const photoInput = document.getElementById('editMemberPhoto');
  if (photoInput) photoInput.required = false;
  const photoLabel = document.getElementById('editMemberPhotoLabel');
  if (photoLabel) photoLabel.textContent = 'Replace Photo (leave blank to keep current)';
}

// ── Reset "Reject Member" modal ──
function resetRejectMemberModal() {
  const form = document.getElementById('rejectMemberForm');
  if (form) form.reset();
}

// ── Reset "Delete Member" modal ──
function resetDeleteMemberModal() {
  const form = document.getElementById('deleteMemberForm');
  if (form) form.reset();

  document.getElementById('deleteMemberId').value = '';
  document.getElementById('deleteMemberName').value = '';
  document.getElementById('deleteMemberPassword').value = '';
}

@if ($addMemberFailed)
document.addEventListener('DOMContentLoaded', function () {
  om('addMemberM');
});
@endif

function openReject(id, name) {
  resetRejectMemberModal();
  document.getElementById('rejectMemberId').value = id;
  document.getElementById('rejectMemberName').value = name;
  om('rejectMemberM');
}

// ── Open delete modal: asks super admin for their password ──
function openDeleteMember(id, name) {
  resetDeleteMemberModal();
  document.getElementById('deleteMemberId').value = id;
  document.getElementById('deleteMemberName').value = name;
  om('deleteMemberM');
  setTimeout(() => document.getElementById('deleteMemberPassword').focus(), 100);
}

function loadMemberDistricts(stateId) {
  document.getElementById('memberStateId').value = stateId;

  const districtSel = document.getElementById('memberDistrict');
  const unitSel = document.getElementById('memberUnit');

  if (!stateId) {
    districtSel.innerHTML = '<option value="">Select State First</option>';
    unitSel.innerHTML = '<option value="">Select District First</option>';
    return;
  }

  districtSel.innerHTML = '<option value="">Loading...</option>';
  unitSel.innerHTML = '<option value="">Select District First</option>';

  fetch(`{{ url('/api/districts') }}`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ state_id: stateId })
  })
  .then(r => r.json())
  .then(data => {
    districtSel.innerHTML = '<option value="">Select District</option>';
    if (data.data && data.data.length > 0) {
      data.data.forEach(d => {
        districtSel.innerHTML += `<option value="${d.id}">${esc(d.name)}</option>`;
      });
    } else {
      districtSel.innerHTML = '<option value="">No districts found</option>';
    }
  })
  .catch(err => {
    districtSel.innerHTML = '<option value="">Error loading districts</option>';
    console.error('District load failed:', err);
  });
}

function loadMemberUnits(districtId) {
  const sel = document.getElementById('memberUnit');

  if (!districtId) {
    sel.innerHTML = '<option value="">Select District First</option>';
    return;
  }

  sel.innerHTML = '<option value="">Loading...</option>';

  fetch(`{{ url('/api/units') }}`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({ district_id: districtId })
  })
  .then(r => r.json())
  .then(data => {
    sel.innerHTML = '<option value="">Select Unit</option>';
    if (data.data && data.data.length > 0) {
      data.data.forEach(u => {
        sel.innerHTML += `<option value="${u.id}">${esc(u.name)}</option>`;
      });
    } else {
      sel.innerHTML = '<option value="">No units found</option>';
    }
  })
  .catch(err => {
    sel.innerHTML = '<option value="">Error loading units</option>';
    console.error('Unit load failed:', err);
  });
}

// ── VIEW MEMBER (address spans the full row) ──
function viewMember(id) {
  document.getElementById('memberContent').innerHTML = 'Loading...';
  om('viewMemberM');

  fetch(`{{ url('/admin/members') }}/${id}/detail`)
    .then(r => r.json())
    .then(d => {
      document.getElementById('memberContent').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
          ${field('Name', d.name)}
          ${field('Email', d.email)}
          ${field('Phone', d.phone)}
          ${field('Date of Birth', d.dob ? String(d.dob).slice(0, 10) : '')}
          ${field('Shop Name', d.organization)}
          ${field('Unit', d.unit_name)}
          <div style="grid-column:1 / -1;">
            ${field('Address', d.address)}
          </div>
          ${field('District', d.district_name)}
          ${field('State', d.state_name)}
          ${field('Membership ID', d.membership_id)}
          ${field('Status', d.status)}
          ${field('Joined Date', d.joined_date)}
          ${field('Expiry Date', d.expiry_date)}
          ${field('Registered On', d.registered_on)}
        </div>
      `;
    })
    .catch(err => {
      document.getElementById('memberContent').innerHTML =
        '<span style="color:var(--red);font-size:12px;">Failed to load member details</span>';
      console.error('Member view failed:', err);
    });
}

// ── EDIT MEMBER (address pre-filled, photo required only if member has none) ──
function editMember(id) {
  resetEditMemberModal();
  om('editMemberM');
  document.getElementById('editMemberCurrentPhoto').innerHTML =
    '<span style="font-size:11px;color:var(--text-dim);">Loading...</span>';

  fetch(`{{ url('/admin/members') }}/${id}/detail`)
    .then(r => r.json())
    .then(d => {
      document.getElementById('editMemberId').value      = id;
      document.getElementById('editMemberName').value    = d.name || '';
      document.getElementById('editMemberPhone').value   = d.phone || '';
      document.getElementById('editMemberOrg').value     = d.organization || '';
      document.getElementById('editMemberAddress').value = d.address || '';

      // dob may come as "YYYY-MM-DD" or "YYYY-MM-DDT00:00:00..." — take the date part
      document.getElementById('editMemberDob').value =
        d.dob ? String(d.dob).slice(0, 10) : '';

      document.getElementById('editMemberCurrentPhoto').innerHTML = d.photo
        ? `<img src="${esc(d.photo)}" style="width:52px;height:52px;border-radius:8px;object-fit:cover;">`
        : '<span style="font-size:11px;color:var(--text-dim);">No photo uploaded</span>';

      // Photo is mandatory: if the member has none, force an upload
      const photoInput = document.getElementById('editMemberPhoto');
      const photoLabel = document.getElementById('editMemberPhotoLabel');
      if (d.photo) {
        photoInput.required = false;
        photoLabel.textContent = 'Replace Photo (leave blank to keep current)';
      } else {
        photoInput.required = true;
        photoLabel.textContent = 'Upload Photo (required)';
      }
    })
    .catch(err => {
      document.getElementById('editMemberCurrentPhoto').innerHTML =
        '<span style="font-size:11px;color:var(--red);">Failed to load member details</span>';
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