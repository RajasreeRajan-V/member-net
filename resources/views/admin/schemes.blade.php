@extends('layouts.admin')
@section('title', 'Schemes')
@section('page_title', 'Schemes')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-piggy-bank"></i> Schemes</div>
    <button class="btn bp bsm" onclick="om('createSchemeModal')">
      <i class="fas fa-plus"></i> New Scheme
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Amount / Month</th>
          <th>Duration</th>
          <th>Start – End</th>
          <th>Created By</th>
          <th>Assigned Members</th>
          <th>Dues Generated</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($schemes as $scheme)
          @php
            $creator = $scheme->createdBy;
            $role    = $creator->role ?? null;

            if ($role === 'unit_admin') {
                $label = 'Unit Admin';
                $color = 'var(--accent)';
                $scope = $unitAdmins[$scheme->created_by]->unit->name ?? null;
            } elseif ($role === 'district_admin') {
                $label = 'District Admin';
                $color = 'var(--green)';
                $scope = $districtAdmins[$scheme->created_by]->district->name ?? null;
            } elseif ($creator) {
                $label = 'Super Admin';
                $color = 'var(--red)';
                $scope = null;
            } else {
                $label = 'Unknown';
                $color = 'var(--text-dim)';
                $scope = null;
            }
          @endphp
          <tr>
            <td style="white-space:normal;font-weight:600;">{{ $scheme->title }}</td>
            <td>₹{{ number_format($scheme->amount, 2) }}</td>
            <td>{{ $scheme->duration_months }} months</td>
            <td>
              {{ $scheme->start_date->format('d M Y') }}
              @if($scheme->end_date) – {{ $scheme->end_date->format('d M Y') }} @endif
            </td>

            {{-- CREATED BY --}}
            <td>
              @if($creator)
                <div class="mn">{{ $creator->name }}</div>
              @endif
              <div style="margin-top:3px;">
                <span style="font-size:10px;padding:2px 8px;border-radius:20px;
                             border:1px solid var(--border);color:{{ $color }};">
                  {{ $label }}
                </span>
              </div>
              @if($scope)
                <div class="me">{{ $scope }}</div>
              @endif
            </td>

            <td>
              <button type="button" class="ca" style="background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;"
                      onclick="viewSchemeMembers({{ $scheme->id }})">
                {{ $scheme->members_count }} member(s) — View
              </button>
            </td>
            <td>
              <a href="{{ route('admin.schemes.payers', $scheme->id) }}" class="ca">
                {{ $scheme->dues_count }} dues
              </a>
            </td>
            <td>
              <span class="badge {{ $scheme->is_active ? 'bap' : 'brj' }}">
                {{ $scheme->is_active ? 'ACTIVE' : 'INACTIVE' }}
              </span>
            </td>
            <td>
              <div class="abr">
                <button class="ab ed" title="Edit"
                  onclick="editScheme({{ $scheme->id }})">
                  <i class="fas fa-pen"></i>
                </button>

                <form action="{{ route('admin.schemes.toggle') }}" method="POST" style="display:inline;">
                  @csrf
                  <input type="hidden" name="id" value="{{ $scheme->id }}">
                  <button type="submit" class="ab" title="{{ $scheme->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="fas fa-power-off"></i>
                  </button>
                </form>

                <form action="{{ route('admin.schemes.delete') }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('Delete this scheme?')">
                  @csrf
                  <input type="hidden" name="id" value="{{ $scheme->id }}">
                  <button type="submit" class="ab rej" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" style="text-align:center;color:var(--text-dim);white-space:normal;">No schemes created yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- ── PAGINATION (same styled block as Members / Expenses) ── --}}
  @if($schemes->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);
                display:flex;align-items:center;justify-content:space-between;
                flex-wrap:wrap;gap:8px;">
      <span style="font-size:11px;color:var(--text-dim);">
        Showing {{ $schemes->firstItem() }}–{{ $schemes->lastItem() }}
        of {{ $schemes->total() }} items
      </span>
      {{ $schemes->appends(request()->query())->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ CREATE MODAL ══ --}}
