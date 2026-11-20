@extends('unit-admin.layout')
@section('title', 'Renewals')
@section('page_title', 'Member Renewals')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-rotate"></i> Renewal Records</div>
    <button class="btn bp bsm" onclick="om('addRenewalM')">
      <i class="fas fa-plus"></i>Add Renewal
    </button>
  </div>

  <div style="padding:12px 16px;border-bottom:1px solid var(--border);
              display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
    <form method="GET" action="{{ route('unit-admin.renewals-manage') }}" style="display:flex;gap:8px;">
      <select name="member_id" style="padding:4px 10px;font-size:11px;
                 border-radius:20px;border:1px solid var(--border);
                 background:transparent;color:var(--text-dim);width:auto;">
        <option value="">All Members</option>
        @foreach($members as $m)
          <option value="{{ $m->id }}" {{ request('member_id') == $m->id ? 'selected' : '' }}>
            {{ $m->user->name ?? '—' }}
          </option>
        @endforeach
      </select>
      <input type="text" name="type" value="{{ request('type') }}" placeholder="Type (e.g. MEMBERSHIP)"
             style="padding:4px 10px;font-size:11px;border-radius:20px;
                    border:1px solid var(--border);background:transparent;color:var(--text-dim);">
      <button type="submit" class="btn bp bsm"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('unit-admin.renewals-manage') }}" class="btn bo bsm"><i class="fas fa-times"></i></a>
    </form>
  </div>

  <div class="tw">
    <table style="min-width: 840px;">
      <thead>
        <tr>
          <th>#</th>
          <th>Member</th>
          <th>Title</th>
          <th>Type</th>
          <th>Valid From</th>
          <th>Valid Until</th>
          <th>Renewal Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($renewals as $r)
        <tr>
          <td style="color:var(--text-dim)">
            {{ ($renewals->currentPage() - 1) * $renewals->perPage() + $loop->iteration }}
          </td>
          <td>
            <div class="mn">{{ $r->memberProfile->user->name ?? '—' }}</div>
            <div class="me">{{ $r->memberProfile->user->email ?? '' }}</div>
          </td>
          <td class="mn">{{ $r->title }}</td>
          <td style="color:var(--text-dim)">{{ $r->type ?? '—' }}</td>
          <td style="color:var(--text-dim)">{{ $r->valid_from->format('d M Y') }}</td>
          <td style="color:var(--text-dim)">{{ $r->valid_until->format('d M Y') }}</td>
          <td style="color:var(--text-dim)">
            {{ $r->renewal_date ? $r->renewal_date->format('d M Y') : '—' }}
          </td>
          <td>
            <span class="badge {{ $r->status === 'ACTIVE' ? 'bap' : 'brj' }}">
              {{ $r->status }}
            </span>
          </td>
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editRenewal({{ $r->id }})" title="Edit">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST" action="{{ route('unit-admin.renewals-manage.delete') }}"
                    style="display:inline"
                    onsubmit="return confirm('Delete this renewal record for {{ addslashes($r->memberProfile->user->name ?? '') }}?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $r->id }}">
                <button type="submit" class="ab rej" title="Delete">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9" style="text-align:center;color:var(--text-dim);padding:40px;">
            <div style="font-size:32px;margin-bottom:10px;">🔄</div>
            <div style="font-size:13px;font-weight:600;">No renewal records yet</div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($renewals->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;
                flex-wrap:wrap;gap:8px;">
      <span style="font-size:11px;color:var(--text-dim);">
        Showing {{ $renewals->firstItem() }}–{{ $renewals->lastItem() }}
        of {{ $renewals->total() }} records
      </span>
      {{ $renewals->appends(request()->query())->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ ADD RENEWAL MODAL ══ --}}
@php
  $selectedMembers = old('form') === 'create' ? array_map('intval', (array) old('member_profile_ids', [])) : [];
@endphp
<div id="addRenewalM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add Renewal Record</span>
      <div class="mc3" onclick="cm('addRenewalM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('unit-admin.renewals-manage.store') }}" onsubmit="return validateMembers()">
      @csrf

      @if ($errors->any() && old('form') === 'create')
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

      <input type="hidden" name="form" value="create">

      <div class="fgrid">
        <div class="fg s2">
          <label>
            Members
            <span id="memberCount" style="color:var(--text-dim);font-weight:400;">(0 selected)</span>
          </label>

          <div style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
            <input type="text" id="memberSearch" placeholder="Search members..."
                   style="flex:1;" autocomplete="off">
            <label style="display:flex;align-items:center;gap:6px;font-size:12px;
                          white-space:nowrap;cursor:pointer;margin:0;">
              <input type="checkbox" id="selectAllMembers" style="width:auto;margin:0;">
              Select all
            </label>
          </div>

          <div id="memberList"
               style="max-height:190px;overflow-y:auto;border:1px solid var(--border);
                      border-radius:8px;padding:6px;">
            @foreach($members as $m)
              <label class="member-opt"
                     data-search="{{ strtolower(($m->user->name ?? '') . ' ' . ($m->user->email ?? '')) }}"
                     style="display:flex;align-items:center;gap:8px;padding:6px 8px;
                            border-radius:6px;cursor:pointer;font-size:12px;margin:0;">
                <input type="checkbox" name="member_profile_ids[]" value="{{ $m->id }}"
                       style="width:auto;margin:0;"
                       {{ in_array($m->id, $selectedMembers) ? 'checked' : '' }}>
                <span>
                  {{ $m->user->name ?? '—' }}
                  <span style="color:var(--text-dim);">({{ $m->user->email ?? '' }})</span>
                </span>
              </label>
            @endforeach
          </div>
          <div id="memberError" style="display:none;color:#f87171;font-size:11px;margin-top:4px;">
            Select at least one member.
          </div>
        </div>

        <div class="fg">
          <label>Title</label>
          <input type="text" name="title" maxlength="150"
                 value="{{ old('form') === 'create' ? old('title') : '' }}"
                 placeholder="Annual Membership" required>
        </div>
        <div class="fg">
          <label>Type (optional)</label>
          <input type="text" name="type" maxlength="50"
                 value="{{ old('form') === 'create' ? old('type') : '' }}"
                 placeholder="e.g. MEMBERSHIP, ID_CARD, FUND">
        </div>
        <div class="fg">
          <label>Valid From</label>
          <input type="date" name="valid_from"
                 value="{{ old('form') === 'create' ? old('valid_from') : '' }}" required>
        </div>
        <div class="fg">
          <label>Valid Until</label>
          <input type="date" name="valid_until"
                 value="{{ old('form') === 'create' ? old('valid_until') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Notes (optional)</label>
          <textarea name="notes" maxlength="500" placeholder="Any additional notes...">{{ old('form') === 'create' ? old('notes') : '' }}</textarea>
        </div>
      </div>
      <div style="margin:0 18px 14px;padding:10px 12px;
                  background:rgba(79,142,247,0.07);border-radius:8px;
                  font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--accent)"></i>
        One renewal record is created for each selected member. Renewal date is automatically set
        to the day after "Valid Until". Status is Active until the "Valid Until" date passes, then Expired.
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addRenewalM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-plus"></i>Add Renewal</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT RENEWAL MODAL ══ --}}
<div id="editRenewalM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Renewal Record</span>
      <div class="mc3" onclick="cm('editRenewalM')"><i class="fas fa-times"></i></div>
    </div>
    <form method="POST" action="{{ route('unit-admin.renewals-manage.update') }}">
      @csrf

      @if ($errors->any() && old('form') === 'edit')
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

      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editRenewalId">

      <div class="fgrid">
        <div class="fg s2">
          <label>Member</label>
          <select name="member_profile_id" id="editRenewalMember" required>
            <option value="">Select Member</option>
            @foreach($members as $m)
              <option value="{{ $m->id }}">{{ $m->user->name ?? '—' }} ({{ $m->user->email ?? '' }})</option>
            @endforeach
          </select>
        </div>
        <div class="fg">
          <label>Title</label>
          <input type="text" name="title" id="editRenewalTitle" maxlength="150" required>
        </div>
        <div class="fg">
          <label>Type (optional)</label>
          <input type="text" name="type" id="editRenewalType" maxlength="50">
        </div>
        <div class="fg">
          <label>Valid From</label>
          <input type="date" name="valid_from" id="editRenewalFrom" required>
        </div>
        <div class="fg">
          <label>Valid Until</label>
          <input type="date" name="valid_until" id="editRenewalUntil" required>
        </div>
        <div class="fg s2">
          <label>Notes (optional)</label>
          <textarea name="notes" id="editRenewalNotes" maxlength="500"></textarea>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editRenewalM')">Cancel</button>
        <button type="submit" class="btn bp"><i class="fas fa-save"></i>Update</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function editRenewal(id) {
  fetch(`{{ url('/unit-admin/renewals-manage') }}/${id}/detail`)
    .then(r => r.json())
    .then(d => {
      document.getElementById('editRenewalId').value      = d.id;
      document.getElementById('editRenewalMember').value  = d.member_profile_id;
      document.getElementById('editRenewalTitle').value   = d.title;
      document.getElementById('editRenewalType').value    = d.type ?? '';
      document.getElementById('editRenewalFrom').value    = d.valid_from_raw;
      document.getElementById('editRenewalUntil').value   = d.valid_until_raw;
      document.getElementById('editRenewalNotes').value   = d.notes ?? '';
      om('editRenewalM');
    })
    .catch(() => alert('Failed to load renewal data!'));
}

