<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background-color:#F1F5F9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0F172A;line-height:1.5;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#F1F5F9;padding:32px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background-color:#FFFFFF;border-radius:18px;border:1px solid #E2E8F0;box-shadow:0 10px 25px -5px rgba(15,23,42,0.06);overflow:hidden;">
                <!-- Brand Header -->
                <tr>
                    <td style="background-color:#0F172A;padding:20px 28px;border-bottom:3px solid #F50057;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                    <span style="font-size:21px;font-weight:900;color:#FFFFFF;letter-spacing:-0.03em;">LocalDate<span style="color:#F50057;">Hub</span></span>
                                    <div style="font-size:11px;color:#94A3B8;margin-top:3px;font-weight:600;letter-spacing:0.02em;">Two-Way Encrypted Email Relay</div>
                                </td>
                                <td align="right">
                                    <span style="display:inline-block;padding:5px 12px;background-color:rgba(245,0,87,0.18);border:1px solid rgba(245,0,87,0.4);border-radius:999px;font-size:11px;font-weight:700;color:#FF80AB;">Private Reply</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <!-- Main Content Area -->
                <tr>
                    <td style="padding:28px 28px 20px;">
                        <!-- Listing Context Badge -->
                        <div style="background-color:#FFF1F5;border:1px solid #FBCFE8;border-radius:12px;padding:12px 16px;margin-bottom:20px;">
                            <div style="font-size:11px;font-weight:800;color:#9D174D;text-transform:uppercase;letter-spacing:0.06em;">Reply Regarding Listing</div>
                            <div style="font-size:14px;font-weight:800;color:#0F172A;margin-top:2px;">
                                <a href="{{ $listingUrl }}" style="color:#0F172A;text-decoration:none;">{{ $listingTitle }}</a>
                            </div>
                            <div style="font-size:12px;color:#64748B;margin-top:4px;">
                                From Advertiser · Masked Relay: <code style="background-color:#FFFFFF;padding:2px 6px;border-radius:6px;border:1px solid #FBCFE8;font-size:11px;color:#BE185D;">{{ $maskedRelayEmail }}</code>
                            </div>
                        </div>

                        <!-- Actual Reply Body -->
                        <div style="margin-bottom:8px;font-size:12px;font-weight:800;color:#475569;text-transform:uppercase;letter-spacing:0.05em;">
                            Advertiser's Response:
                        </div>
                        <div style="background-color:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #F50057;border-radius:12px;padding:20px;font-size:15px;line-height:1.65;color:#1E293B;white-space:pre-line;font-weight:450;">{{ $bodyText }}</div>

                        <!-- Direct Reply Callout (Keep 2-way looping forever) -->
                        <div style="margin-top:24px;padding:16px 18px;background-color:#F0FDF4;border:1px solid #BBF7D0;border-radius:14px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="28" valign="top" style="padding-top:2px;">
                                        <span style="font-size:18px;">💬</span>
                                    </td>
                                    <td style="padding-left:8px;">
                                        <div style="font-size:13px;font-weight:800;color:#166534;">Continue the conversation directly!</div>
                                        <div style="font-size:12px;color:#15803D;margin-top:3px;line-height:1.5;">
                                            Hit <strong>Reply</strong> in your email client to send your next message. Your real email remains protected and hidden.
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>

                <!-- Privacy & Footer -->
                <tr>
                    <td style="padding:16px 28px;background-color:#F8FAFC;border-top:1px solid #E2E8F0;font-size:11px;color:#64748B;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
                            <tr>
                                <td>
                                    🔒 Protected by <strong>LocalDateHub Privacy Shield</strong>. Real emails are never exposed.
                                </td>
                                <td align="right">
                                    <a href="{{ $listingUrl }}" style="color:#F50057;text-decoration:none;font-weight:700;">View Live Listing →</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

