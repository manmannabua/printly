<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update your application</title>
</head>
<body style="margin:0; padding:0; background:#f5f5f5; font-family: Arial, Helvetica, sans-serif; color:#222;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; padding:32px;">
                    <tr><td>
                        <h1 style="margin:0 0 16px; font-size:20px;">Hi {{ $firstName }},</h1>
                        <p style="font-size:14px; line-height:1.6; margin:0 0 16px;">
                            Our recruitment team has invited you to review and update the application you submitted.
                            Use the secure link below to open your application form &mdash; your previously submitted
                            information will be pre-filled, and you can edit anything that needs to change before
                            re-submitting.
                        </p>
                        <p style="margin:24px 0;">
                            <a href="{{ $editUrl }}" style="display:inline-block; background:#1e40af; color:#ffffff; padding:12px 20px; border-radius:6px; text-decoration:none; font-weight:600; font-size:14px;">
                                Update my application
                            </a>
                        </p>
                        <p style="font-size:13px; line-height:1.6; color:#555; margin:0 0 8px;">
                            Or paste this URL into your browser:<br>
                            <span style="word-break:break-all; color:#1e40af;">{{ $editUrl }}</span>
                        </p>
                        <p style="font-size:13px; line-height:1.6; color:#555; margin:24px 0 0;">
                            This link expires on <strong>{{ $expiresAt->format('M j, Y \a\t g:i A') }}</strong>. After
                            you submit your changes, your update will be reviewed by our recruitment team before it
                            replaces the version on file.
                        </p>
                        <p style="font-size:12px; color:#888; margin:24px 0 0;">
                            If you didn&rsquo;t expect this email, you can safely ignore it &mdash; the link will expire
                            on its own.
                        </p>
                    </td></tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
