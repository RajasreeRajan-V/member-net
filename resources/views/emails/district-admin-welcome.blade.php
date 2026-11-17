<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>ECA District Admin Account Created</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 0;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0"
                       style="background:#ffffff;border-radius:8px;overflow:hidden;">
                    <tr>
                        <td style="background:#4f8ef7;padding:20px 28px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:700;">
                                ECA — District Admin Account
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;color:#1f2937;">
                            <p style="margin:0 0 12px;font-size:15px;">
                                Hi {{ $adminName }},
                            </p>
                            <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#4b5563;">
                                An admin account has been created for you to manage the
                                <b>{{ $districtName }}</b> district ({{ $stateName }} state)
                                on the ECA platform. This includes overseeing every unit,
                                unit admin and member within your district.
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0"
                                   style="background:#f9fafb;border:1px solid #e5e7eb;
                                          border-radius:6px;margin-bottom:18px;">
                                <tr>
                                    <td style="padding:14px 16px;font-size:13px;color:#6b7280;">Email</td>
                                    <td style="padding:14px 16px;font-size:13px;font-weight:700;
                                               color:#1f2937;text-align:right;">
                                        {{ $email }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 16px 14px;font-size:13px;color:#6b7280;">Password</td>
                                    <td style="padding:0 16px 14px;font-size:13px;font-weight:700;
                                               color:#1f2937;text-align:right;">
                                        {{ $password }}
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 18px;font-size:12.5px;color:#f59e0b;">
                                For security, please log in and change this password as soon as possible.
                            </p>

                            <a href="{{ url('/district-admin/login') }}"
                               style="display:inline-block;background:#4f8ef7;color:#ffffff;
                                      text-decoration:none;font-size:14px;font-weight:600;
                                      padding:10px 22px;border-radius:6px;">
                                Log In
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 28px;background:#f9fafb;
                                   font-size:11px;color:#9ca3af;text-align:center;">
                            ECA Admin Team
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>