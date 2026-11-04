<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OJT statistics</title>
    <style>
        @page { size: A4 portrait; margin: 20mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #222; }
        h1 { font-size: 16pt; color: #B3001B; margin: 0 0 2mm; }
        h3 { font-size: 11pt; margin: 6mm 0 2mm; }
        .meta { color: #666; margin: 0 0 6mm; }
        .stats-table { width: 100%; border-collapse: collapse; }
        .stats-table th, .stats-table td { border-bottom: 0.3mm solid #ddd; padding: 1.5mm 2mm; text-align: left; }
        .stats-table thead th { background: #E31B2D; color: #fff; }
        .text-end { text-align: right !important; }
    </style>
</head>
<body>
    <h1>OJT statistics</h1>
    <p class="meta">Generated {{ $stats['generated_at']->format('M d, Y h:i A') }} (Philippine time)</p>
    @include('coordinator.statistics.partials.tables')
</body>
</html>
