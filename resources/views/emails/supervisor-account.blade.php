<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your SkillBridge supervisor account</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 20px; font-size: 13px; letter-spacing: .04em; text-transform: uppercase; color: #5E010B; font-weight: 700;">SkillBridge · OJT supervisor account</p>

        <p style="font-size: 16px; margin: 0 0 14px;">Dear {{ $name }},</p>

        <p style="font-size: 16px; margin: 0 0 14px;">
            {{ $hrName ?: 'The HR representative' }} of <strong>{{ $company }}</strong> created a supervisor account for you on SkillBridge, the On-the-Job Training (OJT) system of St. Cecilia's College – Cebu{!! $position ? ', as <strong>'.e($position).'</strong>' : '' !!}.
        </p>

        <p style="font-size: 16px; margin: 0 0 8px;">As a supervisor, you will be able to:</p>
        <ul style="font-size: 16px; margin: 0 0 14px; padding-left: 22px;">
            <li>approve the daily time records of the interns assigned to you;</li>
            <li>certify their weekly reports as true and correct;</li>
            <li>complete their monthly and final performance evaluations.</li>
        </ul>

        <p style="font-size: 16px; margin: 0 0 18px;">To start, please set your own password using the button below.</p>

        <p style="margin: 0 0 8px;">
            <a href="{{ $url }}" style="display: inline-block; background: #5E010B; color: #ffffff; text-decoration: none; font-weight: 700; padding: 12px 22px; border-radius: 8px;">Set my password</a>
        </p>
        <p style="font-size: 13px; color: #4b5563; margin: 14px 0 4px;">If the button does not work, copy this address into your browser:<br><span style="word-break: break-all;">{{ $url }}</span></p>
        <p style="font-size: 13px; color: #4b5563; margin: 0 0 22px;">
            This link works for {{ $days }} days and sets the password for <strong>{{ $email }}</strong>, which is the email you will sign in with.
        </p>

        <p style="font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 12px; margin: 0;">
            If you were not expecting this email, you may disregard it; no one can sign in to the account until a password is set. For questions, please contact your HR representative.
        </p>
    </div>
</body>
</html>
