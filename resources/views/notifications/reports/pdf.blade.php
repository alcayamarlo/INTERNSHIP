<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #333; }
        h1 { color: #2563EB; font-size: 18px; border-bottom: 2px solid #2563EB; padding-bottom: 8px; }
        .meta { color: #666; margin-bottom: 20px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #2563EB; color: #fff; padding: 6px 8px; text-align: left; font-size: 9px; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) td { background: #f8fafc; }
        .footer { margin-top: 30px; font-size: 8px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ $title ?? ucfirst($type ?? 'System') . ' Report' }}</h1>
    <div class="meta">
        Generated: {{ now()->format('F d, Y h:i A') }} | Skill-Bridge System
    </div>

    @if(!empty($data))
        <table>
            <thead>
                <tr>
                    @foreach(array_keys($data[0]) as $header)
                        <th>{{ ucwords(str_replace('_', ' ', $header)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @foreach($row as $value)
                            <td>{{ $value ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p class="meta" style="margin-top:10px;">Total records: {{ count($data) }}</p>
    @else
        <p>No data available for this report.</p>
    @endif

    <div class="footer">Skill-Bridge System &mdash; Confidential Report</div>
</body>
</html>
