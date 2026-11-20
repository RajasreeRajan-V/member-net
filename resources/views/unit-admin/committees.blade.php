@extends('unit-admin.layout')
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
    <table style="min-width: 700px;">
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
            <td style="white-space:normal;font-weight:600;">{{ $committee->name }}</td>
            <td>
              {{ $committee->start_date->format('d M Y') }}
              @if($committee->end_date) – {{ $committee->end_date->format('d M Y') }} @else — Ongoing @endif
            </td>
            <td>
              <a href="{{ route('unit-admin.committees.members', $committee->id) }}" class="ca">
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
                <button class="ab ed" title="Edit"
                  onclick="editCommittee({{ $committee->id }})">
                  <i class="fas fa-pen"></i>
                </button>

                <form action="{{ route('unit-admin.committees.toggle') }}" method="POST" style="display:inline;">
                  @csrf
                  <input type="hidden" name="id" value="{{ $committee->id }}">
                  <button type="submit" class="ab" title="{{ $committee->is_active ? 'Deactivate' : 'Activate' }}">
                    <i class="fas fa-power-off"></i>
                  </button>
                </form>

                <form action="{{ route('unit-admin.committees.delete') }}" method="POST" style="display:inline;"
                  onsubmit="return confirm('Delete this committee?')">
                  @csrf
                  <input type="hidden" name="id" value="{{ $committee->id }}">
                  <button type="submit" class="ab rej" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--text-dim);white-space:normal;">No committees created yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ══ CREATE MODAL ══ --}}
<div class="mo" id="createCommitteeModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">New Committee</div>
      <div class="mc3" onclick="resetCreateCommitteeModal(); cm('createCommitteeModal')"><i class="fas fa-times"></i></div>
    </div>
    <form action="{{ route('unit-admin.committees.store') }}" method="POST" id="createCommitteeForm">
      @csrf
      <div class="fgrid">
        <div class="fg s2">
          <label>Name</label>
          <input type="text" name="name" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" rows="3"></textarea>
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" required>
        </div>
        <div class="fg">
          <label>End Date (optional)</label>
          <input type="date" name="end_date">
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
    <form action="{{ route('unit-admin.committees.update') }}" method="POST" id="editCommitteeForm">
      @csrf
      <input type="hidden" name="id" id="edit_committee_id">
      <div class="fgrid">
        <div class="fg s2">
          <label>Name</label>
          <input type="text" name="name" id="edit_name" required>
        </div>
        <div class="fg s2">
          <label>Description</label>
          <textarea name="description" id="edit_description" rows="3"></textarea>
        </div>
        <div class="fg">
          <label>Start Date</label>
          <input type="date" name="start_date" id="edit_start_date" required>
        </div>
        <div class="fg">
          <label>End Date (optional)</label>
          <input type="date" name="end_date" id="edit_end_date">
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
// ── Reset "New Committee" modal: clears all inputs ──
function resetCreateCommitteeModal() {
  const form = document.getElementById('createCommitteeForm');
  if (form) form.reset();
}

// ── Reset "Edit Committee" modal: clears all inputs + hidden id ──
function resetEditCommitteeModal() {
  const form = document.getElementById('editCommitteeForm');
  if (form) form.reset();

  document.getElementById('edit_committee_id').value = '';
}

function editCommittee(id) {
  resetEditCommitteeModal(); // clear stale data before the fetch resolves
  fetch(`{{ url('/unit-admin/committees') }}/${id}/detail`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('edit_committee_id').value = id;
      document.getElementById('edit_name').value = data.name;
      document.getElementById('edit_description').value = data.description ?? '';
      document.getElementById('edit_start_date').value = data.start_date_raw;
      document.getElementById('edit_end_date').value = data.end_date_raw ?? '';
      om('editCommitteeModal');
    })
    .catch(() => alert('Failed to load committee data!'));
}
</script>
@endsection