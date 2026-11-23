@extends('layouts.admin')
@section('title', 'Unit Admins')
@section('page_title', 'Unit Admins')

@section('content')

<div class="card">
  <div class="ch">
    <div class="ct">
      <i class="fas fa-user-shield"></i>All Unit Admins
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
          <th>Username (Unit ID)</th>
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
            <span style="font-family:monospace;font-weight:700;color:#a78bfa;letter-spacing:1px;background:rgba(167,139,250,0.1);padding:3px 8px;border-radius:6px;border:1px solid rgba(167,139,250,0.25);">
              {{ $ua->unit->unit_id ?? str_pad($ua->unit_id, 6, '0', STR_PAD_LEFT) }}
            </span>
          </td>
          <td>
            <div class="mc2">
              <div class="av2"
                style="background:rgba(167,139,250,0.2);
                       color:var(--purple)">
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
              {{-- EDIT (json_encode so names like D'Souza don't break the JS) --}}
              <div class="ab ed" title="Edit"
                   onclick="editUA(
                     {{ $ua->id }},
                     {{ json_encode($ua->user->name) }},
                     {{ json_encode($ua->user->email) }},
                     {{ json_encode($ua->user->phone) }},
                     {{ $ua->unit_id }})">
                <i class="fas fa-edit"></i>
              </div>

              {{-- RESET PASSWORD --}}
              <form method="POST"
                action="{{ route('admin.unit-admins.reset-password') }}"
                style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $ua->id }}">
                <button type="submit" class="ab"
                  title="Reset Password"
                  onclick="return confirm({{ json_encode('Reset password for ' . $ua->user->name . '?') }})">
                  <i class="fas fa-key"></i>
                </button>
              </form>

              {{-- ACTIVATE / DEACTIVATE --}}
              <form method="POST"
                action="{{ route('admin.unit-admins.toggle-status') }}"
                style="display:inline">
                @csrf
                <input type="hidden" name="id" value="{{ $ua->id }}">
                <button type="submit"
                  class="ab {{ $ua->user->is_active ? 'rej' : 'app' }}"
                  title="{{ $ua->user->is_active ? 'Deactivate' : 'Activate' }}"
                  onclick="return confirm({{ json_encode(($ua->user->is_active ? 'Deactivate ' : 'Activate ') . $ua->user->name . '?') }})">
                  <i class="fas fa-{{ $ua->user->is_active ? 'ban' : 'check' }}"></i>
                </button>
              </form>

              {{-- DELETE (opens password-confirm modal) --}}
              <div class="ab rej" title="Delete"
                   onclick="openDeleteUA({{ $ua->id }}, {{ json_encode($ua->user->name) }})">
                <i class="fas fa-trash"></i>
              </div>
            </div>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="9"
            style="text-align:center;color:var(--text-dim);padding:30px;">
            No unit admins yet
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
    <form method="POST"
          action="{{ route('admin.unit-admins.store') }}">
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
                  background:rgba(79,142,247,0.07);
                  border-radius:8px;font-size:11px;
                  color:var(--text-dim);">
        <i class="fas fa-info-circle"
           style="color:var(--accent)"></i>
        Login credentials: Username will be the Unit ID (e.g. <b>{{ $units->first()?->unit_id ?? 'KAN001' }}</b>), and Password will be the <b>Phone Number</b>.
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
    <form method="POST" action="{{ route('admin.unit-admins.update') }}">
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
            @foreach(\App\Models\Unit::orderBy('name')->get() as $unit)
              <option value="{{ $unit->id }}"
                @selected(old('form') === 'edit' && old('unit_id') == $unit->id)>
                {{ $unit->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div style="margin:0 18px 14px;padding:10px 12px;
                  background:rgba(79,142,247,0.07);border-radius:8px;
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


{{-- ══ DELETE UNIT ADMIN MODAL (password confirm) ══ --}}
<div id="deleteUAM" class="mo">
  <div class="md">
    <div class="mh">
      <span class="mt2">🗑️ Delete Unit Admin</span>
      <div class="mc3" onclick="resetDeleteUAModal(); cm('deleteUAM')">
        <i class="fas fa-times"></i>
      </div>
    </div>
    <form method="POST" action="{{ route('admin.unit-admins.delete') }}" id="deleteUAForm">
      @csrf
      @method('DELETE')
      <input type="hidden" name="id" id="deleteUAId">
      <div class="fgrid c1">
        <div class="fg">
          <label>Unit Admin</label>
          <input type="text" id="deleteUAName" disabled style="opacity:0.6">
        </div>
        <div style="padding:10px 12px;background:rgba(239,68,68,0.1);
                    border:1px solid rgba(239,68,68,0.3);border-radius:8px;
                    font-size:12px;color:#f87171;">
          <i class="fas fa-exclamation-triangle"></i>
          This permanently deletes the unit admin and their login account.
          This cannot be undone.
        </div>
        <div class="fg">
          <label>Enter your login password to confirm</label>
          <input type="password" name="password" id="deleteUAPassword"
                 placeholder="Your password" autocomplete="current-password" required>
        </div>
      </div>
      <div class="ff">
        <button type="button" class="btn bo"
                onclick="resetDeleteUAModal(); cm('deleteUAM')">Cancel</button>
        <button type="submit" class="btn bda">
          <i class="fas fa-trash"></i>Delete Unit Admin
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
// ── EDIT ──
function editUA(id, name, email, phone, unitId) {
  document.getElementById('editUAId').value    = id;
  document.getElementById('editUAName').value  = name;
  document.getElementById('editUAEmail').value = email;
  document.getElementById('editUAPhone').value = phone;
  document.getElementById('editUAUnit').value  = unitId;

  document.getElementById('editUAOriginalEmail').value = email;
  document.getElementById('editUAResend').checked  = false;
  document.getElementById('editUAResend').disabled = false;
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
    checkbox.disabled = true; // forced: email changed, so a resend is mandatory
    label.textContent = 'Resend welcome email (required: email changed)';
  } else {
    checkbox.disabled = false;
    label.textContent = 'Resend welcome email';
  }
}

// ── DELETE (password confirm) ──
function resetDeleteUAModal() {
  const form = document.getElementById('deleteUAForm');
  if (form) form.reset();
  document.getElementById('deleteUAId').value = '';
  document.getElementById('deleteUAName').value = '';
  document.getElementById('deleteUAPassword').value = '';
}

function openDeleteUA(id, name) {
  resetDeleteUAModal();
  document.getElementById('deleteUAId').value = id;
  document.getElementById('deleteUAName').value = name;
  om('deleteUAM');
  setTimeout(() => document.getElementById('deleteUAPassword').focus(), 100);
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