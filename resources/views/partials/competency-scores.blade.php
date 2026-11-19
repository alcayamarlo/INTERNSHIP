{{-- Competency scores as progress bars with percentages. Expects $scores: list of ['skill' => string, 'score' => 0-100]
     (App\Models\StudentCompetencyScore::forDisplay). Optional $heading and $note. Renders nothing when there are none. --}}
@php
    $scores = collect($scores ?? []);
    $average = $scores->isEmpty() ? null : (int) round($scores->avg('score'));
@endphp
@if($scores->isNotEmpty())
    <section class="card mb-4 competency-scores" aria-labelledby="competencyScoresHeading">
        <div class="card-header d-flex flex-wrap align-items-baseline justify-content-between gap-2">
            <h2 class="h6 mb-0" id="competencyScoresHeading"><i class="bi bi-bar-chart-line" aria-hidden="true"></i> {{ $heading ?? 'Competency scores' }}</h2>
            <small class="text-muted">Average {{ $average }}%</small>
        </div>
        <div class="card-body">
            @isset($note)<p class="small text-muted">{{ $note }}</p>@endisset
            <ul class="list-unstyled mb-0 d-grid gap-3">
                @foreach($scores as $row)
                    <li>
                        <div class="d-flex justify-content-between gap-3 small mb-1">
                            <span>{{ $row['skill'] }}</span>
                            <strong>{{ $row['score'] }}%</strong>
                        </div>
                        <div class="progress progress--thin" role="progressbar" aria-label="{{ $row['skill'] }}" aria-valuenow="{{ $row['score'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $row['score'] }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
