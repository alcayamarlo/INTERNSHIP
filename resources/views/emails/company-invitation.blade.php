<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitation to partner with St. Cecilia's College – Cebu</title>
</head>
<body style="font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px; border: 1px solid #e5e7eb;">
        <p style="margin: 0 0 4px; font-size: 13px; letter-spacing: .04em; text-transform: uppercase; color: #5E010B; font-weight: 700;">St. Cecilia's College – Cebu</p>
        <p style="margin: 0 0 20px; font-size: 13px; color: #6b7280;">On-the-Job Training (OJT) Program · Partner Company Invitation</p>

        <p style="font-size: 16px; margin: 0 0 14px;">Dear {{ $companyName }},</p>

        <p style="font-size: 16px; margin: 0 0 14px;">Greetings from St. Cecilia's College – Cebu.</p>

        <p style="font-size: 16px; margin: 0 0 14px;">
            On behalf of the College{{ $coordinatorDepartment ? ' and the '.$coordinatorDepartment.' department' : '' }}, I respectfully invite your company to become a partner in our On-the-Job Training (OJT) Program through SkillBridge, the College's internship placement system.
        </p>

        <p style="font-size: 16px; margin: 0 0 8px;">Once you accept this invitation, your company will be able to:</p>
        <ul style="font-size: 16px; margin: 0 0 14px; padding-left: 22px;">
            <li>post OJT openings for our students;</li>
            <li>review applicants and receive qualified student interns;</li>
            <li>record attendance and complete the required evaluations online.</li>
        </ul>

        <p style="font-size: 16px; margin: 0 0 18px;">To accept, please use the button below to confirm your company details and set a password for your account.</p>

        <p style="margin: 0 0 8px;">
            <a href="{{ $url }}" style="display: inline-block; background: #5E010B; color: #ffffff; text-decoration: none; font-weight: 700; padding: 12px 22px; border-radius: 8px;">Accept Invitation and Create Account</a>
        </p>
        <p style="font-size: 13px; color: #4b5563; margin: 14px 0 4px;">If the button does not work, copy this address into your browser:<br><span style="word-break: break-all;">{{ $url }}</span></p>
        <p style="font-size: 13px; color: #4b5563; margin: 0 0 22px;">
            Reference no.: <strong>{{ $reference }}</strong><br>
            This invitation is valid until <strong>{{ $expiresAt }}</strong> (Philippine time) and can be used once.
        </p>

        <p style="font-size: 16px; margin: 0 0 4px;">For questions, you may reach us at:</p>
        <p style="font-size: 14px; color: #374151; margin: 0 0 22px;">
            {{ $coordinatorName }}, OJT Coordinator{{ $coordinatorDepartment ? ' – '.$coordinatorDepartment : '' }}<br>
            @if($coordinatorOffice){{ $coordinatorOffice }}<br>@endif
            @if($coordinatorEmail)Email: {{ $coordinatorEmail }}<br>@endif
            @if($coordinatorPhone)Phone: {{ $coordinatorPhone }}@endif
        </p>

        <p style="font-size: 16px; margin: 0 0 2px;">Respectfully yours,</p>
        <p style="font-size: 16px; margin: 0 0 22px;"><strong>{{ $coordinatorName }}</strong><br>OJT Coordinator, St. Cecilia's College – Cebu</p>

        <p style="font-size: 12px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 12px; margin: 0;">
            If you were not expecting this invitation, you may disregard this email. No account is created until the form is completed.
        </p>
    </div>
</body>
</html>