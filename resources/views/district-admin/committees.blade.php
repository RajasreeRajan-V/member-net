@extends('layouts.district-admin')
@section('title', 'Committees')
@section('page_title', 'Committees')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-users-cog"></i> Committees</div>
    <button class="btn bp bsm" onclick="resetCreateCommitteeModal(); om('createCommitteeModal')">
      <i class="fas fa-plus"></i> New Committee
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Start – End</th>
          <th>Members</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($committees as $committee)
          <tr>
            <td style="white-space:normal;">
              <div style="font-weight:600;">{{ $committee->name }}</div>
              @if($committee->description)
                <div class="me">{{ \Illuminate\Support\Str::limit($committee->description, 60) }}</div>
              @endif
            </td>
            <td>
              {{ $committee->start_date->format('d M Y') }}
              @if($committee->end_date) – {{ $committee->end_date->format('d M Y') }} @else — Ongoing @endif
            </td>
            <td>
              <a href="{{ route('district-admin.committees.members', $committee->id) }}" class="ca">
                {{ $committee->members_count }} member(s) · Manage
              </a>
            </td>
            <td>
              <span class="badge {{ $committee->is_active ? 'bap' : 'brj' }}">
                {{ $committee->is_active ? 'ACTIVE' : 'INACTIVE' }}
              </span>
            </td>
            <td>
              <div class="abr">
                <button type="button" class="ab ed" title="Edit" onclick="editCommittee({{ $committee->id }})">
                  <i class="fas fa-pen"></i>
                </button>

                <form action="{{ route('district-admin.committees.toggle') }}" method="POST" style="display:inline;">
                  @csrf
                  <input type="hidden" name="id" value="{{ $committee->id }}">
                  <button type="submit" class="ab" title="{{ $committee->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="fas fa-power-off"></i>
                  </button>
                </form>

                <form action="{{ route('district-admin.committees.delete') }}" method="POST" style="display:inline;"
                      onsubmit="return confirm('Delete this committee?')">
                  @csrf @method('DELETE')
                  <input type="hidden" name="id" value="{{ $committee->id }}">
                  <button type="submit" class="ab rej" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--text-dim);white-space:normal;padding:30px;">No committees created yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($committees->hasPages())
    <div style="padding:12px 16px;border-top:1px solid var(--border);">
      {{ $committees->links('pagination.custom') }}
    </div>
  @endif
</div>

{{-- ══ CREATE MODAL ══ --}}
<div class="mo" id="createCommitteeModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">New Committee</div>
      <div class="mc3" onclick="resetCreateCommitteeModal(); cm('createCommitteeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('district-admin.committees.store') }}" method="POST" id="createCommitteeForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="createCommitteeError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Name</label>
          <input type="text" name="name" maxlength="200" value="{{ old('form') === 'create' ? old('name') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" rows="3">{{ old('form') === 'create' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" value="{{ old('form') === 'create' ? old('start_date') : '' }}" required>
        </div>
        <div class="fg">
          <label>End Date (optional)</label>
          <input type="date" name="end_date" value="{{ old('form') === 'create' ? old('end_date') : '' }}">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetCreateCommitteeModal(); cm('createCommitteeModal')">Cancel</button>
        <button type="submit" class="btn bp">Create Committee</button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="mo" id="editCommitteeModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">Edit Committee</div>
      <div class="mc3" onclick="resetEditCommitteeModal(); cm('editCommitteeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('district-admin.committees.update') }}" method="POST" id="editCommitteeForm">
      @csrf
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="edit_committee_id" value="{{ old('form') === 'edit' ? old('id') : '' }}">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editCommitteeError" style="margin:14px 18px 0;padding:10px 12px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;font-size:12px;color:#f87171;">
          <ul style="margin:0;padding-left:16px;">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
          </ul>
        </div>
      @endif

      <div class="fgrid">
        <div class="fg s2">
          <label>Name</label>
          <input type="text" name="name" id="edit_name" maxlength="200" value="{{ old('form') === 'edit' ? old('name') : '' }}" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="edit_description" rows="3">{{ old('form') === 'edit' ? old('description') : '' }}</textarea>
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" id="edit_start_date" value="{{ old('form') === 'edit' ? old('start_date') : '' }}" required>
        </div>
        <div class="fg">
          <label>End Date (optional)</label>
          <input type="date" name="end_date" id="edit_end_date" value="{{ old('form') === 'edit' ? old('end_date') : '' }}">
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="resetEditCommitteeModal(); cm('editCommitteeModal')">Cancel</button>
        <button type="submit" class="btn bp">Save Changes</button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function clearForm(form) {
  form.querySelectorAll('input:not([type=hidden]), textarea').forEach(el => el.value = '');
}

function resetCreateCommitteeModal() {
  const err = document.getElementById('createCommitteeError');
  if (err) err.remove();
  clearForm(document.getElementById('createCommitteeForm'));
}

function resetEditCommitteeModal() {
  const err = document.getElementById('editCommitteeError');
  if (err) err.remove();
  clearForm(document.getElementById('editCommitteeForm'));
  document.getElementById('edit_committee_id').value = '';
}

function editCommittee(id) {
  resetEditCommitteeModal();
  fetch(`{{ url('/district-admin/committees') }}/${id}/detail`, { headers: { 'Accept': 'application/json' } })
    .then(res => {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      return res.json();
    })
    .then(data => {
      document.getElementById('edit_committee_id').value = id;
      document.getElementById('edit_name').value         = data.name;
      document.getElementById('edit_description').value  = data.description ?? '';
      document.getElementById('edit_start_date').value   = data.start_date_raw;
      document.getElementById('edit_end_date').value     = data.end_date_raw ?? '';
      om('editCommitteeModal');
    })
    .catch(err => alert('Failed to load committee data! ' + err.message));
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editCommitteeModal');
    @elseif (old('form') === 'create')
      om('createCommitteeModal');
    @endif
  });
@endif
</script>
@endsection