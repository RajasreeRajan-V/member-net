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
      <div style="display:flex;gap:8px;align-items:center;">
        <a href="{{ route('admin.members.download-all', request()->query()) }}" class="btn bo bsm" title="Download all filtered members as Excel">
          <i class="fas fa-file-excel"></i>Download Excel
        </a>
        <button class="btn bp bsm" onclick="resetAddMemberModal(); om('addMemberM')">
          <i class="fas fa-plus"></i>Add Member
        </button>
      </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('admin.members') }}"
          style="display:flex;gap:8px;padding:12px 16px;
                 border-bottom:1px solid var(--border);
                 flex-wrap:wrap;align-items:center;">
      <div style="position:relative;display:flex;align-items:center;">
        <i class="fas fa-search" style="position:absolute;left:10px;color:var(--text-dim);font-size:11px;pointer-events:none;"></i>
        <input type="text" name="search" value="{{ $search ?? '' }}"
               placeholder="Member ID or Phone..."
               style="padding:5px 12px 5px 28px;font-size:11px;
                      border-radius:20px;border:1px solid var(--border);
                      background:transparent;color:var(--text);
                      width:190px;outline:none;">
      </div>

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

      <select name="unit_id" style="padding:4px 10px;font-size:11px;
                 border-radius:20px;border:1px solid var(--border);
                 background:transparent;color:var(--text-dim);
                 width:auto;cursor:pointer;">
        <option value="">All Units</option>
        @foreach($units as $unit)
          <option value="{{ $unit->id }}"
            {{ ($unit_id ?? '') == $unit->id ? 'selected' : '' }}>
            {{ $unit->name }}
          </option>
        @endforeach
      </select>

      <button type="submit" class="btn bp bsm">
        <i class="fas fa-search"></i>Search
      </button>
      <a href="{{ route('admin.members') }}" class="btn bo bsm">
        <i class="fas fa-times"></i>Clear
      </a>
    </form>

    {{-- TABLE --}}
    <div class="tw">
      <table style="min-width: 880px;">
        <thead>
          <tr>
            <th>#</th>
            <th>Member</th>
            <th>Phone</th>
            <th>Unit</th>
            <th>Membership ID</th>
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
                <div class="mc2" style="cursor:pointer;" onclick="viewMember({{ $member->id }})" title="Click to view member details">
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
              <td style="color:var(--text-dim)">{{ $member->membership_id ?? '—' }}</td>
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
              <td colspan="8"
                  style="text-align:center;color:var(--text-dim);padding:30px;">
                @if(request()->filled('search'))
                  <div>No members found matching "<strong>{{ request('search') }}</strong>"</div>
                  <div style="margin-top:8px;">
                    <a href="{{ route('admin.members') }}" class="btn bo bsm">Clear Search</a>
                  </div>
                @else
                  No members found
                @endif
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
          Unit
        </div>
        <div class="fgrid">
          <div class="fg s2">
            <label>Unit</label>
            <select id="memberUnit" name="unit_id" required>
              <option value="">Select Unit</option>
              @foreach($units as $u)
                <option value="{{ $u->id }}" {{ old('unit_id') == $u->id ? 'selected' : '' }}>
                  {{ $u->name }}
                </option>
              @endforeach
            </select>
          </div>
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
    <div class="md" style="max-width:680px;width:95%;max-height:90vh;overflow-y:auto;border-radius:16px;">
      <div class="mh">
        <span class="mt2" style="display:flex;align-items:center;gap:8px;"><i class="fas fa-id-card" style="color:var(--accent);"></i>Member Profile & Details</span>
        <div class="mc3" onclick="cm('viewMemberM')">
          <i class="fas fa-times"></i>
        </div>
      </div>
      <div id="memberContent" style="padding:20px">Loading...</div>
      <div class="ff" style="display:flex;justify-content:space-between;align-items:center;">
        <a id="adminDownloadMemberBtn" href="#" class="btn bp bsm" target="_blank">
          <i class="fas fa-file-excel"></i>Download Excel
        </a>
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
function resetAddMemberModal() {
  const errBox = document.getElementById('addMemberError');
  if (errBox) errBox.remove();

  const form = document.getElementById('addMemberForm');
  if (form) form.reset();

  // form.reset() restores old('...') defaults, so clear text fields explicitly
  ['addMemberName', 'addMemberEmail', 'addMemberPhone',
   'addMemberDob', 'addMemberOrg', 'addMemberAddress', 'memberUnit'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.value = '';
  });
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

