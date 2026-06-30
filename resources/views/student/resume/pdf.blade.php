<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['personal']['name'] ?? $student->user->name }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .header { border-bottom: 2px solid #2563EB; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { margin: 0; color: #2563EB; font-size: 22px; }
        .header p { margin: 2px 0; color: #666; }
        h2 { color: #2563EB; font-size: 13px; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; margin-top: 15px; }
        .item { margin-bottom: 8px; }
        .item-title { font-weight: bold; }
        .item-meta { color: #666; font-size: 10px; }
        ul { margin: 5px 0; padding-left: 18px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $content['personal']['name'] ?? $student->user->name }}</h1>
        <p>
            {{ $content['personal']['email'] ?? '' }}
            @if(!empty($content['personal']['phone'])) | {{ $content['personal']['phone'] }} @endif
        </p>
        @if(!empty($content['personal']['address']))<p>{{ $content['personal']['address'] }}</p>@endif
        @if(!empty($content['personal']['institution']))
            <p>{{ $content['personal']['institution'] }} — {{ $content['personal']['program'] ?? '' }} {{ $content['personal']['year_level'] ?? '' }}</p>
        @endif
    </div>

    @if(!empty($content['objectives']))
        <h2>Career Objectives</h2>
        <p>{{ $content['objectives'] }}</p>
    @endif

    @if(!empty($content['competencies']))
        <h2>Competencies</h2>
        @foreach($content['competencies'] as $comp)
            <div class="item">
                <span class="item-title">{{ $comp['name'] }}</span>
                <span class="item-meta"> — {{ $comp['category'] }} | {{ $comp['level'] }}</span>
                @if(!empty($comp['description']))<br><span>{{ $comp['description'] }}</span>@endif
            </div>
        @endforeach
    @endif

    @if(!empty($content['certificates']))
        <h2>Certifications</h2>
        @foreach($content['certificates'] as $cert)
            <div class="item">
                <span class="item-title">{{ $cert['title'] }}</span>
                @if(!empty($cert['issuer']))<span class="item-meta"> — {{ $cert['issuer'] }}</span>@endif
                @if(!empty($cert['issue_date']))<span class="item-meta"> ({{ $cert['issue_date'] }})</span>@endif
            </div>
        @endforeach
    @endif

    @if(!empty($content['portfolios']))
        <h2>Portfolio</h2>
        @foreach($content['portfolios'] as $item)
            <div class="item">
                <span class="item-title">{{ $item['title'] }}</span>
                <span class="item-meta"> — {{ $item['type'] }}</span>
                @if(!empty($item['description']))<br><span>{{ $item['description'] }}</span>@endif
            </div>
        @endforeach
    @endif
</body>
</html>
