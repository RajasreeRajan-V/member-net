@extends('layouts.district-admin')
@section('title', 'Change Password')
@section('page_title', 'Change Password')

@section('content')

<div style="max-width:480px;">

  <div class="card">
    <div class="ch">
      <div class="ct"><i class="fas fa-key"></i> Change Password</div>
    </div>

    {{-- TIPS --}}
    <div style="margin:14px 18px 0;padding:10px 14px;background:rgba(245,200,66,0.12);border:1px solid rgba(245,200,66,0.35);border-radius:8px;">
      <div style="font-size:11px;font-weight:600;color:var(--orange);margin-bottom:5px;">
        <i class="fas fa-shield-alt"></i> Password Requirements
      </div>
      <ul style="font-size:11px;color:var(--text-dim);padding-left:14px;line-height:1.9;">
        <li>At least 8 characters</li>
        <li>Must be different from current password</li>
        <li>Mix letters, numbers and symbols for strength</li>
      </ul>
    </div>

    <form method="POST" action="{{ route('district-admin.change-password.post') }}">
      @csrf
      <div class="fgrid c1">

        <div class="fg">
          <label>Current Password</label>
          <div style="position:relative;">
            <input type="password" name="current_password" id="cur_pwd"
                   placeholder="Enter current password" required>
            <button type="button" onclick="togglePwd('cur_pwd',this)"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:12px;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          @error('current_password')
            <span style="font-size:10.5px;color:var(--red);">{{ $message }}</span>
          @enderror
        </div>

        <div class="fg">
          <label>New Password</label>
          <div style="position:relative;">
            <input type="password" name="new_password" id="new_pwd"
                   placeholder="Enter new password" required minlength="8"
                   oninput="checkStrength(this.value); checkMatch();">
            <button type="button" onclick="togglePwd('new_pwd',this)"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:12px;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          {{-- Strength meter --}}
          <div style="margin-top:5px;">
            <div style="height:3px;background:var(--border);border-radius:3px;overflow:hidden;">
              <div id="strengthBar" style="height:100%;width:0;transition:width 0.3s,background 0.3s;border-radius:3px;"></div>
            </div>
            <div id="strengthLabel" style="font-size:10px;color:var(--text-dim);margin-top:3px;"></div>
          </div>
          @error('new_password')
            <span style="font-size:10.5px;color:var(--red);">{{ $message }}</span>
          @enderror
        </div>

        <div class="fg">
          <label>Confirm New Password</label>
          <div style="position:relative;">
            <input type="password" name="confirm_password" id="conf_pwd"
                   placeholder="Re-enter new password" required
                   oninput="checkMatch()">
            <button type="button" onclick="togglePwd('conf_pwd',this)"
                    style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:12px;">
              <i class="fas fa-eye"></i>
            </button>
          </div>
          <div id="matchMsg" style="font-size:10.5px;margin-top:3px;"></div>
          @error('confirm_password')
            <span style="font-size:10.5px;color:var(--red);">{{ $message }}</span>
          @enderror
        </div>

      </div>

      <div class="ff">
        <button type="reset" class="btn bo bsm" onclick="resetMeters()"><i class="fas fa-undo"></i> Reset</button>
        <button type="submit" class="btn bp bsm"><i class="fas fa-save"></i> Update Password</button>
      </div>
    </form>
  </div>

</div>

@endsection

@section('scripts')
<script>
function togglePwd(id, btn) {
  const inp  = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    inp.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

function checkStrength(val) {
  const bar   = document.getElementById('strengthBar');
  const label = document.getElementById('strengthLabel');
  let score = 0;
  if (val.length >= 8)          score++;
  if (/[A-Z]/.test(val))        score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const map = [
    null,
    { w:'25%',  c:'#f87171', t:'Weak' },
    { w:'50%',  c:'#fb923c', t:'Fair' },
    { w:'75%',  c:'#f5c842', t:'Good' },
    { w:'100%', c:'#34d399', t:'Strong' },
  ];
  const lvl = map[score] || { w:'0', c:'transparent', t:'' };
  bar.style.width      = lvl.w;
  bar.style.background = lvl.c;
  label.style.color    = lvl.c;
  label.textContent    = lvl.t;
}

function checkMatch() {
  const np  = document.getElementById('new_pwd').value;
  const cp  = document.getElementById('conf_pwd').value;
  const msg = document.getElementById('matchMsg');
  if (!cp) { msg.textContent = ''; return; }
  msg.innerHTML = (np === cp)
    ? '<span style="color:#34d399;">✓ Passwords match</span>'
    : '<span style="color:#f87171;">✗ Passwords do not match</span>';
}

function resetMeters() {
  document.getElementById('strengthBar').style.width = '0';
  document.getElementById('strengthLabel').textContent = '';
  document.getElementById('matchMsg').textContent = '';
}
</script>
@endsection