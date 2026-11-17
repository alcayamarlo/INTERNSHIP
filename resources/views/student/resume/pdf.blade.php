<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['name'] }}</title>
    @php
        // "compact" is the tighter spacing the builder falls back to when the normal one runs over a page.
        $compact = ($density ?? 'normal') === 'compact';
        $size = $compact ? '9pt' : '10pt';
        $headline = collect([$content['course'] ?? null, $content['school'] ?? null])->filter()->implode(' · ');
        $contactLine = collect($content['contact'] ?? [])->filter()->implode('  |  ');
        $skills = $content['skills'] ?? [];
        $technical = $skills['technical'] ?? [];
        $soft = $skills['soft'] ?? [];
        $generatedAt = filled($content['generated_at'] ?? null)
            ? \Illuminate\Support\Carbon::parse($content['generated_at'])->timezone('Asia/Manila')
            : null;
        // Entry rows ("Issuer: DICT") on one muted line.
        $meta = fn (array $rows): string => collect($rows)->map(fn ($value, $label) => $label.': '.$value)->implode('  ·  ');
    @endphp
    <style>
        :root { --resume-accent: #5E010B; --resume-text: #222222; --resume-muted: #666666; --resume-rule: #CFCFCF; }

        @page { size: A4 portrait; margin: {{ $compact ? '10mm 12mm' : '12mm 14mm' }}; }

        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            margin: 0;
            color: var(--resume-text);
            /* Helvetica is a built-in PDF font (narrow, so more fits on a page) and covers ñ / Ñ and the other accented
               letters of local names. The browser preview falls back to Arial, which looks the same. */
            font-family: Helvetica, Arial, sans-serif;
            font-size: {{ $size }};
            line-height: {{ $compact ? '1.28' : '1.38' }};
        }

        .head { width: 100%; border-collapse: collapse; margin: 0 0 {{ $compact ? '2mm' : '3mm' }}; }
        .head td { vertical-align: top; padding: 0; }
        .head-photo { width: 24mm; text-align: right; }
        .head-photo img { width: 22mm; height: 22mm; }
        .name { margin: 0; color: var(--resume-accent); font-size: {{ $compact ? '18pt' : '20pt' }}; font-weight: bold; line-height: 1.15; word-wrap: break-word; }
        .headline { margin: 1mm 0 0; font-size: {{ $compact ? '9pt' : '9.8pt' }}; color: var(--resume-muted); }
        .contact { margin: 1.2mm 0 0; font-size: {{ $compact ? '8.2pt' : '8.8pt' }}; word-wrap: break-word; }

        .sec {
            margin: {{ $compact ? '3.4mm' : '4.4mm' }} 0 {{ $compact ? '1.4mm' : '1.8mm' }};
            padding-bottom: 0.7mm;
            border-bottom: 0.35mm solid var(--resume-accent);
            color: var(--resume-accent);
            font-size: {{ $compact ? '8.8pt' : '9.4pt' }};
            font-weight: bold;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        p { margin: 0; word-wrap: break-word; }
        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 0.25mm 0; vertical-align: top; word-wrap: break-word; }
        .kv .label { width: 27%; color: var(--resume-muted); }

        .entry { margin: 0 0 {{ $compact ? '1.6mm' : '2.2mm' }}; page-break-inside: avoid; }
        .entry-title { font-weight: bold; }
        .entry-meta { color: var(--resume-muted); font-size: {{ $compact ? '7.8pt' : '8.4pt' }}; }
        .entry-note { margin-top: 0.4mm; }
        .group { margin: 0 0 0.8mm; font-weight: bold; }
        .line { margin: 0 0 0.6mm; page-break-inside: avoid; }
        .generated { margin-top: 4mm; color: var(--resume-muted); font-size: 7pt; text-align: right; }
    </style>
    @if($preview ?? false)
        {{-- Resume Editor live preview (browser, not DomPDF): browsers ignore @page margins, so the same
             margins are added here and the page is a white A4 sheet. --}}
        <style>
            html { background: #FFFFFF; }
            body { width: 210mm; min-height: 297mm; padding: 12mm 14mm; box-sizing: border-box; }
        </style>
    @endif
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <h1 class="name">{{ $content['name'] }}</h1>
                @if($headline !== '')<p class="headline">{{ $headline }}</p>@endif
                @if($contactLine !== '')<p class="contact">{{ $contactLine }}</p>@endif
            </td>
            @if($photo ?? null)
                <td class="head-photo"><img src="{{ $photo }}" alt=""></td>
            @endif
        </tr>
    </table>

    @if(filled($content['summary'] ?? null))
        <div class="sec">Career objective</div>
        <p>{{ $content['summary'] }}</p>
    @endif

    @if(! empty($content['education']))
        <div class="sec">Education</div>
        <table class="kv">
            @foreach($content['education'] as $label => $value)
                <tr><td class="label">{{ $label }}</td><td>{{ $value }}</td></tr>
            @endforeach
        </table>
    @endif

    @if($technical !== [] || $soft !== [])
        <div class="sec">Skills</div>
        @if($technical !== [])<p class="line"><strong>Technical:</strong> {{ implode(', ', $technical) }}</p>@endif
        @if($soft !== [])<p class="line"><strong>Soft skills:</strong> {{ implode(', ', $soft) }}</p>@endif
    @endif

    @if(! empty($content['projects']))
        <div class="sec">Projects</div>
        @foreach($content['projects'] as $project)
            <div class="entry">
                <p class="entry-title">{{ $project['title'] }}</p>
                @if(! empty($project['rows']))<p class="entry-meta">{{ $meta($project['rows']) }}</p>@endif
                @if(filled($project['description'] ?? null))<p class="entry-note">{{ $project['description'] }}</p>@endif
            </div>
        @endforeach
    @endif

    @if(! empty($content['experiences']))
        <div class="sec">OJT and experience</div>
        @foreach($content['experiences'] as $experience)
            <div class="entry">
                <p class="entry-title">{{ $experience['title'] }}</p>
                @if(! empty($experience['rows']))<p class="entry-meta">{{ $meta($experience['rows']) }}</p>@endif
                @if(filled($experience['description'] ?? null))<p class="entry-note">{{ $experience['description'] }}</p>@endif
            </div>
        @endforeach
    @endif

    @if(! empty($content['certificates']))
        <div class="sec">Certifications</div>
        @foreach($content['certificates'] as $certificate)
            <div class="entry">
                <p class="entry-title">{{ $certificate['title'] }}</p>
                @if(! empty($certificate['rows']))<p class="entry-meta">{{ $meta($certificate['rows']) }}</p>@endif
                @if(! empty($certificate['skills']))<p class="entry-meta">Skills: {{ implode(', ', $certificate['skills']) }}</p>@endif
            </div>
        @endforeach
    @endif

    @if(! empty($content['awards']))
        <div class="sec">Honors and awards</div>
        @foreach($content['awards'] as $group)
            @php $first = true; @endphp
            @foreach($group['awards'] as $award)
                @php
                    // An older multi-line honors paragraph is shown one plain line at a time.
                    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $award['title'])), 'strlen'));
                @endphp
                @if($first)<p class="group">{{ $group['label'] }}</p>@php $first = false; @endphp @endif
                @foreach($lines as $line)
                    <p class="line">{{ $line }}@if($loop->last && ($award['meta'] ?? '') !== '') <span class="entry-meta">({{ $award['meta'] }})</span>@endif</p>
                @endforeach
            @endforeach
        @endforeach
    @endif

    @if(! empty($content['references']))
        <div class="sec">Character references</div>
        @foreach($content['references'] as $reference)
            <p class="line"><strong>{{ $reference['title'] }}</strong>@if(! empty($reference['rows'])) <span class="entry-meta">— {{ implode(', ', array_values($reference['rows'])) }}</span>@endif</p>
        @endforeach
    @endif

    @if($generatedAt)<p class="generated">Generated {{ $generatedAt->format('M d, Y') }}</p>@endif
</body>
</html>
