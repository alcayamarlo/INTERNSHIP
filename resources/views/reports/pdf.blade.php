<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            margin: 30px;
        }

        h1 {
            margin-bottom: 18px;
            font-size: 28px;
            color: #0f172a;
        }

        .meta {
            margin-bottom: 20px;
            color: #475569;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 11px;
        }

        th, td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: bold;
        }

        tr:nth-child(even) td {
            background: #f8fafc;
        }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Report' }}</h1>
    <div class="meta">
        Generated: {{ now()->format('F d, Y h:i A') }}
    </div>

    @php
        $rows = $data ?? [];
        $headers = !empty($rows) ? array_keys($rows[0]) : [];
    @endphp

    @if(empty($rows))
        <p>No records available for this report.</p>
    @else
        <table>
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ ucfirst(str_replace('_', ' ', $header)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        @foreach($headers as $header)
                            <td>{{ $row[$header] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
