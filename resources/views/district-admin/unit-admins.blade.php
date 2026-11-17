@extends('layouts.district-admin')
@section('title', 'Unit Admins')
@section('page_title', 'Unit Admins')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-user-shield"></i>Unit Admins in {{ auth()->user()->districtAdmin->district->name ?? 'Your District' }}
    </div>
    <button class="btn bp bsm" onclick="om('addUAM')">
      <i class="fas fa-plus"></i>Create Unit Admin
    </button>
  </div>
  <div class="tw">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Phone</th>
          <th>Unit</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($unitAdmins as $ua)
        <tr>
          <td style="color:var(--text-dim)">{{ $loop->iteration }}</td>
          <td>
            <div class="mc2">
              <div class="av2"
                style="background:rgba(245,200,66,0.2);
                       color:var(--gold)">
                {{ strtoupper(substr($ua->user->name,0,2)) }}
              </div>
              <div class="mn">{{ $ua->user->name }}</div>
            </div>
          </td>
          <td style="color:var(--text-dim)">{{ $ua->user->email }}</td>
          <td style="color:var(--text-dim)">{{ $ua->user->phone }}</td>
          <td><span class="utag">{{ $ua->unit->name }}</span></td>
          <td>
            <span class="badge {{ $ua->user->is_active ? 'bac' : 'brj' }}">
              {{ $ua->user->is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td>
            <div class="abr">
              <div class="ab ed"
                onclick="editUA(
                  {{ $ua->id }},
                  '{{ addslashes($ua->user->name) }}',
                  '{{ addslashes($ua->user->email) }}',
                  '{{ $ua->user->phone }}',
                  {{ $ua->unit_id }})">
                <i class="fas fa-edit"></i>
              </div>
              <form method="POST"
                action="{{ route('district-admin.unit-admins.reset-password') }}"
                style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $ua->id }}">
                <button type="submit" class="ab"
                  title="Reset Password"
                  onclick="return confirm('Reset password for {{ addslashes($ua->user->name) }}?')">
                  <i class="fas fa-key"></i>
                </button>
              </form>
              <form method="POST"
                action="{{ route('district-admin.unit-admins.toggle-status') }}"
                style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $ua->id }}">
                <button type="submit"
                  class="ab {{ $ua->user->is_active ? 'rej' : 'app' }}"
                  title="{{ $ua->user->is_active ? 'Deactivate' : 'Activate' }}"
                  onclick="return confirm('{{ $ua->user->is_active ? 'Deactivate' : 'Activate' }} {{ addslashes($ua->user->name) }}?')">
                  <i class="fas fa-{{ $ua->user->is_active ? 'ban' : 'check' }}"></i>
                </button>
              </form>
              <form method="POST"
                action="{{ route('district-admin.unit-admins.delete') }}"
                style="display:inline"
                onsubmit="return confirm('Delete {{ addslashes($ua->user->name) }}?')">
                @csrf @method('DELETE')
                <input type="hidden" name="id" value="{{ $ua->id }}">
                <button type="submit" class="ab rej">
                  <i class="fas fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="7">
            <div class="empty-state">
              <i class="fas fa-user-shield"></i>
              <p>No unit admins yet. Create one for a unit in your district.</p>
            </div>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- ══ CREATE UNIT ADMIN MODAL ══ --}}
