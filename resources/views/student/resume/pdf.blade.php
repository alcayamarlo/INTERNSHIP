<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['name'] }}</title>
    @php
        // Burgundy left column (photo, contact, education, skills) and a white right column (name, about me,
        // experience, projects, certifications, awards, references). "compact" is the tighter spacing the builder
        // falls back to when the normal one runs over a page.
        $compact = ($density ?? 'normal') === 'compact';
        $edge = $compact ? 10 : 12; // top and bottom page margin (mm); the left column runs from edge to edge
        $headline = collect([$content['course'] ?? null, $content['school'] ?? null])->filter()->implode(' · ');
        $contact = array_filter($content['contact'] ?? [], 'filled');
        $education = is_array($content['education'] ?? null) ? array_filter($content['education'], 'filled') : [];
        // One Skills list, as in the template (a version 4 resume kept Technical and Soft apart).
        $skills = \App\Services\ResumeContent::skillList($content['skills'] ?? []);
        $showPhotoBox = (bool) ($content['show_photo'] ?? true);

        // No 2x2 yet: a placeholder in the template's colours (the student can turn the photo box off in the editor).
        $placeholder = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">'
            .'<rect width="100" height="100" fill="#7A1F2B"/><circle cx="50" cy="38" r="16" fill="#D9A3AC"/>'
            .'<path d="M20 100 C20 74 33 62 50 62 C67 62 80 74 80 100 Z" fill="#D9A3AC"/></svg>');
        $sealFile = public_path('images/brand/ojt-seal-print.jpg');
        $seal = is_file($sealFile) ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($sealFile)) : null;

        /*
         * The left column is only on page 1, so it must never run past the bottom of the page (the PDF renderer
         * would cut it off). Its height is estimated on the safe side; when it would not fit, the skills, and if
         * needed the education, move to the right column, which flows onto a second page.
         */
        $perLine = 23; // characters of a bold 10pt line in the left column
        $valueHeight = fn ($text): float => 4.6 * max(1, (int) ceil(mb_strlen((string) $text) / $perLine));
        $itemsHeight = fn (array $items): float => array_sum(array_map(fn ($value) => 3.9 + $valueHeight($value) + 2.6, $items));
        $listHeight = fn (array $items): float => array_sum(array_map(fn ($value) => 4.6 * max(1, (int) ceil(mb_strlen((string) $value) / ($perLine - 2))) + 1, $items));
        $section = 15.0;
        $budget = 297 - 2 * $edge - 8;
        $used = ($showPhotoBox ? 46 : 0) + ($contact ? $section + $itemsHeight($contact) : 0);
        $educationHeight = $education ? $section + $itemsHeight($education) : 0;
        $educationInSide = $used + $educationHeight <= $budget;
        $used += $educationInSide ? $educationHeight : 0;
        $skillsHeight = $skills ? $section + $listHeight($skills) : 0;
        $skillsInSide = $used + $skillsHeight <= $budget;

        // A contact value too long for the left column: an email breaks after its "@"; anything else wraps where it
        // has to (word-wrap: break-word). Helvetica has no zero-width space, so no invisible break marks are used.
        $contactValue = fn ($text): string => mb_strlen((string) $text) > $perLine && str_contains((string) $text, '@')
            ? e(\Illuminate\Support\Str::before($text, '@')).'@<br>'.e(\Illuminate\Support\Str::after($text, '@'))
            : e((string) $text);
        // An entry's rows split into its date ("2022 – Present"), its organization, and the rest.
        $splitRows = function (array $rows): array {
            $date = $organization = null;
            $others = [];
            foreach ($rows as $label => $value) {
                if ($date === null && preg_match('/period|date|year|when/i', (string) $label)) {
                    $date = $value;
                } elseif ($organization === null && preg_match('/organi[sz]ation|company|employer|agency/i', (string) $label)) {
                    $organization = $value;
                } else {
                    $others[$label] = $value;
                }
            }

            return [$date, $organization, $others];
        };
        $meta = fn (array $rows): string => collect($rows)->map(fn ($value, $label) => $label.': '.$value)->implode('  ·  ');
        // A description written on several lines (or with "-" / "•") becomes bullet points.
        $lines = fn (?string $text): array => array_values(array_filter(array_map(
            fn ($line) => trim((string) preg_replace('/^\s*[-•*]\s*/u', '', $line)),
            preg_split('/\R/u', (string) $text)
        ), 'strlen'));
    @endphp
    <style>
        :root { --resume-accent: #5E010B; --resume-accent-soft: #F7EDEF; --resume-on-accent: #FFFFFF; --resume-on-accent-muted: #EED3D8; --resume-text: #222222; --resume-muted: #555555; }

        /* Only top and bottom margins: the burgundy column runs to the left edge of the page. */
        @page { size: A4 portrait; margin: {{ $edge }}mm 0; }

        html, body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

        body {
            margin: 0;
            color: var(--resume-text);
            /* Helvetica is a built-in PDF font (narrow, so more fits on a page) and covers ñ / Ñ and the other accented
               letters of local names. The browser preview falls back to Arial, which looks the same. */
            font-family: Helvetica, Arial, sans-serif;
            font-size: {{ $compact ? '9pt' : '9.8pt' }};
            line-height: {{ $compact ? '1.3' : '1.4' }};
        }
        p { margin: 0; word-wrap: break-word; }

        /* The burgundy left column, on every page (it runs into the top and bottom margins). */
        .band { position: fixed; top: -{{ $edge }}mm; bottom: -{{ $edge }}mm; left: 0; width: 33%; background: var(--resume-accent); }
        .side { position: absolute; top: 0; left: 0; width: 33%; color: var(--resume-on-accent); }
        .side-inner { margin: 0 8mm; }
        .main { margin-left: 33%; padding: 0 9mm 0 8mm; }

        .photo { text-align: center; margin: 0 0 {{ $compact ? '1mm' : '2mm' }}; }
        .photo img { width: {{ $compact ? '32mm' : '36mm' }}; height: {{ $compact ? '32mm' : '36mm' }}; border: 0.9mm solid #FFFFFF; }

        .side-heading {
            margin: {{ $compact ? '4mm' : '6mm' }} 0 {{ $compact ? '2mm' : '3mm' }};
            padding-bottom: 1.4mm;
            border-bottom: 0.3mm solid var(--resume-on-accent-muted);
            font-size: {{ $compact ? '9.4pt' : '10pt' }};
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .side-heading.first { margin-top: 0; }
        .side-item { margin: 0 0 {{ $compact ? '1.8mm' : '2.6mm' }}; }
        .side-label { display: block; color: var(--resume-on-accent-muted); font-size: {{ $compact ? '8pt' : '8.4pt' }}; }
        .side-value { display: block; font-size: {{ $compact ? '9.2pt' : '10pt' }}; font-weight: bold; word-wrap: break-word; }
        .side ul { margin: 0 0 {{ $compact ? '1.6mm' : '2.4mm' }}; padding-left: 4.4mm; }
        .side li { margin: 0 0 {{ $compact ? '0.6mm' : '1mm' }}; font-size: {{ $compact ? '9.2pt' : '10pt' }}; font-weight: bold; }

        .head { width: 100%; border-collapse: collapse; }
        .head td { padding: 0; vertical-align: top; }
        .name { margin: 0; color: var(--resume-accent); font-size: {{ $compact ? '24pt' : '28pt' }}; font-weight: bold; line-height: 1.08; word-wrap: break-word; }
        .logo { width: 21mm; text-align: right; }
        .logo img { width: {{ $compact ? '15mm' : '17mm' }}; height: {{ $compact ? '15mm' : '17mm' }}; }
        .headline { margin: {{ $compact ? '3mm' : '4.5mm' }} 0 0; color: var(--resume-muted); font-size: {{ $compact ? '8.6pt' : '9.2pt' }}; }

        /* Section headings: burgundy capitals over a burgundy line. */
        .sec {
            margin: {{ $compact ? '4mm' : '5.5mm' }} 0 {{ $compact ? '2mm' : '2.8mm' }};
            padding-bottom: 1.3mm;
            border-bottom: 0.6mm solid var(--resume-accent);
            color: var(--resume-accent);
            font-size: {{ $compact ? '9.6pt' : '10.4pt' }};
            font-weight: bold;
            letter-spacing: 2.2px;
            text-transform: uppercase;
            page-break-after: avoid;
        }

        .entry { margin: 0 0 {{ $compact ? '2mm' : '3mm' }}; page-break-inside: avoid; }
        .entry-head { width: 100%; border-collapse: collapse; }
        .entry-head td { padding: 0; vertical-align: top; }
        .entry-title { font-size: {{ $compact ? '9.6pt' : '10.4pt' }}; font-weight: bold; word-wrap: break-word; }
        .entry-date { width: 34mm; padding-left: 3mm; color: #444444; text-align: right; }
        .meta { color: var(--resume-muted); font-size: {{ $compact ? '8.2pt' : '8.6pt' }}; word-wrap: break-word; }
        .note { margin-top: 0.6mm; }
        ul.bullets { margin: 0.6mm 0 0; padding-left: 4.6mm; }
        ul.bullets li { margin: 0 0 0.6mm; }

        /* Certifications: one tinted box each, with the skills gained in two columns. */
        .cert { margin: 0 0 {{ $compact ? '2mm' : '2.8mm' }}; padding: {{ $compact ? '2.4mm 3.4mm' : '3.2mm 4.2mm' }}; border-radius: 2mm; background: var(--resume-accent-soft); page-break-inside: avoid; }
        .cert .entry-title { color: var(--resume-accent); }
        .cert-skills { margin-top: 1.2mm; font-weight: bold; }
        .cols { width: 100%; border-collapse: collapse; }
        .cols td { width: 50%; padding: 0; vertical-align: top; }

        .kv { width: 100%; border-collapse: collapse; }
        .kv td { padding: 0.3mm 0; vertical-align: top; word-wrap: break-word; }
        .kv .label { width: 30%; color: var(--resume-muted); }
        .group { margin: 0 0 0.8mm; color: var(--resume-accent); font-weight: bold; }
        .line { margin: 0 0 0.8mm; page-break-inside: avoid; }
    </style>
    @if($preview ?? false)
        {{-- Resume Editor live preview (browser, not DomPDF): browsers ignore @page margins, so the same margins are
             added here and the page is a white A4 sheet with the burgundy column down its left side. --}}
        <style>
            html { background: #FFFFFF; }
            body { width: 210mm; min-height: 297mm; padding: {{ $edge }}mm 0; box-sizing: border-box; position: relative; background: #FFFFFF; }
            .band { position: absolute; top: 0; bottom: 0; }
            .side { top: {{ $edge }}mm; }
        </style>
    @endif
</head>
<body>
    <div class="band"></div>

    <div class="side">
        <div class="side-inner">
            @if($showPhotoBox)
                <div class="photo"><img src="{{ $photo ?? $placeholder }}" alt=""></div>
            @endif

            @if($contact)
                <div class="side-heading{{ $showPhotoBox ? '' : ' first' }}">Contact</div>
                @foreach($contact as $label => $value)
                    <div class="side-item"><span class="side-label">{{ $label }}</span><span class="side-value">{!! $contactValue($value) !!}</span></div>
                @endforeach
            @endif

            @if($education && $educationInSide)
                <div class="side-heading{{ $showPhotoBox || $contact ? '' : ' first' }}">Education</div>
                @foreach($education as $label => $value)
                    <div class="side-item"><span class="side-label">{{ $label }}</span><span class="side-value">{{ $value }}</span></div>
                @endforeach
            @endif

            @if($skills && $skillsInSide)
                <div class="side-heading">Skills</div>
                <ul>
                    @foreach($skills as $skill)<li>{{ $skill }}</li>@endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="main">
        <table class="head">
            <tr>
                <td><h1 class="name">{{ $content['name'] }}</h1></td>
                @if($seal)<td class="logo"><img src="{{ $seal }}" alt=""></td>@endif
            </tr>
        </table>
        @if($headline !== '')<p class="headline">{{ $headline }}</p>@endif

        @if(filled($content['summary'] ?? null))
            <div class="sec">About Me</div>
            <p>{{ $content['summary'] }}</p>
        @endif

        @if($education && ! $educationInSide)
            <div class="sec">Education</div>
            <table class="kv">
                @foreach($education as $label => $value)
                    <tr><td class="label">{{ $label }}</td><td>{{ $value }}</td></tr>
                @endforeach
            </table>
        @endif

        @if(! empty($content['experiences']))
            <div class="sec">Experience</div>
            @foreach($content['experiences'] as $experience)
                @php [$date, $organization, $others] = $splitRows($experience['rows'] ?? []); $bullets = $lines($experience['description'] ?? null); @endphp
                <div class="entry">
                    <table class="entry-head"><tr>
                        <td class="entry-title">{{ $experience['title'] }}@if(filled($organization)), {{ $organization }}@endif</td>
                        @if(filled($date))<td class="entry-date">{{ $date }}</td>@endif
                    </tr></table>
                    @if($others)<p class="meta">{{ $meta($others) }}</p>@endif
                    @if($bullets)
                        <ul class="bullets">
                            @foreach($bullets as $bullet)<li>{{ $bullet }}</li>@endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        @endif

        @if(! empty($content['projects']))
            <div class="sec">Projects</div>
            @foreach($content['projects'] as $project)
                @php [$date, $organization, $others] = $splitRows($project['rows'] ?? []); $bullets = $lines($project['description'] ?? null); @endphp
                <div class="entry">
                    <table class="entry-head"><tr>
                        <td class="entry-title">{{ $project['title'] }}@if(filled($organization)), {{ $organization }}@endif</td>
                        @if(filled($date))<td class="entry-date">{{ $date }}</td>@endif
                    </tr></table>
                    @if($others)<p class="meta">{{ $meta($others) }}</p>@endif
                    @if(count($bullets) > 1)
                        <ul class="bullets">
                            @foreach($bullets as $bullet)<li>{{ $bullet }}</li>@endforeach
                        </ul>
                    @elseif($bullets)
                        <p class="note">{{ $bullets[0] }}</p>
                    @endif
                </div>
            @endforeach
        @endif

        @if($skills && ! $skillsInSide)
            {{-- Too many for the left column: the same list, in two columns. --}}
            @php $halfSkills = (int) ceil(count($skills) / 2); @endphp
            <div class="sec">Skills</div>
            <table class="cols"><tr>
                <td><ul class="bullets">@foreach(array_slice($skills, 0, $halfSkills) as $skill)<li>{{ $skill }}</li>@endforeach</ul></td>
                <td>@if(count($skills) > $halfSkills)<ul class="bullets">@foreach(array_slice($skills, $halfSkills) as $skill)<li>{{ $skill }}</li>@endforeach</ul>@endif</td>
            </tr></table>
        @endif

        @if(! empty($content['certificates']))
            <div class="sec">Certifications &amp; Skills Gained</div>
            @foreach($content['certificates'] as $certificate)
                @php
                    [$date, $organization, $others] = $splitRows($certificate['rows'] ?? []);
                    if (filled($organization)) {
                        $others = ['Organization' => $organization, ...$others];
                    }
                    $gained = array_values($certificate['skills'] ?? []);
                    $half = (int) ceil(count($gained) / 2);
                @endphp
                <div class="cert">
                    <table class="entry-head"><tr>
                        <td class="entry-title">{{ $certificate['title'] }}</td>
                        @if(filled($date))<td class="entry-date">{{ $date }}</td>@endif
                    </tr></table>
                    @if($others)<p class="meta">{{ $meta($others) }}</p>@endif
                    @if($gained)
                        <p class="cert-skills">Skills</p>
                        <table class="cols"><tr>
                            <td><ul class="bullets">@foreach(array_slice($gained, 0, $half) as $skill)<li>{{ $skill }}</li>@endforeach</ul></td>
                            <td>@if(count($gained) > $half)<ul class="bullets">@foreach(array_slice($gained, $half) as $skill)<li>{{ $skill }}</li>@endforeach</ul>@endif</td>
                        </tr></table>
                    @endif
                </div>
            @endforeach
        @endif

        @if(! empty($content['awards']))
            <div class="sec">Honors &amp; Awards</div>
            @foreach($content['awards'] as $group)
                @php $first = true; @endphp
                @foreach($group['awards'] as $award)
                    @php
                        // An older multi-line honors paragraph is shown one plain line at a time.
                        $awardLines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $award['title'])), 'strlen'));
                    @endphp
                    @if($first)<p class="group">{{ $group['label'] }}</p>@php $first = false; @endphp @endif
                    @foreach($awardLines as $line)
                        <p class="line">{{ $line }}@if($loop->last && ($award['meta'] ?? '') !== '') <span class="meta">({{ $award['meta'] }})</span>@endif</p>
                    @endforeach
                @endforeach
            @endforeach
        @endif

        @if(! empty($content['references']))
            <div class="sec">Character References</div>
            @foreach($content['references'] as $reference)
                <p class="line"><strong>{{ $reference['title'] }}</strong>@if(! empty($reference['rows'])) <span class="meta">— {{ implode(', ', array_values($reference['rows'])) }}</span>@endif</p>
            @endforeach
        @endif
    </div>
</body>
</html>
