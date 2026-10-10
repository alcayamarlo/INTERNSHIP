<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Invitation</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; color: #111827;">Interview Invitation</h2>

        <p>Hello{{ $recipientName ? ' '.$recipientName : '' }},</p>

        <p>You have been invited to interview for <strong>{{ $internshipTitle }}</strong> with {{ $companyName ?: 'the employer' }}.</p>

        <p>
            <strong>Date & Time:</strong> {{ $interviewAt->format('F j, Y g:i A') }}<br>
            @if($location)
                <strong>Location:</strong> {{ $location }}
            @endif
        </p>

        <p>Please review the attached calendar invite and confirm your availability.</p>
    </div>
</body>
</html>
