<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relay Delivery Failure Alert — LocalDateHub</title>
</head>
<body style="margin:0;padding:0;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F172A;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F8FAFC;padding:28px 14px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background-color:#FFFFFF;border-radius:16px;border:1px solid #FECDD3;overflow:hidden;">
                <tr>
                    <td style="background-color:#BE123C;padding:20px 26px;color:#FFFFFF;">
                        <div style="font-size:18px;font-weight:900;">LocalDateHub · Email Relay Failure Alert</div>
                        <div style="font-size:11px;color:#FFE4E6;margin-top:3px;">Message ID: {{ $messageId }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px;font-size:14px;color:#0F172A;">An email relay delivery failed after retry attempts:</p>
                        <div style="background-color:#FFF1F2;border:1px solid #FECDD3;border-radius:12px;padding:14px;font-size:13px;color:#881337;">
                            <div><strong>Listing ID:</strong> #{{ $listingId }}</div>
                            <div style="margin-top:4px;"><strong>Direction:</strong> {{ strtoupper($direction) }}</div>
                            <div style="margin-top:4px;"><strong>Error:</strong> {{ $errorMessage }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