<div class="mo" id="createSchemeModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">New Scheme</div>
      <div class="mc3" onclick="cm('createSchemeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('admin.schemes.store') }}" method="POST">
      @csrf
      <input type="hidden" name="form_type" value="create_scheme">

      @if ($errors->any() && old('form_type') === 'create_scheme')
        <div style="margin:0 18px 12px;padding:10px 12px;border-radius:8px;background:rgba(220,53,69,0.08);border:1px solid var(--red);color:var(--red);font-size:12px;">
          <strong>Please fix the following:</strong>
          <ul style="margin:6px 0 0 18px;padding:0;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Title</label>
          <input type="text" name="title" value="{{ old('form_type') === 'create_scheme' ? old('title') : '' }}" required>
          @error('title') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" rows="3">{{ old('form_type') === 'create_scheme' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Amount per Month</label>
          <input type="number" step="0.01" name="amount" value="{{ old('form_type') === 'create_scheme' ? old('amount') : '' }}" required>
          @error('amount') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Duration (Months)</label>
          <input type="number" name="duration_months" value="{{ old('form_type') === 'create_scheme' ? old('duration_months') : '' }}" required>
          @error('duration_months') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" id="create_start_date"
                 value="{{ old('form_type') === 'create_scheme' ? old('start_date') : '' }}"
                 onchange="document.getElementById('create_end_date').min = this.value;"
                 required>
          @error('start_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>End Date (optional)</label>
          <input type="date" name="end_date" id="create_end_date"
                 value="{{ old('form_type') === 'create_scheme' ? old('end_date') : '' }}">
          @error('end_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>

        <div class="fg s2">
          <label>
            Select Members
            <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-dim);">
              (only chosen members will see and pay this scheme)
            </span>
          </label>
          @error('member_ids') <span style="color:var(--red);font-size:11px;display:block;margin-bottom:6px;">{{ $message }}</span> @enderror

          <input type="text" placeholder="🔍 Search members..."
                 onkeyup="filterMemberList('create_member_list', this.value)"
                 style="margin-bottom:8px;">

          <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('create_member_list', true, 'create_selected_count')">Select All</button>
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('create_member_list', false, 'create_selected_count')">Clear All</button>
            <span id="create_selected_count" style="font-size:11px;color:var(--text-dim);margin-left:auto;">0 selected</span>
          </div>

          <div id="create_member_list"
               style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
            @foreach ($allMembers as $m)
              <label class="member-check-row"
                     style="display:flex;align-items:center;gap:10px;padding:7px 8px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(79,142,247,0.08)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="member_ids[]" value="{{ $m->id }}"
                       {{ (old('form_type') === 'create_scheme' && in_array($m->id, old('member_ids', []))) ? 'checked' : '' }}
                       onchange="updateSelectedCount('create_member_list', 'create_selected_count')"
                       style="width:16px;height:16px;flex-shrink:0;">
                <span style="font-size:13px;">
                  {{ $m->user->name ?? '—' }}
                  <span style="color:var(--text-dim);font-size:11px;">({{ $m->user->email ?? '' }})</span>
                </span>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('createSchemeModal')">Cancel</button>
        <button type="submit" class="btn bp">Create Scheme</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="mo" id="editSchemeModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">Edit Scheme</div>
      <div class="mc3" onclick="cm('editSchemeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('admin.schemes.update') }}" method="POST">
      @csrf
      <input type="hidden" name="form_type" value="edit_scheme">
      <input type="hidden" name="id" id="edit_scheme_id" value="{{ old('form_type') === 'edit_scheme' ? old('id') : '' }}">

      @if ($errors->any() && old('form_type') === 'edit_scheme')
        <div style="margin:0 18px 12px;padding:10px 12px;border-radius:8px;background:rgba(220,53,69,0.08);border:1px solid var(--red);color:var(--red);font-size:12px;">
          <strong>Please fix the following:</strong>
          <ul style="margin:6px 0 0 18px;padding:0;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Title</label>
          <input type="text" name="title" id="edit_title"
                 value="{{ old('form_type') === 'edit_scheme' ? old('title') : '' }}" required>
          @error('title') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="edit_description" rows="3">{{ old('form_type') === 'edit_scheme' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Amount per Month</label>
          <input type="number" step="0.01" name="amount" id="edit_amount"
                 value="{{ old('form_type') === 'edit_scheme' ? old('amount') : '' }}" required>
          @error('amount') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Duration (Months)</label>
          <input type="number" name="duration_months" id="edit_duration_months"
                 value="{{ old('form_type') === 'edit_scheme' ? old('duration_months') : '' }}" required>
          @error('duration_months') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" id="edit_start_date"
                 value="{{ old('form_type') === 'edit_scheme' ? old('start_date') : '' }}"
                 onchange="document.getElementById('edit_end_date').min = this.value;"
                 required>
          @error('start_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>
        <div class="fg">
          <label>End Date</label>
          <input type="date" name="end_date" id="edit_end_date"
                 value="{{ old('form_type') === 'edit_scheme' ? old('end_date') : '' }}">
          @error('end_date') <span style="color:var(--red);font-size:11px;">{{ $message }}</span> @enderror
        </div>

        <div class="fg s2">
          <label>
            Select Members
            <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-dim);">
              (only chosen members will see and pay this scheme)
            </span>
          </label>
          @error('member_ids') <span style="color:var(--red);font-size:11px;display:block;margin-bottom:6px;">{{ $message }}</span> @enderror

          <input type="text" placeholder="🔍 Search members..."
                 onkeyup="filterMemberList('edit_member_list', this.value)"
                 style="margin-bottom:8px;">

          <div style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('edit_member_list', true, 'edit_selected_count')">Select All</button>
            <button type="button" class="btn bo bsm" onclick="toggleAllCheckboxes('edit_member_list', false, 'edit_selected_count')">Clear All</button>
            <span id="edit_selected_count" style="font-size:11px;color:var(--text-dim);margin-left:auto;">0 selected</span>
          </div>

          <div id="edit_member_list"
               style="max-height:220px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;padding:8px;">
            @foreach ($allMembers as $m)
              <label class="member-check-row"
                     style="display:flex;align-items:center;gap:10px;padding:7px 8px;border-radius:6px;cursor:pointer;"
                     onmouseover="this.style.background='rgba(79,142,247,0.08)'"
                     onmouseout="this.style.background='transparent'">
                <input type="checkbox" name="member_ids[]" value="{{ $m->id }}"
                       {{ (old('form_type') === 'edit_scheme' && in_array($m->id, old('member_ids', []))) ? 'checked' : '' }}
                       onchange="updateSelectedCount('edit_member_list', 'edit_selected_count')"
                       style="width:16px;height:16px;flex-shrink:0;">
                <span style="font-size:13px;">
                  {{ $m->user->name ?? '—' }}
                  <span style="color:var(--text-dim);font-size:11px;">({{ $m->user->email ?? '' }})</span>
                </span>
              </label>
            @endforeach
          </div>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editSchemeModal')">Cancel</button>
        <button type="submit" class="btn bp">Save Changes</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ VIEW MEMBERS MODAL ══ --}}
<div class="mo" id="viewMembersModal">
  <div class="md" style="width:420px">
    <div class="mh">
      <div class="mt2" id="viewMembersTitle">Assigned Members</div>
      <div class="mc3" onclick="cm('viewMembersModal')"><i class="fas fa-times"></i></div>
    </div>
    <div id="viewMembersList" style="max-height:400px;overflow-y:auto;padding:0 18px 18px;"></div>
  </div>
</div>

@endsection

@section('scripts')
<script>
function toggleAllCheckboxes(containerId, checked, countId) {
  document.querySelectorAll(`#${containerId} .member-check-row`).forEach(row => {
    if (row.style.display !== 'none') {
      row.querySelector('input[type="checkbox"]').checked = checked;
    }
  });
  updateSelectedCount(containerId, countId);
}

function updateSelectedCount(containerId, countId) {
  const checked = document.querySelectorAll(`#${containerId} input[type="checkbox"]:checked`).length;
  document.getElementById(countId).textContent = checked + ' selected';
}

function filterMemberList(containerId, query) {
  query = query.toLowerCase();
  document.querySelectorAll(`#${containerId} .member-check-row`).forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(query) ? 'flex' : 'none';
  });
}

