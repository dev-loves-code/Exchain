<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $payload['title'] }}</title>
</head>
<body style="margin:0;padding:0;font-family:Arial, sans-serif;background:#f4f4f5;color:#374151;">
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="center" style="padding:30px 10px;">
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 10px rgba(0,0,0,0.05);">

                <!-- Header -->
                <tr>
                    <td style="background: linear-gradient(135deg, #1a4d4d 0%, #0a2f2f 100%);padding:40px 20px;text-align:center;color:white;border-bottom: 4px solid #b9f442;">
                        <h1 style="margin:0;font-size:28px;font-weight:bold;color:#b9f442;">
                            {{ $payload['title'] }}
                        </h1>
                        <p style="margin:10px 0 0;font-size:16px;color:#e5e7eb;">
                            {{ $payload['subtitle'] }}
                        </p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:30px 25px;text-align:center;font-size:16px;line-height:1.6;">

                        <!-- Refund Status Badge -->
                        @if(isset($payload['status']))
                            @php
                                $color = $payload['status'] === 'approved' ? '#b9f442' : '#ef4444';
                                $textColor = $payload['status'] === 'approved' ? '#0a2f2f' : '#ffffff';
                                $label = $payload['status'] === 'approved' ? 'Refund Approved' : 'Refund Rejected';
                            @endphp

                            <span style="display:inline-block;padding:10px 20px;background:{{ $color }};color:{{ $textColor }};border-radius:8px;font-weight:bold;font-size:15px;margin-bottom:20px;">
                                {{ $label }}
                            </span>
                        @endif

                        <p style="margin:20px 0;">
                            {{ $payload['message'] ?? 'We have reviewed your refund request. Please see the status above.' }}
                        </p>

                        @if(isset($payload['cta_url']) && isset($payload['cta_text']))
                            <a href="{{ $payload['cta_url'] }}"
                               style="display:inline-block;padding:12px 30px;background:#b9f442;color:#0a2f2f;font-weight:bold;font-size:16px;border-radius:8px;text-decoration:none;margin-top:10px;">
                                {{ $payload['cta_text'] }}
                            </a>
                        @endif
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="padding:20px 25px;text-align:center;font-size:14px;color:#6b7280;border-top:1px solid #e5e7eb;">
                        If you have questions about this decision, feel free to reach out.<br>
                        <strong style="color:#1a4d4d;">Exchain Support</strong>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
