@extends('layouts.district-admin')
@section('title', 'Units')
@section('page_title', 'Units')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-building"></i>Units in {{ $district->name ?? 'Your District' }}</div>
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
          <th>Members</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($units as $unit)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td><b>{{ $unit->name }}</b></td>
          <td><span class="badge bli">{{ $unit->member_profiles_count }} members</span></td>
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editUnit({{ $unit->id }}, '{{ addslashes($unit->name) }}')">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('district-admin.units.delete') }}"
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
          <td colspan="4">
            <div class="empty-state">
              <i class="fas fa-building"></i>
              <p>No Units yet in your district. Add one to get started.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ══ ADD UNIT MODAL ══ --}}
<div id="addUnitM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Add Unit</span>
      <div class="mc3" onclick="resetAddUnitModal(); cm('addUnitM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('district-admin.units.store') }}" id="addUnitForm">
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

      <div style="margin:0 18px 14px;padding:10px 12px;
                  background:rgba(245,200,66,0.07);border-radius:8px;
                  font-size:11px;color:var(--text-dim);">
        <i class="fas fa-info-circle" style="color:var(--gold)"></i>
        This unit will be created inside <b style="color:var(--orange)">{{ $district->name ?? 'your district' }}</b>.
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

{{-- ══ EDIT UNIT MODAL ══ --}}
<div id="editUnitM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Unit</span>
      <div class="mc3" onclick="resetEditUnitModal(); cm('editUnitM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('district-admin.units.update') }}" id="editUnitForm">
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
          <i class="fas fa-save"></i>Update Unit
        </button>
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
// ── Reset "Add Unit" modal ──
function resetAddUnitModal() {
  const errBox = document.getElementById('addUnitError');
  if (errBox) errBox.remove();

  const name = document.getElementById('addUnitName');
  if (name) name.value = '';
}

// ── Reset "Edit Unit" modal ──
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
      document.getElementById('editUnitId').value = '{{ old('id') }}';
      om('editUnitM');
    @else
      om('addUnitM');
    @endif
  });
@endif
</script>
@endsection