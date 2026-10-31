<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['name'] }}</title>
    <style>
        :root {
            --resume-sidebar: #B3001B;
            --resume-accent: #E31B2D;
            --resume-name: #B3001B;
            --resume-text: #2B2B2B;
            --resume-muted: #666666;
            --resume-rule: #DDDDDD;
            --resume-on-red: #FFFFFF;
        }

        /* The page margin is applied to the flow only; the sidebar band below bleeds past it. */
        @page { size: A4 portrait; margin: 14mm 0 16mm 0; }

        html, body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            margin: 0;
            color: var(--resume-text);
            /* DejaVu Sans ships with DomPDF and covers ñ / Ñ and other Filipino characters. */
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9pt;
            line-height: 1.4;
        }

        /* Full-height red band, repeated on every page. */
        .sidebar-band {
            position: fixed;
            top: -14mm;
            bottom: -16mm;
            left: 0;
            width: 37%;
            background: var(--resume-sidebar);
        }

        /* Sidebar content sits on page 1 over the band. */
        .sidebar {
            position: absolute;
            top: 0;
            left: 0;
            width: 37%;
            color: var(--resume-on-red);
        }
        .sidebar-inner { margin: 0 7mm; }

        .photo {
            width: 38mm;
            height: 38mm;
            margin: 0 auto 6mm;
            background: var(--resume-accent);
            border: 1mm solid var(--resume-on-red);
            text-align: center;
            overflow: hidden;
        }
        .photo img { width: 38mm; height: 38mm; }
        .photo-initials { display: block; padding-top: 11mm; font-size: 26pt; font-weight: bold; letter-spacing: 1px; }

        .side-heading {
            margin: 5mm 0 2mm;
            padding-bottom: 1mm;
            border-bottom: 0.3mm solid var(--resume-on-red);
            font-size: 9.5pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .side-row { margin: 0 0 2mm; word-wrap: break-word; }
        .side-label { display: block; font-size: 8pt; opacity: 0.85; }
        .side-list { margin: 0; padding-left: 4mm; }
        .side-list li { margin-bottom: 1mm; word-wrap: break-word; }

        .main { margin-left: 37%; padding: 0 12mm 0 8mm; }

        .name { margin: 0; color: var(--resume-name); font-size: 22pt; font-weight: bold; line-height: 1.15; word-wrap: break-word; }
        .headline { margin: 1.5mm 0 0; color: var(--resume-muted); font-size: 10pt; }
        .summary { margin: 3mm 0 0; }

        /* A header bar is wrapped with its first entry so it never ends a page alone. */
        .keep { page-break-inside: avoid; }

        .bar {
            margin: 6mm 0 2.5mm;
            padding: 1.5mm 3mm;
            background: var(--resume-accent);
            color: var(--resume-on-red);
            font-size: 10pt;
            font-weight: bold;
        }

        .entry { margin: 0 0 3mm; page-break-inside: avoid; }
        .entry-title { margin: 0; font-size: 10pt; font-weight: bold; word-wrap: break-word; }
        .entry-meta { margin: 0.5mm 0 0; color: var(--resume-muted); font-size: 9pt; word-wrap: break-word; }
        .entry-note { margin: 1mm 0 0; word-wrap: break-word; }
        .entry-skills { margin: 1mm 0 0; padding-left: 4.5mm; }
        .entry-skills li { margin-bottom: 0.5mm; }

        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 0.6mm 0; vertical-align: top; }
        .rows .label { width: 34%; color: var(--resume-muted); }
        .rows .colon { width: 3%; color: var(--resume-muted); }
        .rows .value { width: 63%; word-wrap: break-word; }

        .award-group { margin: 0 0 1mm; color: var(--resume-name); font-size: 9.5pt; font-weight: bold; }
        /* The old one-paragraph honors text, one plain line at a time. */
        .award-line { margin: 0 0 0.8mm; page-break-inside: avoid; word-wrap: break-word; }

        .certify { margin-top: 8mm; page-break-inside: avoid; }
        .certify p { margin: 0 0 12mm; }
        .signature { width: 60mm; margin-left: auto; text-align: center; }
        .signature-line { border-top: 0.3mm solid var(--resume-text); padding-top: 1mm; }
        .generated { margin-top: 5mm; color: var(--resume-muted); font-size: 8pt; }
    </style>
