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
    .pw-wrap{position:relative;}
    .pw-wrap input{padding-right:36px;}
    .pw-toggle{position:absolute;right:11px;top:50%;transform:translateY(-50%);
               cursor:pointer;color:#8896b3;font-size:13px;border:none;
               background:transparent;padding:0;}
    .pw-toggle:hover{color:#e8edf8;}
    .btn{width:100%;padding:11px;background:#4f8ef7;color:#fff;border:none;
         border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;
         font-family:'DM Sans',sans-serif;transition:all 0.2s;margin-top:8px;}
    .btn:hover{background:#7eb8ff;color:#0f1729;}
    .error{background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.3);
           color:#f87171;padding:10px 14px;border-radius:8px;font-size:12px;
           margin-bottom:16px;display:flex;align-items:center;gap:8px;}
    .hint{font-size:10px;color:#8896b3;margin-top:4px;}
    .strength{height:3px;border-radius:2px;margin-top:6px;transition:all 0.3s;background:#1e2d50;}
    .back-link{text-align:center;margin-top:20px;font-size:11px;color:#8896b3;}
    .back-link a{color:#4f8ef7;text-decoration:none;}
    .back-link a:hover{text-decoration:underline;}
    @media(max-width:480px){
      .box{padding:24px 20px;border-radius:12px;}
      .logo-icon{width:44px;height:44px;font-size:18px;}
      .logo-text{font-size:20px;}
    }
  </style>
</head>
<body>
<div class="box">
  <div class="logo">
    <div class="logo-icon">🎓</div>
    <div class="logo-text">ECA</div>
    <div class="logo-sub">Education Consultancy Association</div>
  </div>

  <div class="title">Reset Password</div>
  <div class="subtitle">Enter your new password below</div>

  @if($errors->any())
    <div class="error">
      <i class="fas fa-times-circle"></i>
      {{ $errors->first() }}
    </div>
  @endif

  @php
    $postRoute = str_contains(request()->path(), 'unit-admin')
      ? 'unit-admin.password.reset.post'
      : 'admin.password.reset.post';
    $loginRoute = str_contains(request()->path(), 'unit-admin')
      ? 'unit-admin.login'
      : 'admin.login';
  @endphp

  <form method="POST" action="{{ route($postRoute) }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">

    <div class="fg">
      <label>Email Address</label>
      <input type="email" value="{{ $email }}" disabled
             style="opacity:0.5;cursor:not-allowed;">
    </div>

    <div class="fg">
      <label>New Password</label>
      <div class="pw-wrap">
        <input type="password" name="password" id="pw1"
               placeholder="Min 8 characters" required
               oninput="checkStrength(this.value)">
        <button type="button" class="pw-toggle" onclick="toggle('pw1',this)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
      <div class="strength" id="strengthBar"></div>
      <div class="hint" id="strengthText"></div>
    </div>

    <div class="fg">
      <label>Confirm New Password</label>
      <div class="pw-wrap">
        <input type="password" name="password_confirmation" id="pw2"
               placeholder="Repeat password" required>
        <button type="button" class="pw-toggle" onclick="toggle('pw2',this)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn">
      <i class="fas fa-lock"></i> Reset Password
    </button>
  </form>

  <div class="back-link">
    <a href="{{ route($loginRoute) }}">
      <i class="fas fa-arrow-left"></i> Back to Login
    </a>
  </div>
</div>

<script>
function toggle(id, btn) {
  const input = document.getElementById(id);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type    = 'text';
    icon.className = 'fas fa-eye-slash';
  } else {
    input.type    = 'password';
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
    { color: '#ef4444', label: 'Weak',      width: '25%'  },
    { color: '#f97316', label: 'Fair',      width: '50%'  },
    { color: '#eab308', label: 'Good',      width: '75%'  },
    { color: '#22c55e', label: 'Strong',    width: '100%' },
  ];

  if (val.length === 0) {
    bar.style.width  = '0';
    text.textContent = '';
    return;
  }

  const level = levels[Math.min(score - 1, 3)] ?? levels[0];
  bar.style.background = level.color;
  bar.style.width      = level.width;
  text.textContent     = level.label;
  text.style.color     = level.color;
}
</script>
</body>
</html>