@extends('layouts.admin')
@section('title', 'States')
@section('page_title', 'States')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-map"></i>All States</div>
    <button class="btn bp bsm" onclick="resetAddStateModal(); om('addStateM')">
      <i class="fas fa-plus"></i>Add State
    </button>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>State Name</th>
          <th>Districts</th>
          <th>Units</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($states as $state)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $state->name }}</b></td>
          <td>{{ $state->districts_count }}</td>
          <td>{{ $state->units_count }}</td>
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editState({{ $state->id }},'{{ addslashes($state->name) }}')">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('admin.states.delete') }}"
                onsubmit="return confirm('Delete {{ addslashes($state->name) }}?')"
                style="display:inline">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $state->id }}">
                <button type="submit" class="ab rej">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
              
              
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5"
            style="text-align:center;color:var(--text-dim);padding:30px;">
            No states yet
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div id="addStateM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add State</span>
      <div class="mc3" onclick="resetAddStateModal(); cm('addStateM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.states.store') }}" id="addStateForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addStateError" style="margin:14px 18px 0;padding:10px 12px;
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

      <div class="fgrid c1">
        <div class="fg">
          <label>State Name</label>
          <input type="text" name="name" id="addStateName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'create' ? old('name') : '' }}"
                 placeholder="Enter state name" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetAddStateModal(); cm('addStateM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-plus"></i>Add State
        </button>
      </div>
    </form>
  </div>
</div>

<div id="editStateM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit State</span>
      <div class="mc3" onclick="resetEditStateModal(); cm('editStateM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.states.update') }}" id="editStateForm">
      @csrf @method('PUT')
      <input type="hidden" name="form" value="edit">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editStateError" style="margin:14px 18px 0;padding:10px 12px;
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

      <div class="fgrid c1">
        <div class="fg">
          <label>State Name</label>
          <input type="hidden" name="id" id="editStateId"
                 value="{{ old('form') === 'edit' ? old('id') : '' }}">
          <input type="text" name="name"
                 id="editStateName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'edit' ? old('name') : '' }}"
                 required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetEditStateModal(); cm('editStateM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-save"></i>Update
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
// ── Reset "Add State" modal: clears stale error banner + input ──
function resetAddStateModal() {
  const errBox = document.getElementById('addStateError');
  if (errBox) errBox.remove();

  const input = document.getElementById('addStateName');
  if (input) input.value = '';
}

// ── Reset "Edit State" modal: clears stale error banner ──
// (name/id get overwritten by editState() anyway, but we clear
//  the error banner so it doesn't linger for the next state you edit)
function resetEditStateModal() {
  const errBox = document.getElementById('editStateError');
  if (errBox) errBox.remove();
}

function editState(id, name) {
  resetEditStateModal();
  document.getElementById('editStateId').value   = id;
  document.getElementById('editStateName').value = name;
  om('editStateM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      document.getElementById('editStateId').value   = '{{ old('id') }}';
      om('editStateM');
    @else
      om('addStateM');
    @endif
  });
@endif
</script>
@endsection