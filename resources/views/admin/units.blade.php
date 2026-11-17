@extends('layouts.admin')
@section('title', 'Units')
@section('page_title', 'Units')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-building"></i>All Units</div>
    <button class="btn bp bsm" onclick="resetAddUnitModal(); om('addUnitM')">
      <i class="fas fa-plus"></i>Add Unit
    </button>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Unit Name</th>
          <th>District</th>
          @unless($fixedState)
            <th>State</th>
          @endunless
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($units as $unit)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $unit->name }}</b></td>
          <td style="color:var(--text-dim)">
            {{ $unit->district->name }}
          </td>
          @unless($fixedState)
            <td style="color:var(--text-dim)">
              {{ $unit->district->state->name }}
            </td>
          @endunless
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editUnit(
                  {{ $unit->id }},
                  '{{ addslashes($unit->name) }}',
                  {{ $unit->district_id }},
                  {{ $unit->district->state_id }})">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('admin.units.delete') }}"
                onsubmit="return confirm('Delete {{ addslashes($unit->name) }}?')"
                style="display:inline">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $unit->id }}">
                <button type="submit" class="ab rej">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="{{ $fixedState ? 4 : 5 }}"
            style="text-align:center;color:var(--text-dim);padding:30px;">
            No units yet
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div id="addUnitM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add Unit</span>
      <div class="mc3" onclick="resetAddUnitModal(); cm('addUnitM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.units.store') }}" id="addUnitForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addUnitError" style="margin:14px 18px 0;padding:10px 12px;
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
        @if($fixedState)
          {{-- Kerala is fixed: no State dropdown, districts are already loaded --}}
          <div class="fg">
            <label>District</label>
            <select name="district_id" id="addUnitDistrict" required>
              <option value="">Select District</option>
              @foreach($districts as $d)
                <option value="{{ $d->id }}"
                  @selected(old('form') === 'create' && old('district_id') == $d->id)>
                  {{ $d->name }}
                </option>
              @endforeach
            </select>
          </div>
        @else
          <div class="fg">
            <label>State</label>
            <select id="addUnitState"
                    onchange="loadDistricts(this.value)">
              <option value="">Select State</option>
              @foreach($states as $state)
                <option value="{{ $state->id }}">
                  {{ $state->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="fg">
            <label>District</label>
            <select name="district_id" id="addUnitDistrict" required>
              <option value="">Select State First</option>
            </select>
          </div>
        @endif
        <div class="fg">
          <label>Unit Name</label>
          <input type="text" name="name" id="addUnitName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'create' ? old('name') : '' }}"
                 placeholder="Enter Unit name" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetAddUnitModal(); cm('addUnitM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-plus"></i>Add Unit
        </button>
      </div>
    </form>
  </div>
</div>

<div id="editUnitM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Unit</span>
      <div class="mc3" onclick="resetEditUnitModal(); cm('editUnitM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.units.update') }}" id="editUnitForm">
      @csrf @method('PUT')
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editUnitId">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editUnitError" style="margin:14px 18px 0;padding:10px 12px;
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
          <label>Unit Name</label>
          <input type="text" name="name"
                 id="editUnitName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetEditUnitModal(); cm('editUnitM')">Cancel</button>
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
const KERALA_FIXED = {{ $fixedState ? 'true' : 'false' }};

function loadDistricts(stateId) {
  if (!stateId) return;

  const select = document.getElementById('addUnitDistrict');
  select.innerHTML = '<option value="">Loading...</option>';

  fetch(`{{ route('admin.districts.by-state') }}`, {
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
    select.innerHTML = '<option value="">Select District</option>';
    if (data.data && data.data.length > 0) {
      data.data.forEach(d => {
        select.innerHTML += `<option value="${d.id}">${d.name}</option>`;
      });
    } else {
      select.innerHTML = '<option value="">No districts found</option>';
    }
  })
  .catch(err => {
    select.innerHTML = '<option value="">Error loading districts</option>';
    console.error('District load failed:', err);
  });
}

// ── Reset "Add Unit" modal: clears stale error banner + inputs ──
function resetAddUnitModal() {
  const errBox = document.getElementById('addUnitError');
  if (errBox) errBox.remove();

  const name = document.getElementById('addUnitName');
  if (name) name.value = '';

  const distSelect = document.getElementById('addUnitDistrict');

  if (KERALA_FIXED) {
    // District list is already filled with Kerala districts: just deselect
    if (distSelect) distSelect.value = '';
  } else {
    const stateSelect = document.getElementById('addUnitState');
    if (stateSelect) stateSelect.value = '';
    if (distSelect) distSelect.innerHTML = '<option value="">Select State First</option>';
  }
}

// ── Reset "Edit Unit" modal: clears stale error banner ──
function resetEditUnitModal() {
  const errBox = document.getElementById('editUnitError');
  if (errBox) errBox.remove();
}

function editUnit(id, name) {
  resetEditUnitModal();
  document.getElementById('editUnitId').value   = id;
  document.getElementById('editUnitName').value = name;
  om('editUnitM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      document.getElementById('editUnitId').value   = '{{ old('id') }}';
      om('editUnitM');
    @else
      om('addUnitM');
    @endif
  });
@endif
</script>
@endsection