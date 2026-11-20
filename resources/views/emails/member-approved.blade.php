<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'DM Sans', Arial, sans-serif; background: #f0f4ff; margin: 0; padding: 30px 0; color: #0f1729; }
    .wrap { max-width: 520px; margin: 0 auto; background: #ffffff; border-radius: 13px;
            overflow: hidden; border: 1px solid rgba(15,23,41,0.1); box-shadow: 0 4px 24px rgba(15,23,41,0.06); }

    .header { background: linear-gradient(135deg, #34d399, #10b981);
              padding: 28px 32px; text-align: center; }
    .header h1 { font-family: 'Instrument Serif', serif; color: #fff; font-size: 24px; margin: 0 0 4px; font-weight: 400; }
    .header p  { color: rgba(255,255,255,0.8); font-size: 12px; margin: 0; }
    .badge { background: rgba(255,255,255,0.2); color: #fff; border-radius: 20px;
             padding: 4px 14px; font-size: 11px; font-weight: 600;
             display: inline-block; margin-top: 10px; letter-spacing: 0.3px; }

    .body  { padding: 32px; color: #0f1729; }
    .body p { font-size: 14px; line-height: 1.7; margin: 0 0 14px; color: #374151; }

    .success-box { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 8px;
                   padding: 13px 16px; margin-bottom: 18px; text-align: center; }
    .success-box p { margin: 0; font-size: 14px; color: #065f46; font-weight: 600; }

    .cred-box { background: rgba(15,23,41,0.04); border: 1px solid rgba(15,23,41,0.1);
                border-radius: 11px; padding: 18px 20px; margin: 18px 0; }
    .cred-box h4 { margin: 0 0 12px; font-size: 10.5px; color: #3a7ae0;
                   text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
    .cred-table { width: 100%; border-collapse: collapse; }
    .cred-table td { font-size: 13px; padding: 7px 0;
                     border-bottom: 1px solid rgba(15,23,41,0.08); }
    .cred-table tr:last-child td { border-bottom: none; }
    .cred-label { color: #4a5a7a; width: 90px; }
    .cred-value { font-weight: 700; color: #0f1729;
                  font-family: 'Courier New', monospace; font-size: 14px; }

    .steps-title { font-size: 14px; font-weight: 700; color: #0f1729; margin: 0 0 14px; }
    .step-table  { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .step-num-td { width: 32px; vertical-align: top; padding-top: 1px; }
    .step-num    { width: 24px; height: 24px; background: #4f8ef7; color: #fff;
                   border-radius: 50%; font-size: 12px; font-weight: 700;
                   text-align: center; line-height: 24px; display: inline-block; }
    .step-text   { font-size: 13px; color: #374151; line-height: 1.6;
                   padding-left: 10px; vertical-align: top; padding-top: 3px; }

    .warning { background: #fefce8; border: 1px solid #fde68a; border-radius: 8px;
               padding: 10px 14px; font-size: 12px; color: #854d0e; margin-top: 16px; }
    .footer  { text-align: center; padding: 16px 32px 24px; font-size: 11px;
               color: #4a5a7a; border-top: 1px solid rgba(15,23,41,0.08); }
  </style>
</head>
<body>
<div class="wrap">

  <div class="header">
    <h1>🎓 Member Net</h1>
    <div class="badge">✅ Membership Approved</div>
  </div>

  <div class="body">

    <div class="success-box">
      <p>🎉 Congratulations! Your membership has been approved.</p>
    </div>

    <p>Dear <b>{{ $memberName }}</b>,</p>
    <p>
      Your Member Net membership application for <b>{{ $unitName }}</b> unit
      has been reviewed and approved by your Unit Admin. Here are your login
      credentials — keep them safe.
    </p>

    <div class="cred-box">
      <h4>🔑 Your Login Credentials</h4>
      <table class="cred-table">
        <tr>
          <td class="cred-label">Username</td>
          <td class="cred-value">{{ $membershipId }}</td>
        </tr>
        <tr>
          <td class="cred-label">Password</td>
          <td class="cred-value">{{ $phone }}</td>
        </tr>
        <tr>
          <td class="cred-label">Unit</td>
          <td class="cred-value" style="font-family:'DM Sans',Arial,sans-serif;">{{ $unitName }}</td>
        </tr>
      </table>
    </div>

    <p class="steps-title">Next steps to activate your membership:</p>

    <table class="step-table">
      <tr>
        <td class="step-num-td"><span class="step-num">1</span></td>
        <td class="step-text">
          Log in to the Member Net app using your <b>Membership ID</b> and
          <b>phone number</b> as the password.
        </td>
      </tr>
    </table>

    <table class="step-table">
      <tr>
        <td class="step-num-td"><span class="step-num">2</span></td>
        <td class="step-text">
          Your membership is now <b>ACTIVE</b> and available in your
          <b>Dashboard</b>.
        </td>
      </tr>
    </table>

    <table class="step-table" style="margin-bottom:20px;">
      <tr>
        <td class="step-num-td"><span class="step-num">3</span></td>
        <td class="step-text">
          Your <b>Membership ID card</b> will be available in your account.
        </td>
      </tr>
    </table>

    <div class="warning">
      ⚠️ Please <b>change your password</b> after your first login.
      Do not share these credentials with anyone.
    </div>

    <p style="font-size:11px;color:#4a5a7a;text-align:center;margin-top:16px;">
      This is an automated email. Please do not reply directly.
    </p>
  </div>

  <div class="footer">
    &copy; {{ date('Y') }} Member Net
  </div>

</div>
</body>
</html>