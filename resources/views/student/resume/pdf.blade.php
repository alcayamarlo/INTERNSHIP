<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['name'] }}</title>
    @php
        // The school's Appendix H curriculum vitae: a left column (photo, About Me, contact, skills) and a right
        // column (name, title, education, soft skills, then the rest), in burgundy and white.
        // "compact" is the tighter spacing the builder falls back to when the normal one runs over a page.
        $compact = ($density ?? 'normal') === 'compact';
        $margin = $compact ? 8 : 10; // mm
        $size = $compact ? '8.9pt' : '9.6pt';
        $sideSize = $compact ? '8.5pt' : '9pt';
        $last = trim((string) ($content['name_last'] ?? ''));
        $given = trim((string) ($content['name_given'] ?? ''));
        if ($last === '' && $given === '') {
            $given = (string) ($content['name'] ?? '');
        }
        $contact = $content['contact'] ?? [];
        $technical = $content['skills']['technical'] ?? [];
        $soft = $content['skills']['soft'] ?? [];
        $education = is_array($content['education'] ?? null) && array_is_list($content['education']) ? $content['education'] : [];
        $generatedAt = filled($content['generated_at'] ?? null)
            ? \Illuminate\Support\Carbon::parse($content['generated_at'])->timezone('Asia/Manila')
            : null;
        // Entry rows ("Issuer: DICT") on one muted line.
        $meta = fn (array $rows): string => collect($rows)->map(fn ($value, $label) => $label.': '.$value)->implode('  ·  ');
        // Small burgundy icons for Contact Me (SVG, which the PDF renderer supports; icon fonts it does not).
        $icon = fn (string $paths): string => 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" width="16" height="16">'.$paths.'</svg>');
        $icons = [
            'Mobile' => $icon('<path fill="#5E010B" d="M3.6 1.2l2.2 2.2c.4.4.4 1 0 1.4l-.9.9a9.6 9.6 0 0 0 5.4 5.4l.9-.9c.4-.4 1-.4 1.4 0l2.2 2.2c.4.4.4 1 0 1.4l-1.3 1.3c-.6.6-1.6.8-2.4.4A16 16 0 0 1 1.5 5.9c-.4-.8-.2-1.8.4-2.4L3.2 1.2c.1-.1.3-.1.4 0z"/>'),
            'Email' => $icon('<path fill="#5E010B" d="M1 3h14v10H1z"/><path fill="none" stroke="#FFFFFF" stroke-width="1.3" d="M1.6 3.6L8 9l6.4-5.4"/>'),
            'City' => $icon('<path fill="#5E010B" d="M8 0C5 0 2.5 2.5 2.5 5.5 2.5 9.6 8 16 8 16s5.5-6.4 5.5-10.5C13.5 2.5 11 0 8 0zm0 7.8a2.3 2.3 0 1 1 0-4.6 2.3 2.3 0 0 1 0 4.6z"/>'),
        ];
    @endphp
    <style>
        :root { --resume-accent: #5E010B; --resume-on-accent: #FFFFFF; --resume-text: #2B2B2B; --resume-muted: #555555; }

        @page { size: A4 portrait; margin: {{ $margin }}mm; }

        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            margin: 0;
            color: var(--resume-text);
            /* Helvetica is a built-in PDF font (narrow, so more fits on a page) and covers ñ / Ñ and the other accented
               letters of local names. The browser preview falls back to Arial, which looks the same. */
            font-family: Helvetica, Arial, sans-serif;
            font-size: {{ $size }};
            line-height: {{ $compact ? '1.24' : '1.3' }};
            background: #FFFFFF;
        }
        p { margin: 0; word-wrap: break-word; }

        /* The thin burgundy line between the columns, repeated on every page. */
        .divider { position: fixed; top: 0; bottom: 0; left: 33.4%; width: 0.4mm; background: var(--resume-accent); }

        /* Left column: page 1 only. Its content is capped (About Me, 15 skills, 3 contact lines) so it always fits. */
        .side { position: absolute; top: 0; left: 0; width: 31%; font-size: {{ $sideSize }}; }
        .main { margin-left: 36%; }

        /* Section headings: burgundy pills with white capitals, like the template. */
        .pill {
            display: block;
            margin: {{ $compact ? '2.8mm' : '3.6mm' }} 0 {{ $compact ? '1.6mm' : '2mm' }};
            padding: {{ $compact ? '1mm' : '1.2mm' }} 0;
            border-radius: 3.6mm;
            background: var(--resume-accent);
            color: var(--resume-on-accent);
            font-size: {{ $compact ? '9pt' : '9.8pt' }};
            font-weight: bold;
            letter-spacing: 1.1px;
            text-align: center;
            text-transform: uppercase;
            page-break-after: avoid;
        }
        .side .pill { width: 86%; margin-left: auto; margin-right: auto; }
        .main .pill { width: 52%; }

        .photo { text-align: center; margin: 0 0 {{ $compact ? '1mm' : '2mm' }}; }
        .photo img { width: {{ $compact ? '30mm' : '34mm' }}; height: {{ $compact ? '30mm' : '34mm' }}; }
        .initials {
            /* Padding instead of line-height: the PDF renderer places a tall line box lower than browsers do. */
            width: 30mm; height: 20mm; padding-top: 10mm; margin: 0 auto;
            border: 0.8mm solid var(--resume-accent); border-radius: 50%;
            color: var(--resume-accent); font-size: 24pt; font-weight: bold; line-height: 1; text-align: center;
        }
        .about { text-align: justify; }

        .contact { width: 100%; border-collapse: collapse; }
        .contact td { padding: 0.8mm 0; vertical-align: middle; word-wrap: break-word; }
        .contact .icon { width: 6mm; }
        .contact .icon img { width: 3.6mm; height: 3.6mm; }

        ul.bullets { margin: 0; padding-left: 4.6mm; }
        ul.bullets li { margin: 0 0 {{ $compact ? '0.5mm' : '0.9mm' }}; }

        .surname { margin: 0; color: var(--resume-accent); font-size: {{ $compact ? '25pt' : '28pt' }}; font-weight: bold; line-height: 1.05; word-wrap: break-word; }
        .given { margin: 0.6mm 0 0; color: var(--resume-accent); font-size: {{ $compact ? '18pt' : '21pt' }}; line-height: 1.1; word-wrap: break-word; }
        .title { margin: 1.6mm 0 0; color: var(--resume-muted); font-size: {{ $compact ? '9.6pt' : '10.4pt' }}; letter-spacing: 1.4px; }

        .edu { margin: 0 0 {{ $compact ? '1.6mm' : '2.2mm' }}; page-break-inside: avoid; }
        .edu-level { font-weight: bold; font-size: {{ $compact ? '9.6pt' : '10.4pt' }}; }
        .edu-years { color: var(--resume-muted); }
        .edu-school { font-weight: bold; }

        .entry { margin: 0 0 {{ $compact ? '1.4mm' : '1.8mm' }}; page-break-inside: avoid; }
        .entry-title { font-weight: bold; color: var(--resume-accent); }
        .entry-meta { color: var(--resume-muted); font-size: {{ $compact ? '8.2pt' : '8.6pt' }}; }
        .entry-note { margin-top: 0.4mm; }
        .group { margin: 0 0 0.8mm; font-weight: bold; color: var(--resume-accent); }
        .line { margin: 0 0 0.8mm; page-break-inside: avoid; }
        .generated { margin-top: 4mm; color: var(--resume-muted); font-size: 7.5pt; text-align: right; }
    </style>
    @if($preview ?? false)
        {{-- Resume Editor live preview (browser, not DomPDF): browsers ignore @page margins, so the page is a white
             A4 sheet with the same margins, and the left column and the divider are placed inside them. --}}
        <style>
            html { background: #FFFFFF; }
            body { width: 210mm; min-height: 297mm; padding: {{ $margin }}mm; box-sizing: border-box; position: relative; }
            .divider { position: absolute; top: {{ $margin }}mm; bottom: {{ $margin }}mm; left: calc({{ $margin }}mm + (210mm - {{ 2 * $margin }}mm) * 0.334); }
            .side { top: {{ $margin }}mm; left: {{ $margin }}mm; width: calc((210mm - {{ 2 * $margin }}mm) * 0.31); }
        </style>
    @endif
</head>
<body>
    <div class="divider"></div>

    <div class="side">
        <div class="photo">
            @if($photo ?? null)
                <img src="{{ $photo }}" alt="">
            @elseif(filled($content['initials'] ?? null))
                <div class="initials">{{ $content['initials'] }}</div>
            @endif
        </div>

        @if(filled($content['summary'] ?? null))
            <div class="pill">About Me</div>
            <p class="about">{{ $content['summary'] }}</p>
        @endif

        @if(! empty($contact))
            <div class="pill">Contact Me</div>
            <table class="contact">
                @foreach($contact as $label => $value)
                    <tr>
                        <td class="icon">@isset($icons[$label])<img src="{{ $icons[$label] }}" alt="">@endisset</td>
                        <td>{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if($technical !== [])
            <div class="pill">Skills</div>
            <ul class="bullets">
                @foreach($technical as $skill)
                    <li>{{ $skill }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <div class="main">
        @if($last !== '')
            <h1 class="surname">{{ $last }}</h1>
            @if($given !== '')<p class="given">{{ $given }}</p>@endif
        @else
            <h1 class="surname">{{ $given }}</h1>
        @endif
        @if(filled($content['title'] ?? null))<p class="title">{{ $content['title'] }}</p>@endif

        @if($education !== [])
            <div class="pill">Education</div>
            @foreach($education as $entry)
                <div class="edu">
                    <p class="edu-level">{{ $entry['level'] ?? '' }}</p>
                    @if(filled($entry['years'] ?? null))<p class="edu-years">{{ $entry['years'] }}</p>@endif
                    @if(filled($entry['school'] ?? null))<p class="edu-school">{{ $entry['school'] }}</p>@endif
                    @if(filled($entry['detail'] ?? null))<p>{{ $entry['detail'] }}</p>@endif
                </div>
            @endforeach
        @endif

        @if($soft !== [])
            <div class="pill">Soft Skills</div>
            <ul class="bullets">
                @foreach($soft as $skill)
                    <li>{{ $skill }}</li>
                @endforeach
            </ul>
        @endif

        @if(! empty($content['projects']))
            <div class="pill">Projects</div>
            @foreach($content['projects'] as $project)
                <div class="entry">
                    <p class="entry-title">{{ $project['title'] }}</p>
                    @if(! empty($project['rows']))<p class="entry-meta">{{ $meta($project['rows']) }}</p>@endif
                    @if(filled($project['description'] ?? null))<p class="entry-note">{{ $project['description'] }}</p>@endif
                </div>
            @endforeach
        @endif

        @if(! empty($content['experiences']))
            <div class="pill">OJT and Experience</div>
            @foreach($content['experiences'] as $experience)
                <div class="entry">
                    <p class="entry-title">{{ $experience['title'] }}</p>
                    @if(! empty($experience['rows']))<p class="entry-meta">{{ $meta($experience['rows']) }}</p>@endif
                    @if(filled($experience['description'] ?? null))<p class="entry-note">{{ $experience['description'] }}</p>@endif
                </div>
            @endforeach
        @endif

        @if(! empty($content['certificates']))
            <div class="pill">Certifications</div>
            @foreach($content['certificates'] as $certificate)
                <div class="entry">
                    <p class="entry-title">{{ $certificate['title'] }}</p>
                    @if(! empty($certificate['rows']))<p class="entry-meta">{{ $meta($certificate['rows']) }}</p>@endif
                    @if(! empty($certificate['skills']))<p class="entry-meta">Skills: {{ implode(', ', $certificate['skills']) }}</p>@endif
                </div>
            @endforeach
        @endif

        @if(! empty($content['awards']))
            <div class="pill">Honors and Awards</div>
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
            <div class="pill">Character References</div>
            @foreach($content['references'] as $reference)
                <p class="line"><strong>{{ $reference['title'] }}</strong>@if(! empty($reference['rows'])) <span class="entry-meta">— {{ implode(', ', array_values($reference['rows'])) }}</span>@endif</p>
            @endforeach
        @endif

        @if($generatedAt)<p class="generated">Generated {{ $generatedAt->format('M d, Y') }}</p>@endif
    </div>
</body>
</html>
