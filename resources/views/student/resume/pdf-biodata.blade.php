<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['name'] }}</title>
    <style>
        @page { size: A4 portrait; margin: 32px 36px 36px; }

        body {
            margin: 0;
            color: #111111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9.5px;
            line-height: 1.45;
        }

        .top { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .top td { vertical-align: top; padding: 0; }
        .doc-title { margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.3em; }
        .doc-name { margin: 6px 0 0; font-size: 14px; font-weight: 700; text-transform: uppercase; }
        .doc-sub { margin: 2px 0 0; color: #444444; }
        .photo-cell { width: 116px; text-align: right; }
        .photo {
            display: inline-block;
            width: 108px;
            height: 108px;
            border: 1px solid #111111;
            overflow: hidden;
            text-align: center;
        }
        .photo img { width: 108px; height: 108px; }

        /* Keeps a header bar on the same page as its first box. */
        .keep { page-break-inside: avoid; }

        .bar {
            margin: 10px 0 0;
            padding: 4px 10px;
            background: #111111;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            border-radius: 6px 6px 0 0;
        }

        .box {
            border: 1px solid #111111;
            border-top: none;
            padding: 6px 10px;
        }
        .box.is-next { border-top: 1px solid #111111; }
        .box.is-last { border-radius: 0 0 6px 6px; }

        .rows { width: 100%; border-collapse: collapse; }
        .rows td { padding: 1.5px 0; vertical-align: top; }
        .rows .label { width: 128px; color: #333333; }
        .rows .colon { width: 10px; }
        .rows .value { word-wrap: break-word; }

        .item-title { margin: 0 0 2px; font-size: 10px; font-weight: 700; }
        .item-note { margin: 3px 0 0; }
        .item-skills { margin: 3px 0 0; }
        .item-skills strong { font-weight: 700; }

        .inline-list { margin: 0; }

        /* One award per row; a row never splits across pages. */
        .award-row { page-break-inside: avoid; }
        .award-title { display: block; }
        .award-meta { display: block; font-size: 8.5px; color: #444444; }

        .certify { margin-top: 18px; page-break-inside: avoid; }
        .certify p { margin: 0 0 30px; }
        .signature { width: 220px; margin-left: auto; text-align: center; }
        .signature-line { border-top: 1px solid #111111; padding-top: 3px; }
    </style>
</head>
<body>
    @php
        $subtitle = collect([$content['education']['Course'] ?? null, $content['education']['Year level'] ?? null])->filter()->implode(' · ');
        // label : value sections, then sections made of separate items, in resume order.
        $itemSections = [
            'certificates' => 'CERTIFICATES AND TRAINING',
            'projects' => 'PROJECTS / PORTFOLIO',
            'experiences' => 'EXPERIENCE',
            'references' => 'CHARACTER REFERENCE',
        ];
    @endphp

    <table class="top">
        <tr>
            <td>
                <h1 class="doc-title">RESUME</h1>
                <p class="doc-name">{{ $content['name'] }}</p>
                @if($subtitle !== '')<p class="doc-sub">{{ $subtitle }}</p>@endif
            </td>
            <td class="photo-cell">
                <div class="photo">@if($photo)<img src="{{ $photo }}" alt="">@endif</div>
            </td>
        </tr>
    </table>

    @foreach(['personal' => 'PERSONAL DATA', 'education' => 'EDUCATIONAL BACKGROUND'] as $key => $heading)
        @if(! empty($content[$key]))
            <div class="keep">
                <div class="bar">{{ $heading }}</div>
                <div class="box is-last">
                    @include('student.resume.partials.pdf-rows', ['rows' => $content[$key]])
                </div>
            </div>
        @endif
    @endforeach

    {{-- Grouped by level (College first); only levels with awards are listed. --}}
    @foreach($content['awards'] ?? [] as $index => $group)
        @if($index === 0)<div class="keep"><div class="bar">HONORS / AWARDS</div>@endif
        <div class="box {{ $index > 0 ? 'is-next' : '' }} {{ $loop->last ? 'is-last' : '' }}">
            <table class="rows">
                @foreach($group['awards'] as $award)
                    <tr class="award-row">
                        <td class="label">{{ $loop->first ? $group['label'] : '' }}</td>
                        <td class="colon">{{ $loop->first ? ':' : '' }}</td>
                        <td class="value">
                            <span class="award-title">{!! nl2br(e($award['title'])) !!}</span>
                            @if($award['meta'] !== '')<span class="award-meta">{{ $award['meta'] }}</span>@endif
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>
        @if($index === 0)</div>@endif
    @endforeach

    @if(! empty($content['skills']))
        <div class="keep">
            <div class="bar">SKILLS</div>
            <div class="box is-last"><p class="inline-list">{{ implode(' • ', $content['skills']) }}</p></div>
        </div>
    @endif

    @foreach($itemSections as $key => $heading)
        @php($items = $content[$key] ?? [])
        @foreach($items as $index => $item)
            @if($index === 0)<div class="keep"><div class="bar">{{ $heading }}</div>@endif
            <div class="box keep {{ $index > 0 ? 'is-next' : '' }} {{ $loop->last ? 'is-last' : '' }}">
                <p class="item-title">{{ $item['title'] }}</p>
                @if(! empty($item['rows']))
                    @include('student.resume.partials.pdf-rows', ['rows' => $item['rows']])
                @endif
                @if(! empty($item['skills']))
                    <p class="item-skills"><strong>Skills learned:</strong> {{ implode(' • ', $item['skills']) }}</p>
                @endif
                @if(! empty($item['description']))
                    <p class="item-note">{{ $item['description'] }}</p>
                @endif
            </div>
            @if($index === 0)</div>@endif
        @endforeach
    @endforeach

    <div class="certify">
        <p>I hereby certify that the above information is true and correct to the best of my knowledge.</p>
        <div class="signature">
            <div class="signature-line">Applicant's Signature</div>
        </div>
    </div>
</body>
</html>
