@extends('layouts.admin')
@section('title', 'Districts')
@section('page_title', 'Districts')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-map-marker-alt"></i>All Districts
    </div>
    <button class="btn bp bsm" onclick="resetAddDistModal(); om('addDistM')">
      <i class="fas fa-plus"></i>Add District
    </button>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>District Name</th>
          @unless($fixedState)
            <th>State</th>
          @endunless
          <th>Units</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($districts as $district)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $district->name }}</b></td>
          @unless($fixedState)
            <td style="color:var(--text-dim)">
              {{ $district->state->name }}
            </td>
          @endunless
          <td>{{ $district->units_count }}</td>

          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editDistrict(
                  {{ $district->id }},
                  '{{ addslashes($district->name) }}',
                  {{ $district->state_id }})">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('admin.districts.delete') }}"
                onsubmit="return confirm('Delete {{ addslashes($district->name) }}?')"
                style="display:inline">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $district->id }}">
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
            No districts yet
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div id="addDistM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add District</span>
      <div class="mc3" onclick="resetAddDistModal(); cm('addDistM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.districts.store') }}" id="addDistForm">
      @csrf
      <input type="hidden" name="form" value="create">

      @if ($errors->any() && old('form') === 'create')
        <div id="addDistError" style="margin:14px 18px 0;padding:10px 12px;
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

      <div class="fgrid {{ $fixedState ? 'c1' : '' }}">
        @if($fixedState)
          <input type="hidden" name="state_id" value="{{ $fixedState->id }}">
        @else
          <div class="fg">
            <label>State</label>
            <select name="state_id" id="addDistStateSelect" required>
              <option value="">Select State</option>
              @foreach($states as $state)
                <option value="{{ $state->id }}"
                  @selected(old('form') === 'create' && old('state_id') == $state->id)>
                  {{ $state->name }}
                </option>
              @endforeach
            </select>
          </div>
        @endif
        <div class="fg">
          <label>District Name</label>
          <input type="text" name="name" id="addDistName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'create' ? old('name') : '' }}"
                 placeholder="Enter district name" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetAddDistModal(); cm('addDistM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-plus"></i>Add District
        </button>
      </div>
    </form>
  </div>
</div>

<div id="editDistM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit District</span>
      <div class="mc3" onclick="resetEditDistModal(); cm('editDistM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.districts.update') }}" id="editDistForm">
      @csrf @method('PUT')
      <input type="hidden" name="form" value="edit">
      <input type="hidden" name="id" id="editDistId">

      @if ($errors->any() && old('form') === 'edit')
        <div id="editDistError" style="margin:14px 18px 0;padding:10px 12px;
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

      <div class="fgrid {{ $fixedState ? 'c1' : '' }}">
        @if($fixedState)
          <input type="hidden" name="state_id" value="{{ $fixedState->id }}">
        @else
          <div class="fg">
            <label>State</label>
            <select name="state_id" id="editDistState" required>
              @foreach($states as $state)
                <option value="{{ $state->id }}">{{ $state->name }}</option>
              @endforeach
            </select>
          </div>
        @endif
        <div class="fg">
          <label>District Name</label>
          <input type="text" name="name" id="editDistName"
                 maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetEditDistModal(); cm('editDistM')">Cancel</button>
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
// ── Reset "Add District" modal: clears stale error banner + inputs ──
function resetAddDistModal() {
  const errBox = document.getElementById('addDistError');
  if (errBox) errBox.remove();

  const name = document.getElementById('addDistName');
  if (name) name.value = '';

  // This select doesn't exist when Kerala is fixed, so we check first
  const state = document.getElementById('addDistStateSelect');
  if (state) state.value = '';
}

// ── Reset "Edit District" modal: clears stale error banner ──
function resetEditDistModal() {
  const errBox = document.getElementById('editDistError');
  if (errBox) errBox.remove();
}

function editDistrict(id, name, stateId) {
  resetEditDistModal();
  document.getElementById('editDistId').value   = id;
  document.getElementById('editDistName').value = name;

  const state = document.getElementById('editDistState');
  if (state) state.value = stateId;

  om('editDistM');
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      document.getElementById('editDistId').value = '{{ old('id') }}';
      const st = document.getElementById('editDistState');
      if (st) st.value = '{{ old('state_id') }}';
      om('editDistM');
    @else
      om('addDistM');
    @endif
  });
@endif
</script>
@endsection