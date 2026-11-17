@extends('layouts.admin')

@section('title', 'District Admins')
@section('page_title', 'District Admins')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct"><i class="fas fa-user-shield"></i>District Admins</div>
    <button class="btn bp bsm" onclick="om('addDaModal')">
      <i class="fas fa-plus"></i> Add District Admin
    </button>
  </div>

  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>District</th>
          <th>State</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($districtAdmins as $da)
        <tr>
          <td>
            <div class="mc2">
              <div class="av2" style="background:rgba(245,200,66,0.15);color:var(--gold);">
                {{ strtoupper(substr($da->user->name, 0, 1)) }}
              </div>
              <div class="mn">{{ $da->user->name }}</div>
            </div>
          </td>
          <td>{{ $da->user->email }}</td>
          <td>{{ $da->user->phone }}</td>
          <td>{{ $da->district->name ?? '—' }}</td>
          <td>{{ $da->district->state->name ?? '—' }}</td>
          <td>
            @if($da->user->is_active)
              <span class="badge bap">Active</span>
            @else
              <span class="badge brj">Inactive</span>
            @endif
          </td>
          <td>
            <div class="abr">
              {{-- ACTIVATE / DEACTIVATE --}}
              <form method="POST" action="{{ route('admin.district-admins.toggle-status') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="id" value="{{ $da->id }}">
                <button type="submit" class="ab" title="{{ $da->user->is_active ? 'Deactivate' : 'Activate' }}">
                  <i class="fas fa-power-off"></i>
                </button>
              </form>

              {{-- DELETE (opens password-confirm modal) --}}
              <div class="ab rej" title="Delete"
                   onclick="openDeleteDA({{ $da->id }}, {{ json_encode($da->user->name) }})">
                <i class="fas fa-trash"></i>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <i class="fas fa-user-shield"></i>
              <p>No district admins yet. Add one to get started.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>


{{-- ══ ADD DISTRICT ADMIN MODAL ══ --}}
<div class="mo" id="addDaModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">Add District Admin</div>
      <div class="mc3" onclick="cm('addDaModal')"><i class="fas fa-times"></i></div>
    </div>

    {{-- Validation errors (only for the add form, so a failed delete never shows here) --}}
    @if($errors->any() && old('form') === 'create')
      <div style="margin:14px 18px 0; padding:10px 14px; border-radius:8px; font-size:12px;
                  background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.3); color:var(--red);">
        <i class="fas fa-times-circle"></i>
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('admin.district-admins.store') }}">
      @csrf
      <input type="hidden" name="form" value="create">
      <div class="fgrid c1">
        <div class="fg">
          <label>Full Name</label>
          <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Rahim Khan" required>
        </div>
        <div class="fg">
          <label>Email</label>
          <input type="email" name="email" value="{{ old('email') }}" placeholder="districtadmin@fumma.com" required>
        </div>
        <div class="fg">
          <label>Phone</label>
          <input type="text" name="phone" value="{{ old('phone') }}" placeholder="9876543210" maxlength="10" required>
        </div>
        <div class="fg">
          <label>District</label>
          <select name="district_id" required>
            <option value="">Select district</option>
            @foreach($districts as $district)
              <option value="{{ $district->id }}" {{ old('district_id') == $district->id ? 'selected' : '' }}>
                {{ $district->name }} ({{ $district->state->name ?? '' }})
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('addDaModal')">Cancel</button>
        <button type="submit" class="btn bp">Create District Admin</button>
      </div>
    </form>
  </div>
</div>


{{-- ══ DELETE DISTRICT ADMIN MODAL (password confirm) ══ --}}
<div class="mo" id="deleteDaModal">
  <div class="md">
    <div class="mh">
      <div class="mt2">🗑️ Delete District Admin</div>
      <div class="mc3" onclick="resetDeleteDAModal(); cm('deleteDaModal')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.district-admins.delete') }}" id="deleteDAForm">
      @csrf
      @method('DELETE')
      <input type="hidden" name="id" id="deleteDAId">
      <div class="fgrid c1">
        <div class="fg">
          <label>District Admin</label>
          <input type="text" id="deleteDAName" disabled style="opacity:0.6">
        </div>
        <div style="padding:10px 12px;background:rgba(239,68,68,0.1);
                    border:1px solid rgba(239,68,68,0.3);border-radius:8px;
                    font-size:12px;color:#f87171;">
          <i class="fas fa-exclamation-triangle"></i>
          This permanently deletes the district admin and their login account.
          This cannot be undone.
        </div>
        <div class="fg">
          <label>Enter your login password to confirm</label>
          <input type="password" name="password" id="deleteDAPassword"
                 placeholder="Your password" autocomplete="current-password" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetDeleteDAModal(); cm('deleteDaModal')">Cancel</button>
        <button type="submit" class="btn bda">
          <i class="fas fa-trash"></i>Delete District Admin
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
// ── DELETE (password confirm) ──
function resetDeleteDAModal() {
  const form = document.getElementById('deleteDAForm');
  if (form) form.reset();
  document.getElementById('deleteDAId').value = '';
  document.getElementById('deleteDAName').value = '';
  document.getElementById('deleteDAPassword').value = '';
}

function openDeleteDA(id, name) {
  resetDeleteDAModal();
  document.getElementById('deleteDAId').value = id;
  document.getElementById('deleteDAName').value = name;
  om('deleteDaModal');
  setTimeout(() => document.getElementById('deleteDAPassword').focus(), 100);
}
</script>

{{-- Reopen the add modal only if the ADD form failed validation --}}
@if($errors->any() && old('form') === 'create')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    om('addDaModal');
  });
</script>
@endif
@endsection