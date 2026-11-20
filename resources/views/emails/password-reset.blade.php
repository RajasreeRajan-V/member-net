<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: 'DM Sans', Arial, sans-serif; background: #f4f6fb; margin: 0; padding: 30px 0; }
    .wrap { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #4f8ef7, #7c3aed); padding: 28px 32px; text-align: center; }
    .header h1 { color: #fff; font-size: 22px; margin: 0; }
    .header p  { color: rgba(255,255,255,0.75); font-size: 12px; margin: 4px 0 0; }
    .body { padding: 32px; color: #374151; }
    .body p { font-size: 14px; line-height: 1.7; margin: 0 0 16px; }
    .btn { display: block; width: fit-content; margin: 20px auto; padding: 13px 32px; background: #4f8ef7; color: #fff !important; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; }
    .footer { text-align: center; padding: 16px 32px 24px; font-size: 11px; color: #9ca3af; }
    .warning { background: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #92400e; margin-top: 16px; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header">
      <h1>🎓 ECA</h1>
      <p>Member Net Association</p>
    </div>
    <div class="body">
      <p>Hi <b>{{ $userName }}</b>,</p>
      <p>We received a request to reset your password. Click the button below to set a new password:</p>
      <a href="{{ $resetUrl }}" class="btn">Reset My Password</a>
      <div class="warning">
        ⏱ This link expires in <b>60 minutes</b>. If you did not request a password reset, please ignore this email.
      </div>
    </div>
    <div class="footer">
      &copy; {{ date('Y') }} ECA — Member Net Association
    </div>
  </div>
</body>
</html>