<div id="addUAM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Create Unit Admin</span>
      <div class="mc3" onclick="cm('addUAM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('district-admin.unit-admins.store') }}">
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
        <div class="fg">
          <label>Full Name</label>
          <input type="text" name="name" maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'create' ? old('name') : '' }}"
                 placeholder="Enter full name" required>
        </div>
        <div class="fg">
          <label>Email</label>
          <input type="email" name="email" maxlength="150"
                 value="{{ old('form') === 'create' ? old('email') : '' }}"
                 placeholder="Enter email"
                 pattern="[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}"
                 title="Enter a valid email with a domain, e.g. name@example.com"
                 required>
        </div>
        <div class="fg">
          <label>Phone</label>
          <input type="text" name="phone" maxlength="10"
                 value="{{ old('form') === 'create' ? old('phone') : '' }}"
                 placeholder="9845002001"
                 pattern="[6-9]{1}[0-9]{9}"
                 title="Enter valid 10-digit Indian mobile number"
                 required>
        </div>
        <div class="fg">
          <label>Assign Unit</label>
          <select name="unit_id" required>
            <option value="">Select Unit</option>
            @foreach($units as $unit)
              <option value="{{ $unit->id }}"
                @selected(old('form') === 'create' && old('unit_id') == $unit->id)>
                {{ $unit->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>
      <div style="margin:0 18px 14px;padding:10px 12px;
                  background:rgba(245,200,66,0.07);
                  border-radius:8px;font-size:11px;
                  color:var(--text-dim);">
        <i class="fas fa-info-circle"
           style="color:var(--gold)"></i>
        Password will be auto-generated and shown
        in success message. Share it immediately!
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="cm('addUAM')">Cancel</button>
        <button type="submit" class="btn bp">
          <i class="fas fa-user-plus"></i>Create
        </button>
      </div>
    </form>
  </div>
</div>

{{-- ══ EDIT UNIT ADMIN MODAL ══ --}}
<div id="editUAM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">Edit Unit Admin</span>
      <div class="mc3" onclick="cm('editUAM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('district-admin.unit-admins.update') }}">
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
      <input type="hidden" name="id" id="editUAId"
             value="{{ old('form') === 'edit' ? old('id') : '' }}">
      <input type="hidden" id="editUAOriginalEmail" value="">

      <div class="fgrid">
        <div class="fg">
          <label>Full Name</label>
          <input type="text" name="name" id="editUAName" maxlength="100"
                 pattern="[A-Za-z\s'\-]+"
                 title="Letters, spaces, hyphens and apostrophes only"
                 value="{{ old('form') === 'edit' ? old('name') : '' }}"
                 required>
        </div>
        <div class="fg">
          <label>Email</label>
          <input type="email" name="email" id="editUAEmail" maxlength="150"
                 pattern="[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}"
                 title="Enter a valid email with a domain, e.g. name@example.com"
                 value="{{ old('form') === 'edit' ? old('email') : '' }}"
                 oninput="onEditUAEmailChange()"
                 required>
        </div>
        <div class="fg">
          <label>Phone</label>
          <input type="text" name="phone" id="editUAPhone" maxlength="10"
                 value="{{ old('form') === 'edit' ? old('phone') : '' }}"
                 pattern="[6-9]{1}[0-9]{9}" required>
        </div>
        <div class="fg s2">
          <label>Assign Unit</label>
          <select name="unit_id" id="editUAUnit" required>
            <option value="">Select Unit</option>
            @foreach($units as $unit)
              <option value="{{ $unit->id }}"
                @selected(old('form') === 'edit' && old('unit_id') == $unit->id)>
                {{ $unit->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div style="margin:0 18px 14px;padding:10px 12px;
                  background:rgba(245,200,66,0.07);border-radius:8px;
                  font-size:12px;color:var(--text-dim);">
        <label style="display:flex;align-items:flex-start;gap:8px;cursor:pointer;margin:0;">
          <input type="checkbox" name="resend_email" id="editUAResend" value="1"
                 style="margin-top:2px;">
          <span>
            <b id="editUAResendLabel" style="color:var(--text);">Resend welcome email</b><br>
            A new password will be generated and emailed. This happens automatically if you
            change the email address, since the old login mail can no longer reach them.
          </span>
        </label>
      </div>

      <div class="ff">
        <button type="button" class="btn bo" onclick="cm('editUAM')">Cancel</button>
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
function editUA(id, name, email, phone, unitId) {
  document.getElementById('editUAId').value    = id;
  document.getElementById('editUAName').value  = name;
  document.getElementById('editUAEmail').value = email;
  document.getElementById('editUAPhone').value = phone;
  document.getElementById('editUAUnit').value  = unitId;

  document.getElementById('editUAOriginalEmail').value = email;
  document.getElementById('editUAResend').checked = false;
  document.getElementById('editUAResendLabel').textContent = 'Resend welcome email';

  om('editUAM');
}

function onEditUAEmailChange() {
  const original = document.getElementById('editUAOriginalEmail').value.trim().toLowerCase();
  const current  = document.getElementById('editUAEmail').value.trim().toLowerCase();
  const checkbox = document.getElementById('editUAResend');
  const label    = document.getElementById('editUAResendLabel');

  if (current !== original && current.length > 0) {
    checkbox.checked  = true;
    checkbox.disabled = true;
    label.textContent = 'Resend welcome email (required — email changed)';
  } else {
    checkbox.disabled = false;
    label.textContent = 'Resend welcome email';
  }
}

@if ($errors->any())
  document.addEventListener('DOMContentLoaded', function () {
    @if (old('form') === 'edit')
      om('editUAM');
    @else
      om('addUAM');
    @endif
  });
@endif
</script>
@endsection