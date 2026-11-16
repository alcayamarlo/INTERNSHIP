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
        <ul class="sb-rows sb-rows--card">
            @foreach($items as $item)
                <li>
                    <span class="sb-grow">{{ $item['label'] }}@if($item['kind'] === 'monthly_evaluation' && $item['step'] === 'student_comment') <span class="text-muted small">· your comment</span>@endif</span>
                    <x-status-badge status="due" :tone="$item['overdue'] ? 'danger' : 'warning'" :label="$item['overdue'] ? 'Overdue · was due '.$item['due_on']->format('M j') : 'Due '.$item['due_on']->format('M j')" />
                </li>
            @endforeach
        </ul>
    </section>
@endif
