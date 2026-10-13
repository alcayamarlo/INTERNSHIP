<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resume — {{ $content['personal']['name'] ?? $student->user->name }}</title>
    <style>
        :root {
            --resume-page: #f8fafc;
            --resume-surface: #ffffff;
            --resume-border: #dfe7ee;
            --resume-text: #0f172a;
            --resume-soft: #475569;
            --resume-muted: #64748b;
            --resume-accent: #0f172a;
            --resume-accent-soft: #e2e8f0;
            --resume-highlight: #2563eb;
        }

        body {
            margin: 0;
            background: var(--resume-page);
            color: var(--resume-text);
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.55;
        }

        .resume-page {
            width: 100%;
            max-width: 860px;
            margin: 0 auto;
            background: var(--resume-surface);
            border: 1px solid var(--resume-border);
            box-sizing: border-box;
            padding: 28px 30px 24px;
        }

        .resume-header {
            display: block;
            border-bottom: 2px solid var(--resume-accent-soft);
            padding-bottom: 16px;
            margin-bottom: 18px;
            position: relative;
        }

        .resume-header-row {
            display: table;
            width: 100%;
        }

        .resume-header-copy {
            display: table-cell;
            vertical-align: top;
            width: 75%;
        }

        .resume-header-meta {
            display: table-cell;
            vertical-align: top;
            width: 25%;
            text-align: right;
        }

        .resume-name {
            margin: 0;
            font-size: 28px;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: var(--resume-accent);
            font-weight: 700;
        }

        .resume-role {
            margin: 6px 0 10px;
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--resume-highlight);
            font-weight: 700;
        }

        .resume-contact {
            margin: 0;
            color: var(--resume-soft);
            font-size: 10.5px;
        }

        .resume-contact + .resume-contact {
            margin-top: 2px;
        }

        .resume-photo {
            width: 78px;
            height: 78px;
            display: inline-block;
            border: 1px solid var(--resume-border);
            border-radius: 12px;
            overflow: hidden;
            background: #e2e8f0;
            text-align: center;
            line-height: 78px;
            color: var(--resume-muted);
            font-size: 22px;
            font-weight: 700;
        }

        .resume-photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .resume-grid {
            display: block;
        }

        .resume-main {
            width: 100%;
        }

        .resume-section {
            margin-bottom: 14px;
            page-break-inside: avoid;
        }

        .resume-section h2 {
            margin: 0 0 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid var(--resume-border);
            color: var(--resume-accent);
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .resume-summary {
            margin: 0;
            color: var(--resume-soft);
        }

        .resume-item {
            margin-bottom: 8px;
        }

        .resume-item-header {
            display: block;
            margin-bottom: 2px;
        }

        .resume-item-title {
            color: var(--resume-text);
            font-weight: 700;
            font-size: 12px;
        }

        .resume-item-meta {
            color: var(--resume-muted);
            font-size: 10px;
        }

        .resume-item p,
        .resume-item ul {
            margin: 0;
            color: var(--resume-soft);
        }

        .resume-item ul {
            padding-left: 16px;
            margin-top: 3px;
        }

        .resume-skill-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .resume-skill-list li {
            display: inline-block;
            margin: 0 6px 6px 0;
            padding: 5px 8px;
            background: var(--resume-accent-soft);
            border: 1px solid var(--resume-border);
            border-radius: 999px;
            color: var(--resume-text);
            font-size: 10px;
            font-weight: 600;
        }

        .resume-skill-list li span {
            color: var(--resume-muted);
            font-weight: 400;
        }

        .resume-spec {
            color: var(--resume-muted);
            font-size: 10px;
        }

        @media print {
            body {
                background: white;
            }

            .resume-page {
                border: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    @php
        $studentName = $content['personal']['name'] ?? $student->user->name;
        $initials = collect(preg_split('/\s+/', trim($studentName) ?: 'Student'))->take(2)->map(fn ($segment) => mb_substr($segment, 0, 1))->implode('') ?: 'ST';
        $photoUrl = null;
        if (! empty($student->profile_picture)) {
            $publicPath = \App\Support\PublicFileUrl::relative($student->profile_picture);
            $photoUrl = $publicPath ? url($publicPath) : null;
        }
        $educationLine = trim(implode(' — ', array_filter([
            $content['personal']['institution'] ?? null,
            $content['personal']['program'] ?? null,
            $content['personal']['year_level'] ?? null,
        ], fn ($value) => $value !== null && $value !== '')));
    @endphp

    <div class="resume-page">
        <header class="resume-header">
            <div class="resume-header-row">
                <div class="resume-header-copy">
                    <h1 class="resume-name">{{ $studentName }}</h1>
                    @if(! empty($content['personal']['program']) || ! empty($content['personal']['year_level']))
                        <div class="resume-role">
                            {{ trim(implode(' ', array_filter([$content['personal']['program'] ?? null, $content['personal']['year_level'] ?? null]))) }}
                        </div>
                    @endif

                    @if(! empty($content['personal']['email']) || ! empty($content['personal']['phone']) || ! empty($content['personal']['address']))
                        @if(! empty($content['personal']['email']))
                            <p class="resume-contact">{{ $content['personal']['email'] }}</p>
                        @endif
                        @if(! empty($content['personal']['phone']))
                            <p class="resume-contact">{{ $content['personal']['phone'] }}</p>
                        @endif
                        @if(! empty($content['personal']['address']))
                            <p class="resume-contact">{{ $content['personal']['address'] }}</p>
                        @endif
                    @endif
                </div>

                <div class="resume-header-meta">
                    @if($photoUrl)
                        <div class="resume-photo"><img src="{{ $photoUrl }}" alt="{{ $studentName }} profile photo"></div>
                    @else
                        <div class="resume-photo" aria-label="Profile placeholder">{{ $initials }}</div>
                    @endif
                </div>
            </div>
        </header>

        <div class="resume-grid">
            <main class="resume-main">
                @if(! empty($content['objectives']))
                    <section class="resume-section">
                        <h2>Profile</h2>
                        <p class="resume-summary">{{ $content['objectives'] }}</p>
                    </section>
                @endif

                @if(! empty($educationLine))
                    <section class="resume-section">
                        <h2>Education</h2>
                        <div class="resume-item">
                            <div class="resume-item-header">
                                <span class="resume-item-title">{{ $content['personal']['institution'] ?? 'Institution' }}</span>
                            </div>
                            @if(! empty($content['personal']['program']) || ! empty($content['personal']['year_level']))
                                <p class="resume-spec">{{ trim(implode(' • ', array_filter([$content['personal']['program'] ?? null, $content['personal']['year_level'] ?? null]))) }}</p>
                            @endif
                        </div>
                    </section>
                @endif

                @if(! empty($content['competencies']))
                    <section class="resume-section">
                        <h2>Skills</h2>
                        <ul class="resume-skill-list">
                            @foreach($content['competencies'] as $competency)
                                <li>
                                    {{ $competency['name'] }}
                                    @if(! empty($competency['level']))
                                        <span> · {{ $competency['level'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if(! empty($content['certificates']))
                    <section class="resume-section">
                        <h2>Certifications</h2>
                        @foreach($content['certificates'] as $certificate)
                            <div class="resume-item">
                                <div class="resume-item-header">
                                    <span class="resume-item-title">{{ $certificate['title'] }}</span>
                                    @if(! empty($certificate['issue_date']))
                                        <span class="resume-item-meta"> · {{ $certificate['issue_date'] }}</span>
                                    @endif
                                </div>
                                @if(! empty($certificate['issuer']))
                                    <p class="resume-spec">{{ $certificate['issuer'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif

                @if(! empty($content['portfolios']))
                    <section class="resume-section">
                        <h2>Projects</h2>
                        @foreach($content['portfolios'] as $project)
                            <div class="resume-item">
                                <div class="resume-item-header">
                                    <span class="resume-item-title">{{ $project['title'] }}</span>
                                    @if(! empty($project['type']))
                                        <span class="resume-item-meta"> · {{ $project['type'] }}</span>
                                    @endif
                                </div>
                                @if(! empty($project['description']))
                                    <p>{{ $project['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif
            </main>
        </div>
    </div>
</body>
</html>
