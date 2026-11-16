<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partner company invitation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; color: #111827;">You are invited to SkillBridge</h2>
        <p style="font-size: 16px;">Hello {{ $companyName }},</p>
        <p style="font-size: 16px;">{{ $coordinatorName }} of St. Cecilia's College – Cebu invited your company to join SkillBridge, the school's OJT and internship placement system, as a partner company.</p>
        <p style="font-size: 16px;">Open the link below to check your company details, add the missing information, and set a password. Your account is ready as soon as you finish.</p>
        <p style="margin: 24px 0;">
            <a href="{{ $url }}" style="display: inline-block; background: #5E010B; color: #ffffff; text-decoration: none; font-weight: 700; padding: 12px 20px; border-radius: 8px;">Complete your company account</a>
        </p>
        <p style="font-size: 14px; color: #4b5563;">This link works until {{ $expiresAt }} (Philippine time) and can be used once. Reference: {{ $reference }}.</p>
        <p style="font-size: 14px; color: #4b5563;">If the button does not work, copy this address into your browser:<br><span style="word-break: break-all;">{{ $url }}</span></p>
        <p style="font-size: 13px; color: #6b7280;">If you were not expecting this email, you can ignore it. No account is created until the form is completed.</p>
    </div>
</body>
</html>