function editScheme(id) {
  fetch(`{{ url('/admin/schemes') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit_scheme_id').value = id;
      document.getElementById('edit_title').value = data.title;
      document.getElementById('edit_description').value = data.description ?? '';
      document.getElementById('edit_amount').value = data.amount;
      document.getElementById('edit_duration_months').value = data.duration_months;
      document.getElementById('edit_start_date').value = data.start_date_raw;
      document.getElementById('edit_end_date').min = data.start_date_raw;
      document.getElementById('edit_end_date').value = data.end_date_raw ?? '';

      const assignedIds = (data.member_ids || []).map(String);
      document.querySelectorAll('#edit_member_list input[type="checkbox"]').forEach(cb => {
        cb.checked = assignedIds.includes(cb.value);
      });
      updateSelectedCount('edit_member_list', 'edit_selected_count');

      om('editSchemeModal');
    })
    .catch(() => alert('Failed to load scheme data!'));
}

function viewSchemeMembers(id) {
  fetch(`{{ url('/admin/schemes') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('viewMembersList');
      list.innerHTML = '';

      if (!data.members || data.members.length === 0) {
        list.innerHTML = '<div style="padding:12px 0;color:var(--text-dim);text-align:center;">No members assigned.</div>';
      } else {
        data.members.forEach(m => {
          list.innerHTML += `
            <div style="padding:10px 0;border-bottom:1px solid var(--border);">
              <div style="font-weight:600;font-size:13px;">${m.name}</div>
              <div style="font-size:11px;color:var(--text-dim);">${m.email}</div>
            </div>`;
        });
      }

      document.getElementById('viewMembersTitle').textContent = data.title + ' — Assigned Members';
      om('viewMembersModal');
    })
    .catch(() => alert('Failed to load member list!'));
}

// Re-open whichever modal failed validation, so errors are actually visible.
document.addEventListener('DOMContentLoaded', function () {
  @if ($errors->any())
    @if (old('form_type') === 'create_scheme')
      om('createSchemeModal');
      document.getElementById('create_end_date').min = document.getElementById('create_start_date').value || '';
    @elseif (old('form_type') === 'edit_scheme')
      om('editSchemeModal');
      document.getElementById('edit_end_date').min = document.getElementById('edit_start_date').value || '';
    @endif
  @endif
});
</script>
@endsection