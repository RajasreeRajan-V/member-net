<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Net Unit Admin Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: #0f1729;
      color: #e8edf8;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .box {
      width: 100%;
      max-width: 420px;
      background: #162040;
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 16px;
      padding: 36px;
    }
    .logo { text-align: center; margin-bottom: 28px; }
    .logo-icon {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, #4f8ef7, #7c3aed);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin: 0 auto 10px;
    }
    .logo-text { font-family: 'Instrument Serif', serif; font-size: 24px; }
    .logo-sub { font-size: 11px; color: #8896b3; margin-top: 3px; }

    /* Role chip — purple to differentiate from super admin (blue) */
    .role-chip {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(124,58,237,0.12);
      border: 1px solid rgba(124,58,237,0.25);
      color: #a78bfa;
      border-radius: 20px; padding: 3px 12px;
      font-size: 10.5px; font-weight: 600;
      margin-bottom: 20px;
    }

    .title { font-size: 16px; font-weight: 600; margin-bottom: 4px; }
    .subtitle { font-size: 12px; color: #8896b3; margin-bottom: 24px; }

    .fg { display: flex; flex-direction: column; gap: 5px; margin-bottom: 16px; }
    label { font-size: 10.5px; font-weight: 600; color: #8896b3; text-transform: uppercase; letter-spacing: 0.5px; }
    input {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 8px; padding: 10px 12px;
      color: #e8edf8; font-size: 13px;
      font-family: 'DM Sans', sans-serif;
      outline: none; width: 100%; transition: border 0.2s;
    }
        input:focus { border-color: #4f8ef7; }

    /* Hide Edge's built-in password reveal / clear buttons */
    input::-ms-reveal,
    input::-ms-clear { display: none; }

    /* Keep long passwords from running under the eye icon */
    #password { padding-right: 40px; }

    .btn {
      width: 100%; padding: 11px;
      background: #4f8ef7; color: #fff; border: none;
      border-radius: 8px; font-size: 13px; font-weight: 600;
      cursor: pointer; font-family: 'DM Sans', sans-serif;
      transition: all 0.2s; margin-top: 8px;
    }
    .btn:hover { background: #7eb8ff; color: #0f1729; }

    .error {
      background: rgba(248,113,113,0.1);
      border: 1px solid rgba(248,113,113,0.3);
      color: #f87171;
      padding: 10px 14px; border-radius: 8px;
      font-size: 12px; margin-bottom: 16px;
      display: flex; align-items: center; gap: 8px;
    }

    .back-link { text-align: center; margin-top: 18px; font-size: 11px; color: #8896b3; }
    .back-link a { color: #4f8ef7; text-decoration: none; }
    .back-link a:hover { text-decoration: underline; }

    @media(max-width:480px) {
      body { padding: 16px; }
      .box { padding: 24px 20px; border-radius: 12px; }
      .logo-icon { width: 44px; height: 44px; font-size: 18px; }
      .logo-text { font-size: 20px; }
      .title { font-size: 14px; }
      .subtitle { font-size: 11px; }
      input { padding: 9px 11px; font-size: 12px; }
      .btn { padding: 10px; font-size: 12px; }
    }
  </style>
</head>
<body>
  <div class="box">
    <div class="logo">
      <div class="logo-icon">🎓</div>
      <div class="logo-text">Member Net</div>
      <div class="logo-sub">Member Net Association</div>
    </div>

    

    <div class="title">Unit Admin Login</div>
    <div class="subtitle">Sign in to manage your unit</div>

    @if($errors->any())
      <div class="error">
        <i class="fas fa-times-circle"></i>
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('unit-admin.login.post') }}">
      @csrf
      <div class="fg">
        <label>Username / Unit ID</label>
        <input type="text" name="email" value="{{ old('email') }}" placeholder="Enter Unit ID (e.g. KAN001)" required autofocus>
      </div>
      <div class="fg">
  <label>Password (Phone Number)</label>
  <div style="position:relative;">
    <input type="password" id="password" name="password" placeholder="Enter 10-digit phone number" required>

    <i class="fas fa-eye"
       onclick="togglePassword(this)"
       style="position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer;">
    </i>
  </div>
</div>

      <div style="text-align:right;margin-bottom:12px;">
  <a href="{{ route('unit-admin.password.forgot') }}"
     style="font-size:11px;color:#8896b3;text-decoration:none;">
    <i class="fas fa-key"></i> Forgot password?
  </a>
</div>
      <button type="submit" class="btn">
        <i class="fas fa-sign-in-alt"></i>
        Sign In
      </button>
    </form>

    <div class="back-link">
      Super Admin? <a href="{{ route('admin.login') }}">Login here</a>
    </div>
  </div>
<script>
function togglePassword(icon) {
  const input = document.getElementById('password');
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  icon.classList.toggle('fa-eye', !show);
  icon.classList.toggle('fa-eye-slash', show);
}
</script>

</body>
</html>
