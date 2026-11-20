<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ECA — Reset Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{font-family:'DM Sans',sans-serif;background:#0f1729;color:#e8edf8;
         min-height:100vh;display:flex;align-items:center;justify-content:center;padding:16px;}
    .box{width:100%;max-width:420px;background:#162040;
         border:1px solid rgba(255,255,255,0.07);border-radius:16px;padding:36px;}
    .logo{text-align:center;margin-bottom:28px;}
    .logo-icon{width:52px;height:52px;background:linear-gradient(135deg,#4f8ef7,#7c3aed);
               border-radius:14px;display:flex;align-items:center;justify-content:center;
               font-size:22px;margin:0 auto 10px;}
    .logo-text{font-family:'Instrument Serif',serif;font-size:24px;}
    .logo-sub{font-size:11px;color:#8896b3;margin-top:3px;}
    .title{font-size:16px;font-weight:600;margin-bottom:4px;}
    .subtitle{font-size:12px;color:#8896b3;margin-bottom:24px;}
    .fg{display:flex;flex-direction:column;gap:5px;margin-bottom:16px;position:relative;}
    label{font-size:10.5px;font-weight:600;color:#8896b3;text-transform:uppercase;letter-spacing:0.5px;}
    input{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.07);
          border-radius:8px;padding:10px 12px;color:#e8edf8;font-size:13px;
          font-family:'DM Sans',sans-serif;outline:none;width:100%;transition:border 0.2s;}
    input:focus{border-color:#4f8ef7;}
    input:disabled{opacity:0.5;cursor:not-allowed;}
    .pw-wrap{position:relative;}
    .pw-wrap input{padding-right:36px;}
    .pw-toggle{position:absolute;right:11px;top:50%;transform:translateY(-50%);
               cursor:pointer;color:#8896b3;font-size:13px;border:none;
               background:transparent;padding:0;}
    .pw-toggle:hover{color:#e8edf8;}
    .btn{width:100%;padding:11px;background:#4f8ef7;color:#fff;border:none;
         border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;
         font-family:'DM Sans',sans-serif;transition:all 0.2s;margin-top:4px;}
    .btn:hover{background:#7eb8ff;color:#0f1729;}
    .btn:disabled{opacity:0.5;cursor:not-allowed;}
    .alert{padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:16px;
           display:flex;align-items:flex-start;gap:8px;}
    .alert-success{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);color:#34d399;}
    .alert-error{background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);color:#f87171;}
    .strength{height:3px;border-radius:2px;margin-top:6px;transition:all 0.3s;background:#1e2d50;}
    .hint{font-size:10px;color:#8896b3;margin-top:4px;}
    .email-display{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);
                   border-radius:8px;padding:10px 12px;font-size:12px;color:#8896b3;margin-bottom:16px;}
    .success-state{text-align:center;padding:20px 0;}
    .success-icon{font-size:40px;margin-bottom:12px;}
    .success-title{font-size:16px;font-weight:600;margin-bottom:6px;color:#34d399;}
    .success-msg{font-size:12px;color:#8896b3;margin-bottom:20px;line-height:1.6;}
  </style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="logo-icon">🎓</div>
    <div class="logo-text">ECA</div>
    <div class="logo-sub">Member Net Association</div>
  </div>

  <div id="formSection">
    <div class="title">Reset Your Password</div>
    <div class="subtitle">Enter a new password for your account</div>

    <div id="alertBox" style="display:none;"></div>

    <div class="email-display">
      <i class="fas fa-envelope" style="color:#4f8ef7;margin-right:6px;"></i>
      {{ $email }}
    </div>

    <div class="fg">
      <label>New Password</label>
      <div class="pw-wrap">
        <input type="password" id="password" placeholder="Min 8 characters"
               oninput="checkStrength(this.value)">
        <button type="button" class="pw-toggle" onclick="toggle('password', this)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
      <div class="strength" id="strengthBar"></div>
      <div class="hint" id="strengthText"></div>
    </div>

    <div class="fg">
      <label>Confirm New Password</label>
      <div class="pw-wrap">
        <input type="password" id="confirmPassword" placeholder="Repeat password">
        <button type="button" class="pw-toggle" onclick="toggle('confirmPassword', this)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>

    <button class="btn" id="submitBtn" onclick="submitReset()">
      <i class="fas fa-lock"></i> Reset Password
    </button>
  </div>

  <div id="successSection" style="display:none;">
    <div class="success-state">
      <div class="success-icon">✅</div>
      <div class="success-title">Password Reset Successfully!</div>
      <div class="success-msg">
        Your password has been updated.<br>
        You can now log in to the ECA app with your new password.
      </div>
      <div style="font-size:11px;color:#8896b3;">
        You can close this page and return to the app.
      </div>
    </div>
  </div>

</div>

<script>
  const TOKEN = '{{ $token }}';
  const EMAIL = '{{ $email }}';

  function toggle(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
      input.type     = 'text';
      icon.className = 'fas fa-eye-slash';
    } else {
      input.type     = 'password';
      icon.className = 'fas fa-eye';
    }
  }

  function checkStrength(val) {
    const bar  = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');
    let score  = 0;
    if (val.length >= 8)           score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
      { color:'#ef4444', label:'Weak',   width:'25%'  },
      { color:'#f97316', label:'Fair',   width:'50%'  },
      { color:'#eab308', label:'Good',   width:'75%'  },
      { color:'#22c55e', label:'Strong', width:'100%' },
    ];

    if (!val.length) { bar.style.width='0'; text.textContent=''; return; }
    const level = levels[Math.min(score-1, 3)] ?? levels[0];
    bar.style.background = level.color;
    bar.style.width      = level.width;
    text.textContent     = level.label;
    text.style.color     = level.color;
  }

  function showAlert(message, type) {
    const box = document.getElementById('alertBox');
    box.style.display = 'flex';
    box.className = 'alert alert-' + type;
    box.innerHTML = `<i class="fas fa-${type==='success'?'check':'times'}-circle" style="margin-top:1px;flex-shrink:0;"></i><span>${message}</span>`;
  }

  function hideAlert() {
    document.getElementById('alertBox').style.display = 'none';
  }

  async function submitReset() {
    hideAlert();

    const password        = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const btn             = document.getElementById('submitBtn');

    if (!password) {
      showAlert('Please enter a new password.', 'error'); return;
    }
    if (password.length < 8) {
      showAlert('Password must be at least 8 characters.', 'error'); return;
    }
    if (password !== confirmPassword) {
      showAlert('Passwords do not match.', 'error'); return;
    }

    btn.disabled     = true;
    btn.innerHTML    = '<i class="fas fa-spinner fa-spin"></i> Resetting...';

    try {
      const res = await fetch('https://kmubhashira.astradevelops.in/eca-api/public/api/reset-password', {

        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          email:                 EMAIL,
          token:                 TOKEN,
          password:              password,
          password_confirmation: confirmPassword,
        }),
      });

      const data = await res.json();

      if (res.ok) {
        // Show success screen
        document.getElementById('formSection').style.display   = 'none';
        document.getElementById('successSection').style.display = 'block';
      } else {
        showAlert(data.message || 'Something went wrong. Please try again.', 'error');
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-lock"></i> Reset Password';
      }
    } catch (err) {
      showAlert('Network error. Please check your connection.', 'error');
      btn.disabled  = false;
      btn.innerHTML = '<i class="fas fa-lock"></i> Reset Password';
    }
  }
</script>
</body>
</html>