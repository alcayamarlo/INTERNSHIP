{{-- Weekly journals. Expects $placement and $mode: student | employer | readonly. --}}
@php $mode ??= 'readonly'; @endphp

<div class="card mb-4">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-journal-text" aria-hidden="true"></i> Weekly Journal</h2></div>
    <div class="card-body">
        @forelse($placement->journalEntries as $entry)
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <strong>{{ $entry->title }}</strong>
                        <div class="small text-muted">Week of {{ $entry->weekLabel() }}</div>
                    </div>
                    <x-status-badge :status="$entry->isAcknowledged() ? 'approved' : 'pending'" :label="$entry->isAcknowledged() ? 'Acknowledged' : 'Submitted'" />
                </div>
                <div class="small"><strong>Accomplishments:</strong> {!! nl2br(e($entry->accomplishments)) !!}</div>
                @if($entry->learnings)<div class="small mt-1"><strong>Learnings:</strong> {!! nl2br(e($entry->learnings)) !!}</div>@endif
                @if($entry->challenges)<div class="small mt-1"><strong>Challenges:</strong> {!! nl2br(e($entry->challenges)) !!}</div>@endif
                @if($entry->supervisor_comment)
                    <div class="small mt-2 p-2 rounded" style="background: rgba(13,110,253,.08);"><i class="bi bi-chat-quote" aria-hidden="true"></i> <strong>Supervisor:</strong> {{ $entry->supervisor_comment }}</div>
                @endif

                @if($mode === 'student' && ! $entry->isAcknowledged() && $placement->isActive())
                    <form method="POST" action="{{ route('student.ojt.journals.destroy', [$placement, $entry]) }}" class="mt-2" data-confirm="Delete this journal entry?" data-confirm-title="Delete journal">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                @elseif($mode === 'employer' && ! $entry->isAcknowledged())
                    <form method="POST" action="{{ route('employer.interns.journals.review', [$placement, $entry]) }}" class="mt-2">
                        @csrf @method('PATCH')
                        <label class="form-label small" for="comment-{{ $entry->id }}">Comment (optional)</label>
                        <textarea id="comment-{{ $entry->id }}" name="supervisor_comment" rows="2" class="form-control form-control-sm mb-2" maxlength="2000"></textarea>
                        <button type="submit" class="btn btn-sm btn-primary">Acknowledge</button>
                    </form>
                @endif
            </div>
        @empty
            <x-empty-state icon="bi-journal-text" title="No journal entries yet" message="Weekly reflections appear here once the student submits them." />
        @endforelse
    </div>
</div>
