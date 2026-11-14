{{--
    Documents still owed on an active OJT, with "Due Nov 20" / "Overdue" badges.
    Expects $placement and $actor: student | supervisor (only that person's items are listed).
--}}
@php
    $items = collect(app(\App\Services\OjtDeadlineService::class)->pendingFor($placement))
        ->where('actor', $actor)
        ->sortBy(fn ($item) => $item['due_on']->timestamp)
        ->values();
@endphp
@if($items->isNotEmpty())
    <section class="card mb-4" aria-labelledby="dueDocs-{{ $placement->id }}" data-live-region="ojt-deadlines-{{ $placement->id }}-{{ $actor }}" data-live-topics="placements">
        <div class="card-header"><h2 class="h6 mb-0" id="dueDocs-{{ $placement->id }}"><i class="bi bi-alarm" aria-hidden="true"></i> Due documents</h2></div>
        <ul class="list-group list-group-flush">
            @foreach($items as $item)
                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>{{ $item['label'] }}@if($item['kind'] === 'monthly_evaluation' && $item['step'] === 'student_comment') <span class="text-muted small">· your comment</span>@endif</span>
                    <span class="badge {{ $item['overdue'] ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $item['overdue'] ? 'Overdue · was due '.$item['due_on']->format('M j') : 'Due '.$item['due_on']->format('M j') }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endif
