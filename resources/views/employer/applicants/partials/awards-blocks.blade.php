{{-- Honors / awards grouped by school level. Expects $awardGroups (the profile 'awards' array).
     BACKEND: structured awards would be cleaner. The award title is free text with level headings and
     "Title, Grade N, 2023" lines, so it is split here for presentation only. No text is changed. --}}
@php
    $headingPattern = '/^(senior high school|junior high school|high school|grade school|elementary|college|tertiary|secondary)\s*:?$/i';
    $levels = [];
    foreach ($awardGroups as $group) {
        $current = $group['label'];
        foreach ($group['awards'] as $award) {
            $awardCurrent = $current;
            foreach (preg_split('/\R/', (string) $award['title']) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                if (preg_match($headingPattern, $line)) { $awardCurrent = $line; continue; }
                $chips = [];
                $title = $line;
                if (preg_match('/^(.+?),\s*(Grade\s*\d+),\s*(\d{4})$/i', $line, $m)) { $title = $m[1]; $chips = [$m[2], $m[3]]; }
                elseif (preg_match('/^(.+?),\s*(\d{4})$/', $line, $m)) { $title = $m[1]; $chips = [$m[2]]; }
                $levels[$awardCurrent][] = ['title' => $title, 'chips' => $chips];
            }
            if (($award['meta'] ?? '') !== '' && ! empty($levels[$awardCurrent])) {
                $last = array_key_last($levels[$awardCurrent]);
                $levels[$awardCurrent][$last]['chips'][] = $award['meta'];
            }
        }
    }
@endphp
<section class="sb-block">
    <h2>Honors / awards</h2>
    @forelse($levels as $levelName => $rows)
        <div class="ap-award-level">
            <div class="ap-award-level__head">
                <h3 class="ap-award-level__title">{{ $levelName }}</h3>
                <span class="ap-award-level__count">{{ count($rows) }} {{ \Illuminate\Support\Str::plural('award', count($rows)) }}</span>
            </div>
            <ul class="ap-award-list">
                @foreach($rows as $row)
                    <li class="ap-award">
                        <span class="ap-award__title">{{ $row['title'] }}</span>
                        @if($row['chips'])
                            <span class="ap-award__chips">@foreach($row['chips'] as $chip)<span class="ap-award__chip">{{ $chip }}</span>@endforeach</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="sb-note">No awards listed.</p>
    @endforelse
</section>
