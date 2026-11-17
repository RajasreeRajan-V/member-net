@extends('layouts.admin')
@section('title', 'Change Password')
@section('page_title', 'Change Password')

@section('content')

<div style="max-width:480px;margin:0 auto;">
  <div class="card">
    <div class="ch">
      <div class="ct">
        <i class="fas fa-key"></i>Change Password
      </div>
    </div>

    <form method="POST"
          action="{{ route('admin.change-password.post') }}">
      @csrf
      <div class="fgrid c1">

        {{-- CURRENT PASSWORD --}}
        <div class="fg">
          <label>Current Password</label>
          <div style="position:relative;">
            <input type="password"
                   name="current_password"
                   id="currentPwd"
                   placeholder="Enter current password"
                   required>
            <span onclick="togglePwd('currentPwd', this)"
                  style="position:absolute;right:10px;
                         top:50%;transform:translateY(-50%);
                         cursor:pointer;color:var(--text-dim);
                         font-size:12px;">
              <i class="fas fa-eye"></i>
            </span>
          </div>
        </div>

        {{-- NEW PASSWORD --}}
        <div class="fg">
          <label>New Password</label>
          <div style="position:relative;">
            <input type="password"
                   name="new_password"
                   id="newPwd"
                   placeholder="Min 8 characters"
                   required>
            <span onclick="togglePwd('newPwd', this)"
                  style="position:absolute;right:10px;
                         top:50%;transform:translateY(-50%);
                         cursor:pointer;color:var(--text-dim);
                         font-size:12px;">
              <i class="fas fa-eye"></i>
            </span>
          </div>
        </div>

        {{-- CONFIRM PASSWORD --}}
        <div class="fg">
          <label>Confirm New Password</label>
          <div style="position:relative;">
            <input type="password"
                   name="confirm_password"
                   id="confirmPwd"
                   placeholder="Repeat new password"
                   required>
            <span onclick="togglePwd('confirmPwd', this)"
                  style="position:absolute;right:10px;
                         top:50%;transform:translateY(-50%);
                         cursor:pointer;color:var(--text-dim);
                         font-size:12px;">
              <i class="fas fa-eye"></i>
            </span>
          </div>
        </div>

        {{-- PASSWORD RULES --}}
        <div style="padding:10px 12px;
                    background:rgba(79,142,247,0.07);
                    border-radius:8px;font-size:11px;
                    color:var(--text-dim);line-height:1.8;">
          <i class="fas fa-info-circle"
             style="color:var(--accent)"></i>
          Password requirements:
          <br>→ Minimum 8 characters
          <br>→ Must be different from current
          <br>→ New and confirm must match
        </div>

      </div>
      <div class="ff">
        <a href="{{ route('admin.dashboard') }}"
           class="btn bo">
          Cancel
        </a>
        <button type="submit" class="btn bp">
          <i class="fas fa-key"></i>Change Password
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@section('scripts')
<script>
function togglePwd(inputId, icon) {
  const input = document.getElementById(inputId);
  const i     = icon.querySelector('i');
  if (input.type === 'password') {
    input.type  = 'text';
    i.className = 'fas fa-eye-slash';
  } else {
    input.type  = 'password';
    i.className = 'fas fa-eye';
  }
}
</script>
@endsection

