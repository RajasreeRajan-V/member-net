<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Net District Admin - Login</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --navy: #ffffff;
      --navy-2: #f0f4ff;
      --accent: #4f8ef7;
      --accent-2: #3a7ae0;
      --red: #f87171;
      --text: #0f1729;
      --text-dim: #4a5a7a;
      --border: rgba(15, 23, 41, 0.1);
      --card: rgba(15, 23, 41, 0.04);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--navy-2);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .wrap {
      width: 100%;
      max-width: 380px;
    }

    .brand {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      margin-bottom: 26px;
    }

    .brand-icon {
      width: 52px;
      height: 52px;
      background: linear-gradient(135deg, var(--accent), #7c3aed);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      margin-bottom: 4px;
    }

    .brand-title {
      font-family: 'Instrument Serif', serif;
      font-size: 24px;
    }

    .brand-sub {
      font-size: 12px;
      color: var(--text-dim);
    }

    .card {
      background: #ffffff;
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 28px 26px;
      box-shadow: 0 10px 40px rgba(15, 23, 41, 0.06);
    }

    .card h2 {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .card p.sub {
      font-size: 12px;
      color: var(--text-dim);
      margin-bottom: 22px;
    }

    .fg {
      margin-bottom: 16px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    label {
      font-size: 10.5px;
      font-weight: 700;
      color: var(--text-dim);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .input-wrap {
      position: relative;
    }

    .input-wrap i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 12px;
      color: var(--text-dim);
    }

    input {
      width: 100%;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 11px 12px 11px 34px;
      font-size: 13px;
      font-family: 'DM Sans', sans-serif;
      color: var(--text);
      outline: none;
      transition: border 0.2s;
    }

    input:focus {
      border-color: var(--accent);
    }

    /* Hide Edge's built-in password reveal / clear buttons */
    input::-ms-reveal,
    input::-ms-clear { display: none; }

    /* Space for the eye icon on the right */
    #password { padding-right: 40px; }

    /* Eye icon sits on the right (the base .input-wrap i rule puts icons on the left) */
    .input-wrap i.toggle-eye {
      left: auto;
      right: 12px;
      cursor: pointer;
      font-size: 13px;
    }

    .row-between {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .forgot {
      font-size: 11.5px;
      color: var(--accent-2);
      text-decoration: none;
      font-weight: 600;
    }

    .forgot:hover {
      text-decoration: underline;
    }

    .btn-login {
      width: 100%;
      background: var(--accent);
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s;
    }

    .btn-login:hover {
      background: var(--accent-2);
    }

    .alert-error {
      background: rgba(248, 113, 113, 0.1);
      border: 1px solid rgba(248, 113, 113, 0.3);
      color: var(--red);
      padding: 10px 12px;
      border-radius: 8px;
      font-size: 12px;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .foot-link {
      text-align: center;
      margin-top: 18px;
      font-size: 11.5px;
      color: var(--text-dim);
    }

    .foot-link a {
      color: var(--accent-2);
      text-decoration: none;
      font-weight: 600;
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="brand">
      <div class="brand-icon">🎓</div>
      <div class="brand-title">Member Net</div>
      <div class="brand-sub">District Admin Panel</div>
    </div>

    <div class="card">
      <h2>Welcome back</h2>
      <p class="sub">Sign in to manage your district</p>

      @if ($errors->any())
        <div class="alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <span>{{ $errors->first() }}</span>
        </div>
      @endif

      <form method="POST" action="{{ route('district-admin.login.post') }}">
        @csrf

        <div class="fg">
          <label>Email</label>
          <div class="input-wrap">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" value="{{ old('email') }}"
                   placeholder="you@example.com" required autofocus>
          </div>
        </div>

        <div class="fg">
          <label>Password</label>
          <div class="input-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <i class="fas fa-eye toggle-eye" onclick="togglePassword(this)"></i>
          </div>
        </div>

        <div class="row-between" style="margin-top:-6px;">
          <span></span>
          <a class="forgot" href="{{ route('district-admin.password.forgot') }}">Forgot password?</a>
        </div>

        <button type="submit" class="btn-login">
          <i class="fas fa-sign-in-alt"></i> Log In
        </button>
      </form>
    </div>

    <div class="foot-link">
      Not a district admin? <a href="{{ route('admin.login') }}">Super Admin login</a>
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