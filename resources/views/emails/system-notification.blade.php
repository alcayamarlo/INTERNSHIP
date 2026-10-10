<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb;">
        <h2 style="margin-top: 0; color: #111827;">{{ $title }}</h2>
        <p style="margin-bottom: 20px; font-size: 16px;">{{ $message }}</p>

        @if (!empty($data))
            <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
                @foreach ($data as $key => $value)
                    <tr>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; color: #4b5563; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', (string) $key)) }}</td>
                        <td style="padding: 8px 0; border-bottom: 1px solid #e5e7eb; color: #111827;">{{ is_scalar($value) ? (string) $value : json_encode($value) }}</td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</body>
</html>