// ── Multi-member selection (Add modal) ──
(function () {
  const list      = document.getElementById('memberList');
  const search    = document.getElementById('memberSearch');
  const selectAll = document.getElementById('selectAllMembers');
  const counter   = document.getElementById('memberCount');
  const errorBox  = document.getElementById('memberError');

  const boxes   = () => list.querySelectorAll('input[type="checkbox"]');
  const visible = () => [...list.querySelectorAll('.member-opt')]
                          .filter(l => l.style.display !== 'none')
                          .map(l => l.querySelector('input'));

  function refresh() {
    const checked = [...boxes()].filter(b => b.checked).length;
    counter.textContent = `(${checked} selected)`;
    const vis = visible();
    selectAll.checked = vis.length > 0 && vis.every(b => b.checked);
    if (checked > 0) errorBox.style.display = 'none';
  }

  search.addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    list.querySelectorAll('.member-opt').forEach(l => {
      l.style.display = l.dataset.search.includes(q) ? 'flex' : 'none';
    });
    refresh();
  });

  selectAll.addEventListener('change', function () {
    visible().forEach(b => b.checked = this.checked);
    refresh();
  });

  list.addEventListener('change', refresh);
  refresh();
})();

function validateMembers() {
  const any = document.querySelectorAll('#memberList input:checked').length > 0;
  document.getElementById('memberError').style.display = any ? 'none' : 'block';
  return any;
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editRenewalM');
    @else
      om('addRenewalM');
    @endif
  });
@endif
</script>
@endsection