</head>
<body>
    @php
        $headline = collect([$content['course'] ?? null, $content['school'] ?? null])->filter()->implode(' · ');
        $generatedAt = filled($content['generated_at'] ?? null)
            ? \Illuminate\Support\Carbon::parse($content['generated_at'])->timezone('Asia/Manila')
            : null;
        // Entry sections after Certificates and Honors / awards, in resume order.
        $entrySections = [
            'projects' => 'Projects',
            'experiences' => 'Experience',
            'references' => 'Character reference',
        ];
    @endphp

    <div class="sidebar-band"></div>

    <div class="sidebar">
        <div class="sidebar-inner">
            <div class="photo">
                @if($photo)
                    <img src="{{ $photo }}" alt="">
                @elseif(filled($content['initials'] ?? null))
                    <span class="photo-initials">{{ $content['initials'] }}</span>
                @endif
            </div>

            @if(! empty($content['contact']))
                <div class="side-heading">Contact</div>
                @foreach($content['contact'] as $label => $value)
                    <p class="side-row"><span class="side-label">{{ $label }}</span>{{ $value }}</p>
                @endforeach
            @endif

            @if(! empty($content['education']))
                <div class="side-heading">Education</div>
                @foreach($content['education'] as $label => $value)
                    <p class="side-row"><span class="side-label">{{ $label }}</span>{{ $value }}</p>
                @endforeach
            @endif

            @if(! empty($content['skills']))
                <div class="side-heading">Skills</div>
                <ul class="side-list">
                    @foreach($content['skills'] as $skill)
                        <li>{{ $skill }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="main">
        <h1 class="name">{{ $content['name'] }}</h1>
        @if($headline !== '')<p class="headline">{{ $headline }}</p>@endif
        @if(filled($content['summary'] ?? null))<p class="summary">{{ $content['summary'] }}</p>@endif

        @if(! empty($content['personal']))
            <div class="keep">
                <div class="bar">Personal data</div>
                @include('student.resume.partials.pdf-rows', ['rows' => $content['personal']])
            </div>
        @endif

        @include('student.resume.partials.pdf-entries', ['items' => $content['certificates'] ?? [], 'heading' => 'Certificates'])

        @foreach($content['awards'] ?? [] as $groupIndex => $group)
            @foreach($group['awards'] as $awardIndex => $award)
                @php
                    // A multi-line title is the old honors paragraph: shown as plain lines that may flow
                    // across pages, instead of one block that cannot split.
                    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $award['title'])), 'strlen'));
                    $isParagraph = count($lines) > 1;
                @endphp
                @if($groupIndex === 0 && $awardIndex === 0)<div class="keep"><div class="bar">Honors / awards</div>@endif
                @if($isParagraph)
                    @if($awardIndex === 0)<p class="award-group">{{ $group['label'] }}</p>@endif
                    <p class="award-line">{{ $lines[0] }}</p>
                    @if($groupIndex === 0 && $awardIndex === 0)</div>@endif
                    @foreach(array_slice($lines, 1) as $line)
                        <p class="award-line">{{ $line }}</p>
                    @endforeach
                    @if(($award['meta'] ?? '') !== '')<p class="entry-meta">{{ $award['meta'] }}</p>@endif
                @else
                    <div class="entry">
                        @if($awardIndex === 0)<p class="award-group">{{ $group['label'] }}</p>@endif
                        <p class="entry-title">{{ $award['title'] }}</p>
                        @if(($award['meta'] ?? '') !== '')<p class="entry-meta">{{ $award['meta'] }}</p>@endif
                    </div>
                    @if($groupIndex === 0 && $awardIndex === 0)</div>@endif
                @endif
            @endforeach
        @endforeach

        @foreach($entrySections as $key => $heading)
            @include('student.resume.partials.pdf-entries', ['items' => $content[$key] ?? [], 'heading' => $heading])
        @endforeach

        <div class="certify">
            <p>I hereby certify that the above information is true and correct to the best of my knowledge.</p>
            <div class="signature">
                <div class="signature-line">Applicant's signature</div>
            </div>
            @if($generatedAt)
                <p class="generated">Generated {{ $generatedAt->format('M d, Y h:i A') }} (Philippine time)</p>
            @endif
        </div>
    </div>
</body>
</html>
