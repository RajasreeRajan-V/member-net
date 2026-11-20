@extends('unit-admin.layout')
@section('title', 'Members')
@section('page_title', 'Members')

@section('content')

{{-- FILTER BAR --}}
<div class="card">
  <form method="GET" action="{{ route('unit-admin.members') }}">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;padding:14px 18px;">
      <div class="fg" style="margin:0;flex:2;min-width:200px;">
        <label>Search Member</label>
        <div style="position:relative;display:flex;align-items:center;">
          <i class="fas fa-search" style="position:absolute;left:11px;color:var(--text-dim);font-size:12px;pointer-events:none;"></i>
          <input type="text" name="search" value="{{ $search ?? '' }}"
                 placeholder="Member ID or Phone Number..."
                 style="padding-left:32px;width:100%;">
        </div>
      </div>
      <div class="fg" style="margin:0;flex:1;min-width:140px;">
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
        <button type="submit" class="btn bp bsm"><i class="fas fa-search"></i> Search</button>
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
    <div style="display:flex;gap:8px;align-items:center;">
      <a href="{{ route('unit-admin.members.download-all', request()->query()) }}" class="btn bo bsm" title="Download all filtered members as Excel">
        <i class="fas fa-file-excel"></i> Download Excel
      </a>
      <button class="btn bp bsm" onclick="om('addMemberM')">
        <i class="fas fa-plus"></i> Add Member
      </button>
    </div>
  </div>

  @if($members->isEmpty())
    <div class="empty-state">
      <i class="fas fa-users"></i>
      @if(request()->filled('search'))
        <p>No members found matching "<strong>{{ request('search') }}</strong>"</p>
        <a href="{{ route('unit-admin.members') }}" class="btn bo bsm" style="margin-top:10px;">
          <i class="fas fa-times"></i> Clear Search
        </a>
      @else
        <p>No members found</p>
        <button class="btn bp bsm" style="margin-top:10px;" onclick="om('addMemberM')">
          <i class="fas fa-plus"></i> Add First Member
        </button>
      @endif
    </div>
  @else
    <div class="tw">
      <table style="min-width: 850px;">
        <thead>
          <tr>
            <th>Member</th>
            <th>Phone</th>
            <th>Membership ID</th>
            <th>Organization</th>
            <th>Status</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          @foreach($members as $m)
          <tr>
            <td>
              <div class="mc2" style="cursor:pointer;" onclick="viewMember({{ $m->id }})" title="Click to view member details">
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
            <td>{{ Str::limit($m->organization, 20) }}</td>
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
            <td style="color:var(--text-dim);">
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
  <div class="md md-lg" style="max-width:680px;width:95%;max-height:90vh;overflow-y:auto;border-radius:16px;">
    <div class="mh">
      <span class="mt2" style="display:flex;align-items:center;gap:8px;"><i class="fas fa-id-card" style="color:var(--accent);"></i>Member Profile & Details</span>
      <div class="mc3" onclick="cm('memberModal')"><i class="fas fa-times"></i></div>
    </div>
    <div id="memberModalBody" style="padding:20px">
      Loading...
    </div>
    <div class="ff" style="display:flex;justify-content:space-between;align-items:center;">
      <a id="unitDownloadMemberBtn" href="#" class="btn bp bsm" target="_blank">
        <i class="fas fa-file-excel"></i> Download Excel
      </a>
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
    document.getElementById('memberModalBody').innerHTML = `
      <div style="text-align:center;padding:45px 20px;color:var(--text-dim);">
        <i class="fas fa-spinner fa-spin" style="font-size:28px;color:var(--accent);margin-bottom:12px;display:block;"></i>
        <span style="font-size:13px;font-weight:500;">Loading member profile...</span>
      </div>
    `;
    document.getElementById('unitDownloadMemberBtn').href = `${MEMBER_DETAIL_BASE}/${id}/download`;
    om('memberModal');

    fetch(`${MEMBER_DETAIL_BASE}/${id}/detail`, { headers: { 'Accept': 'application/json' } })
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
                    <div style="font-size:11.5px;color:var(--text-dim);margin-top:4px;display:flex;flex-wrap:gap:8px;align-items:center;">
                      <span style="font-weight:600;color:var(--text);"><i class="fas fa-rupee-sign" style="font-size:10px;margin-right:2px;"></i>${Number(s.amount).toLocaleString('en-IN')}</span>
                      <span style="opacity:0.4;margin:0 4px;">&bull;</span>
                      <span><i class="far fa-clock" style="font-size:10px;margin-right:2px;"></i>${esc(s.duration_months)} Months</span>
                      <span style="opacity:0.4;margin:0 4px;">&bull;</span>
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

        document.getElementById('memberModalBody').innerHTML = `
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
        document.getElementById('memberModalBody').innerHTML = `
          <div style="text-align:center;padding:35px 20px;color:var(--red);">
            <i class="fas fa-exclamation-triangle" style="font-size:24px;margin-bottom:8px;display:block;"></i>
            <span style="font-size:13px;font-weight:500;">Failed to load member details. Please try again.</span>
          </div>
        `;
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