// ── Helper functions for Member Details Modal ──
function renderMemberTile(icon, label, value, isLink = false, linkHref = '') {
  const displayVal = (value !== null && value !== undefined && String(value).trim() !== '') ? String(value).trim() : '—';
  const content = isLink && displayVal !== '—'
    ? `<a href="${esc(linkHref)}" style="color:var(--text);text-decoration:none;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color='var(--text)'">${esc(displayVal)}</a>`
    : esc(displayVal);

  return `
    <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:10px 12px;display:flex;align-items:center;gap:12px;min-width:0;">
      <div style="width:34px;height:34px;border-radius:8px;background:rgba(79,142,247,0.1);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;">
        <i class="${icon}"></i>
      </div>
      <div style="min-width:0;flex:1;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:2px;">${esc(label)}</div>
        <div style="font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${content}</div>
      </div>
    </div>
  `;
}

function renderMemberFullTile(icon, label, value) {
  const displayVal = (value !== null && value !== undefined && String(value).trim() !== '') ? String(value).trim() : '—';
  return `
    <div style="grid-column:1 / -1;background:var(--card);border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;align-items:flex-start;gap:12px;">
      <div style="width:34px;height:34px;border-radius:8px;background:rgba(79,142,247,0.1);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;margin-top:2px;">
        <i class="${icon}"></i>
      </div>
      <div style="min-width:0;flex:1;">
        <div style="font-size:10px;color:var(--text-dim);text-transform:uppercase;letter-spacing:0.5px;font-weight:600;margin-bottom:3px;">${esc(label)}</div>
        <div style="font-size:13px;font-weight:500;color:var(--text);white-space:pre-line;line-height:1.5;">${esc(displayVal)}</div>
      </div>
    </div>
  `;
}

