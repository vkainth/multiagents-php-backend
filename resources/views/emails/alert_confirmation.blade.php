<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirm your listing alert</title>
</head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;padding:30px 0;">
  <tr>
    <td align="center">
      <table width="560" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.1);">
        <tr>
          <td style="background:#231f20;padding:24px 30px;">
            <img src="https://www.bccondosandhomes.com/frontend/images/bccondosandhome-1.jpg" alt="BC Condos And Homes" height="36" style="display:block;">
          </td>
        </tr>
        <tr>
          <td style="padding:30px;">
            <h2 style="margin:0 0 16px;font-size:22px;color:#231f20;">Confirm your listing alert</h2>
            <p style="margin:0 0 12px;font-size:15px;color:#444;line-height:1.6;">
              You asked to receive alerts for <strong>{{ $contextTitle }}</strong>.
              Click the button below to confirm your email address and activate your alert.
            </p>
            <p style="margin:0 0 24px;font-size:13px;color:#888;">This confirmation link expires in 7 days.</p>
            <a href="{{ $confirmUrl }}" style="display:inline-block;background:#2c6fad;color:#fff;font-size:15px;font-weight:700;padding:14px 28px;border-radius:5px;text-decoration:none;">
              Confirm &amp; Activate Alert
            </a>
            <p style="margin:24px 0 0;font-size:12px;color:#aaa;line-height:1.6;">
              If you didn't request this, simply ignore this email. No alerts will be sent without confirmation.<br>
              Questions? Reply to this email or call <a href="tel:6042293342" style="color:#2c6fad;">604-229-3342</a>.
            </p>
          </td>
        </tr>
        <tr>
          <td style="background:#f7f4ef;padding:16px 30px;font-size:11px;color:#999;text-align:center;">
            &copy; {{ date('Y') }} BC Condos And Homes &mdash; Hani &amp; Les &mdash;
            <a href="https://www.bccondosandhomes.com" style="color:#888;">bccondosandhomes.com</a>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
</body>
</html>
