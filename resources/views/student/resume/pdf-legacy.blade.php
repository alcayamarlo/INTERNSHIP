<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['personal']['name'] ?? $student->user->name }}</title>
    <style>
        @page { margin: 36px 40px; }

        body {
            margin: 0;
            color: #1f1f1f;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.6;
        }

        .resume-header {
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #1f1f1f;
            margin-bottom: 14px;
        }

        .resume-name {
            margin: 0;
            font-size: 26px;
            line-height: 1.15;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .resume-role {
            margin: 4px 0 12px;
            font-size: 12px;
            color: #333333;
        }

        .resume-contact-row {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            color: #444444;
        }

        .resume-contact-row td {
            width: 33.33%;
            padding: 0 4px;
            vertical-align: top;
        }

        .resume-section {
            margin: 0 0 12px;
            padding-bottom: 10px;
            border-bottom: 1px solid #555555;
        }

        .resume-section.is-last {
            border-bottom: none;
        }

        .resume-section h2 {
            margin: 0 0 8px;
            font-size: 12px;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .resume-summary {
            margin: 0;
            color: #333333;
        }

        .resume-item {
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .resume-item-meta {
            display: block;
            color: #666666;
            font-size: 9px;
        }

        .resume-item-title {
            display: block;
            font-weight: 700;
            font-size: 10px;
        }

        .resume-item p {
            margin: 0;
            color: #333333;
        }

        .resume-skill-table {
            width: 100%;
            border-collapse: collapse;
        }

        .resume-skill-table td {
            width: 33.33%;
            padding: 0 6px 3px 0;
            vertical-align: top;
        }
    </style>
</head>
<body>
    @php
        $studentName = $content['personal']['name'] ?? $student->user->name;
        $personal = $content['personal'] ?? [];
        $roleLine = trim(implode(' ', array_filter([$personal['program'] ?? null, $personal['year_level'] ?? null])));
        // One Certificates section: My Certificates entries first, then older certificate files.
        $certificateItems = collect($content['competencies'] ?? [])->map(fn ($item) => [
            'title' => $item['name'],
            'issuer' => $item['issuer'] ?? null,
            'issue_date' => $item['issue_date'] ?? null,
            'details' => trim(implode(' · ', array_filter([$item['type'] ?? null, $item['level'] ?? null]))),
            'skills' => $item['skills'] ?? [],
        ])->concat(collect($content['certificates'] ?? [])->map(fn ($item) => [
            'title' => $item['title'],
            'issuer' => $item['issuer'] ?? null,
            'issue_date' => $item['issue_date'] ?? null,
            'details' => '',
            'skills' => [],
        ]))->values();
        $tagRows = ['skills' => collect($content['skills'] ?? [])->values()->chunk(3)];
    @endphp

    <header class="resume-header">
        <h1 class="resume-name">{{ $studentName }}</h1>
        @if($roleLine !== '')
            <div class="resume-role">{{ $roleLine }}</div>
        @endif

        <table class="resume-contact-row">
            <tr>
                <td style="text-align: left;">{{ $personal['phone'] ?? '' }}</td>
                <td style="text-align: center;">{{ $personal['email'] ?? '' }}</td>
                <td style="text-align: right;">{{ $personal['address'] ?? '' }}</td>
            </tr>
        </table>
    </header>

    @php
        $sections = array_values(array_filter([
            ! empty($content['objectives']) ? 'about' : null,
            ! empty($personal['institution']) || $roleLine !== '' ? 'education' : null,
            $certificateItems->isNotEmpty() ? 'certificates' : null,
            ! empty($content['portfolios']) ? 'projects' : null,
            $tagRows['skills']->isNotEmpty() ? 'skills' : null,
        ]));
        $lastSection = end($sections);
    @endphp

    @foreach($sections as $section)
        <section class="resume-section {{ $section === $lastSection ? 'is-last' : '' }}">
            @if($section === 'about')
                <h2>About Me</h2>
                <p class="resume-summary">{{ $content['objectives'] }}</p>

            @elseif($section === 'education')
                <h2>Education</h2>
                <div class="resume-item">
                    @if(! empty($personal['year_level']))
                        <span class="resume-item-meta">{{ $personal['institution'] ?? 'Institution' }} | {{ $personal['year_level'] }}</span>
                    @else
                        <span class="resume-item-meta">{{ $personal['institution'] ?? 'Institution' }}</span>
                    @endif
                    @if(! empty($personal['program']))
                        <span class="resume-item-title">{{ $personal['program'] }}</span>
                    @endif
                </div>

            @elseif($section === 'certificates')
                <h2>Certificates</h2>
                @foreach($certificateItems as $certificate)
                    <div class="resume-item">
                        <span class="resume-item-meta">
                            {{ $certificate['issuer'] ?? '' }}@if(! empty($certificate['issuer']) && ! empty($certificate['issue_date'])) | @endif{{ $certificate['issue_date'] ?? '' }}
                        </span>
                        <span class="resume-item-title">{{ $certificate['title'] }}</span>
                        @if($certificate['details'] !== '')<p>{{ $certificate['details'] }}</p>@endif
                        @if($certificate['skills'])<p>Skills learned: {{ implode(', ', $certificate['skills']) }}</p>@endif
                    </div>
                @endforeach

            @elseif($section === 'projects')
                <h2>Projects</h2>
                @foreach($content['portfolios'] as $project)
                    <div class="resume-item">
                        @if(! empty($project['type']))
                            <span class="resume-item-meta">{{ $project['type'] }}</span>
                        @endif
                        <span class="resume-item-title">{{ $project['title'] }}</span>
                        @if(! empty($project['description']))
                            <p>{{ $project['description'] }}</p>
                        @endif
                    </div>
                @endforeach

            @elseif($section === 'skills')
                <h2>Skills</h2>
                <table class="resume-skill-table">
                    @foreach($tagRows[$section] as $row)
                        <tr>
                            @foreach($row as $entry)
                                <td>&bull; {{ $entry['name'] }}@if(! empty($entry['level'])) <span style="color: #666666;">({{ $entry['level'] }})</span>@endif</td>
                            @endforeach
                            @for($i = $row->count(); $i < 3; $i++)
                                <td></td>
                            @endfor
                        </tr>
                    @endforeach
                </table>
            @endif
        </section>
    @endforeach
</body>
</html>
