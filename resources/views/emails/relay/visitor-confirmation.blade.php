<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Sent Privately — LocalDateHub</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F172A;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F8FAFC;padding:28px 14px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#FFFFFF;border-radius:16px;border:1px solid #E2E8F0;overflow:hidden;">
                <tr>
                    <td style="background-color:#F50057;padding:22px 26px;">
                        <span style="font-size:20px;font-weight:900;color:#FFFFFF;letter-spacing:-0.02em;">LocalDateHub</span>
                        <div style="font-size:11px;color:#FFE4EE;margin-top:4px;font-weight:600;">Encrypted Relay Confirmation</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:26px;">
                        <h2 style="margin:0 0 10px;font-size:18px;font-weight:800;color:#0F172A;">Your message has been securely relayed</h2>
                        <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#475569;">
                            Your inquiry for <strong>{{ $listingTitle }}</strong> was encrypted and queued for delivery through LocalDateHub. Your personal email address remains private.
                        </p>
                        <div style="background-color:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:14px;font-size:12px;color:#475569;">
                            <div><strong>Subject:</strong> {{ $subjectLine }}</div>
                            <div style="margin-top:6px;"><strong>Relay Address:</strong> {{ $maskedRelayEmail }}</div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 26px;background-color:#F8FAFC;border-top:1px solid #E2E8F0;font-size:11px;color:#64748B;">
                        LocalDateHub Privacy Protection · <a href="{{ $listingUrl }}" style="color:#F50057;text-decoration:none;font-weight:700;">Return to Listing</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