// ── VIEW MEMBER DETAILS ──
function viewMember(id) {
  document.getElementById('memberContent').innerHTML = `
    <div style="text-align:center;padding:45px 20px;color:var(--text-dim);">
      <i class="fas fa-spinner fa-spin" style="font-size:28px;color:var(--accent);margin-bottom:12px;display:block;"></i>
      <span style="font-size:13px;font-weight:500;">Loading member profile...</span>
    </div>
  `;
  document.getElementById('adminDownloadMemberBtn').href = `{{ url('/admin/members') }}/${id}/download`;
  om('viewMemberM');

  fetch(`{{ url('/admin/members') }}/${id}/detail`)
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then(d => {
      const initials = d.name ? d.name.trim().split(/\s+/).map(p => p[0]).slice(0, 2).join('').toUpperCase() : 'M';

      const photoHtml = d.photo
        ? `<img src="${esc(d.photo)}" alt="${esc(d.name)}" style="width:88px;height:88px;border-radius:14px;object-fit:cover;border:3px solid #fff;box-shadow:0 6px 18px rgba(0,0,0,0.12);flex-shrink:0;background:#fff;">`
        : `<div style="width:88px;height:88px;border-radius:14px;background:linear-gradient(135deg, var(--accent, #4f8ef7), #7c3aed);color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;box-shadow:0 6px 18px rgba(79,142,247,0.25);border:3px solid #fff;flex-shrink:0;">
            <span style="font-size:26px;font-weight:700;line-height:1;">${initials}</span>
            <span style="font-size:9px;font-weight:600;opacity:0.85;margin-top:4px;letter-spacing:0.5px;">NO PHOTO</span>
          </div>`;

      let statusBadge = '';
      const st = (d.status || '').toUpperCase();
      if (st === 'ACTIVE') {
        statusBadge = `<span class="badge bac" style="font-size:11px;padding:3px 9px;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-check-circle" style="font-size:10px;"></i>Active</span>`;
      } else if (st === 'APPROVED') {
        statusBadge = `<span class="badge bap" style="font-size:11px;padding:3px 9px;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-check" style="font-size:10px;"></i>Approved</span>`;
      } else if (st === 'PENDING') {
        statusBadge = `<span class="badge bpe" style="font-size:11px;padding:3px 9px;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-clock" style="font-size:10px;"></i>Pending</span>`;
      } else if (st === 'REJECTED') {
        statusBadge = `<span class="badge brj" style="font-size:11px;padding:3px 9px;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-times-circle" style="font-size:10px;"></i>Rejected</span>`;
      } else {
        statusBadge = `<span class="badge bex" style="font-size:11px;padding:3px 9px;">${esc(d.status || '—')}</span>`;
      }

      const memberNetBadge = d.is_fumma_member
        ? `<span class="badge bap" style="font-size:11px;padding:3px 9px;display:inline-flex;align-items:center;gap:4px;"><i class="fas fa-shield-alt" style="font-size:10px;"></i>Member Net</span>`
        : '';

      let dobFormatted = '—';
      if (d.dob) {
        const dobStr = String(d.dob).slice(0, 10);
        try {
          const dobObj = new Date(dobStr);
          if (!isNaN(dobObj.getTime())) {
            dobFormatted = dobObj.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
          } else {
            dobFormatted = dobStr;
          }
        } catch (e) {
          dobFormatted = dobStr;
        }
      }

      let schemesHtml = '';
      const totalSchemes = d.total_schemes ?? (d.schemes ? d.schemes.length : 0);

      if (d.schemes && d.schemes.length > 0) {
        schemesHtml = `
          <div style="display:flex;flex-direction:column;gap:8px;">
            ${d.schemes.map(s => `
              <div style="background:var(--card);border:1px solid var(--border);border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px;">
                <div style="min-width:0;flex:1;">
                  <div style="font-weight:700;font-size:13.5px;color:var(--text);display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-hand-holding-usd" style="color:var(--accent);font-size:12px;"></i>
                    ${esc(s.title)}
                  </div>
                  <div style="font-size:11.5px;color:var(--text-dim);margin-top:4px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                    <span style="font-weight:600;color:var(--text);"><i class="fas fa-rupee-sign" style="font-size:10px;margin-right:2px;"></i>${Number(s.amount).toLocaleString('en-IN')}</span>
                    <span style="opacity:0.4;">&bull;</span>
                    <span><i class="far fa-clock" style="font-size:10px;margin-right:2px;"></i>${esc(s.duration_months)} Months</span>
                    <span style="opacity:0.4;">&bull;</span>
                    <span><i class="far fa-calendar-alt" style="font-size:10px;margin-right:2px;"></i>${esc(s.start_date)} to ${esc(s.end_date)}</span>
                  </div>
                </div>
                <div>
                  <span class="badge ${s.is_active ? 'bac' : 'bpe'}" style="font-size:10px;padding:3px 8px;">
                    ${s.is_active ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
            `).join('')}
          </div>
        `;
      } else {
        schemesHtml = `
          <div style="background:var(--card);border:1px dashed var(--border);border-radius:10px;padding:18px;text-align:center;color:var(--text-dim);font-size:12.5px;">
            <i class="fas fa-layer-group" style="font-size:22px;opacity:0.35;display:block;margin-bottom:6px;"></i>
            No schemes enrolled for this member.
          </div>
        `;
      }

      document.getElementById('memberContent').innerHTML = `
        {{-- Hero Profile Banner --}}
        <div style="background:linear-gradient(135deg, rgba(79,142,247,0.08) 0%, rgba(124,58,237,0.05) 100%);border:1px solid rgba(79,142,247,0.18);border-radius:14px;padding:18px 20px;display:flex;align-items:center;gap:18px;margin-bottom:18px;position:relative;overflow:hidden;">
          <div style="position:absolute;right:-25px;top:-25px;width:130px;height:130px;background:radial-gradient(circle, rgba(79,142,247,0.15) 0%, transparent 70%);pointer-events:none;"></div>

          ${photoHtml}

          <div style="min-width:0;flex:1;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
              <h3 style="margin:0;font-size:19px;font-weight:700;color:var(--text);letter-spacing:-0.2px;">${esc(d.name)}</h3>
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-top:6px;">
              <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(79,142,247,0.12);color:var(--accent);border:1px solid rgba(79,142,247,0.25);padding:3px 9px;border-radius:6px;font-size:11.5px;font-weight:700;letter-spacing:0.3px;">
                <i class="fas fa-hashtag" style="font-size:10px;"></i>${esc(d.membership_id || 'PENDING')}
              </span>
              ${statusBadge}
              ${memberNetBadge}
            </div>

            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:8px;font-size:12px;color:var(--text-dim);">
              <span style="display:flex;align-items:center;gap:4px;">
                <i class="fas fa-store" style="color:var(--accent);font-size:11px;"></i>${esc(d.organization || 'Shop not specified')}
              </span>
              <span style="display:flex;align-items:center;gap:4px;">
                <i class="fas fa-building" style="color:var(--accent);font-size:11px;"></i>${esc(d.unit_name || '—')}
              </span>
            </div>
          </div>
        </div>

        {{-- Section 1: Personal & Contact Information --}}
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-dim);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
          <i class="fas fa-user-circle" style="color:var(--accent);"></i>Personal Information
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
          ${renderMemberTile('fas fa-phone', 'Phone Number', d.phone, true, `tel:${d.phone}`)}
          ${renderMemberTile('fas fa-envelope', 'Email Address', d.email, true, `mailto:${d.email}`)}
          ${renderMemberTile('fas fa-birthday-cake', 'Date of Birth', dobFormatted)}
          ${renderMemberTile('fas fa-map-marker-alt', 'State', d.state_name || 'Kerala')}
          ${renderMemberFullTile('fas fa-map-marked-alt', 'Full Address', d.address)}
        </div>

        {{-- Section 2: Membership & Business Information --}}
        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-dim);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
          <i class="fas fa-briefcase" style="color:var(--accent);"></i>Membership & Organization
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;">
          ${renderMemberTile('fas fa-store', 'Shop / Organization', d.organization)}
          ${renderMemberTile('fas fa-building', 'Unit', d.unit_name)}
          ${renderMemberTile('fas fa-id-card-alt', 'Membership Type', d.membership_type || 'ANNUAL')}
          ${renderMemberTile('fas fa-calendar-check', 'Joined Date', d.joined_date)}
          ${renderMemberTile('fas fa-hourglass-half', 'Expiry Date', d.expiry_date)}
          ${renderMemberTile('fas fa-clock', 'Registered On', d.registered_on)}
        </div>

        {{-- Section 3: Schemes Breakdown --}}
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
          <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.6px;color:var(--text-dim);display:flex;align-items:center;gap:6px;">
            <i class="fas fa-layer-group" style="color:var(--accent);"></i>Enrolled Schemes
          </span>
          <span class="badge" style="background:rgba(79,142,247,0.12);color:var(--accent);border:1px solid rgba(79,142,247,0.25);font-size:11px;padding:2px 8px;font-weight:700;">
            ${totalSchemes} Scheme${totalSchemes === 1 ? '' : 's'}
          </span>
        </div>
        ${schemesHtml}
      `;
    })
    .catch(err => {
      document.getElementById('memberContent').innerHTML = `
        <div style="text-align:center;padding:35px 20px;color:var(--red);">
          <i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>
          <span style="font-size:13px;font-weight:500;">Failed to load member details. Please try again.</span>
        </div>
      `;
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