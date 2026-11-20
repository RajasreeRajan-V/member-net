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

    .header-approved { background: linear-gradient(135deg, #34d399, #10b981);
                       padding: 28px 32px; text-align: center; }
    .header-rejected { background: linear-gradient(135deg, #4a5a7a, #0f1729);
                       padding: 28px 32px; text-align: center; }
    .header-approved h1,
    .header-rejected h1 { font-family: 'Instrument Serif', serif; color: #fff; font-size: 24px; margin: 0 0 4px; font-weight: 400; }
    .header-approved p,
    .header-rejected p  { color: rgba(255,255,255,0.8); font-size: 12px; margin: 0; }
    .badge { background: rgba(255,255,255,0.2); color: #fff; border-radius: 20px;
             padding: 4px 14px; font-size: 11px; font-weight: 600;
             display: inline-block; margin-top: 10px; letter-spacing: 0.3px; }

    .body  { padding: 32px; color: #0f1729; }
    .body p { font-size: 14px; line-height: 1.7; margin: 0 0 14px; color: #374151; }

    /* Approved */
    .success-box { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 8px;
                   padding: 13px 16px; margin-bottom: 18px; text-align: center; }
    .success-box p { margin: 0; font-size: 14px; color: #065f46; font-weight: 600; }

    .steps-title { font-size: 14px; font-weight: 700; color: #0f1729;
                   margin: 0 0 14px; }
    .step-table  { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .step-num-td { width: 32px; vertical-align: top; padding-top: 1px; }
    .step-num    { width: 24px; height: 24px; background: #4f8ef7; color: #fff;
                   border-radius: 50%; font-size: 12px; font-weight: 700;
                   text-align: center; line-height: 24px;
                   display: inline-block; }
    .step-text   { font-size: 13px; color: #374151; line-height: 1.6;
                   padding-left: 10px; vertical-align: top; padding-top: 3px; }

    /* Rejected */
    .reason-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;
                  padding: 13px 16px; margin-bottom: 18px; }
    .reason-box h4 { margin: 0 0 6px; font-size: 10.5px; color: #991b1b;
                     text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
    .reason-box p { margin: 0; font-size: 13px; color: #7f1d1d; }

    .contact-box { background: rgba(15,23,41,0.04); border: 1px solid rgba(15,23,41,0.1); border-radius: 8px;
                   padding: 13px 16px; margin-bottom: 18px; }
    .contact-box p { margin: 0; font-size: 13px; color: #4a5a7a; }

    .btn-wrap  { text-align: center; margin: 20px 0 8px; }
    .btn-blue  { display: inline-block; padding: 13px 36px; background: #4f8ef7;
                 color: #fff; text-decoration: none; border-radius: 8px;
                 font-weight: 600; font-size: 14px; }

    .footer { text-align: center; padding: 16px 32px 24px; font-size: 11px;
              color: #4a5a7a; border-top: 1px solid rgba(15,23,41,0.08); }
  </style>
</head>
<body>
<div class="wrap">

  {{-- ── HEADER ── --}}
  @if($status === 'approved')
    <div class="header-approved">
      <h1>🎓 Member Net</h1>
      <p>Member Net Association</p>
      <div class="badge">✅ Membership Approved</div>
    </div>
  @else
    <div class="header-rejected">
      <h1>🎓 Member Net</h1>
      <p>Member Net Association</p>
      <div class="badge">❌ Application Update</div>
    </div>
  @endif

  <div class="body">

    {{-- ══ APPROVED ══ --}}
    @if($status === 'approved')

      <div class="success-box">
        <p>🎉 Congratulations! Your membership has been approved.</p>
      </div>

      <p>Dear <b>{{ $memberName }}</b>,</p>
      <p>
        Your Member Net membership application for <b>{{ $unitName }}</b> unit
        has been reviewed and approved by your Unit Admin.
      </p>

      <p class="steps-title">Next steps to activate your membership:</p>

      <table class="step-table">
        <tr>
          <td class="step-num-td">
            <span class="step-num">1</span>
          </td>
          <td class="step-text">
            Log in to the Member Net app using your registered email and password.
          </td>
        </tr>
      </table>

      <table class="step-table">
        <tr>
          <td class="step-num-td">
            <span class="step-num">2</span>
          </td>
          <td class="step-text">
            Your membership is now <b>ACTIVE</b> and available in your
            <b>Dashboard</b>.
          </td>
        </tr>
      </table>

      <table class="step-table" style="margin-bottom:20px;">
        <tr>
          <td class="step-num-td">
            <span class="step-num">3</span>
          </td>
          <td class="step-text">
            Your <b>Membership ID card</b> will be available in your account.
          </td>
        </tr>
      </table>

    {{-- ══ REJECTED ══ --}}
    @else

      <p>Dear <b>{{ $memberName }}</b>,</p>
      <p>
        Thank you for applying for Member Net membership under
        <b>{{ $unitName }}</b> unit. After review, your application
        has not been approved at this time.
      </p>

      @if($reason)
        <div class="reason-box">
          <h4>Reason</h4>
          <p>{{ $reason }}</p>
        </div>
      @endif

      <div class="contact-box">
        <p>
          If you believe this is an error or would like to re-apply,
          please contact your unit admin at
          <b>{{ $contactEmail }}</b>.
        </p>
      </div>

      <div class="btn-wrap">
        <a href="mailto:{{ $contactEmail }}" class="btn-blue">
          Contact Unit Admin
        </a>
      </div>

    @endif

    <p style="font-size:11px;color:#4a5a7a;text-align:center;margin-top:8px;">
      This is an automated email. Please do not reply directly.
    </p>
  </div>

  <div class="footer">
    &copy; {{ date('Y') }} Member Net
  </div>

</div>
</body>
</html>