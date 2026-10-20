{{-- DTR table. Expects $placement and $mode: student | employer | readonly. --}}
@php
    $mode ??= 'readonly';
    $logs = $placement->attendanceLogs;
    $hasPending = $logs->contains('status', \App\Models\AttendanceLog::STATUS_PENDING);
@endphp

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h6 mb-0"><i class="bi bi-clock" aria-hidden="true"></i> Daily Time Record</h2>
        @if($mode === 'employer' && $hasPending && ! $placement->isCleared())
            <form method="POST" action="{{ route('employer.interns.attendance.approve-all', $placement) }}" data-confirm="Approve every pending time entry?" data-confirm-title="Approve all">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-all" aria-hidden="true"></i> Approve all pending</button>
            </form>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Time</th>
                    <th scope="col">Hours</th>
                    <th scope="col">Tasks</th>
                    <th scope="col">Status</th>
                    @if($mode !== 'readonly')<th scope="col"><span class="visually-hidden">Actions</span></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php $locked = $placement->isCleared() || ($placement->isCompleted() && $log->isApproved()); @endphp
                    <tr>
                        <td data-label="Date" class="text-nowrap"><strong>{{ $log->work_date->format('D, M d, Y') }}</strong></td>
                        <td data-label="Time" class="text-nowrap small">{{ $log->timeInLabel() }} – {{ $log->timeOutLabel() }}@if($log->break_minutes)<div class="text-muted">{{ $log->break_minutes }} min break</div>@endif</td>
                        <td data-label="Hours"><strong>{{ number_format((float) $log->hours, 2) }}</strong></td>
                        <td data-label="Tasks" class="small sb-stack-block" style="min-width: 220px;">{{ $log->task_summary }}
                            @if($log->reviewer_remarks)<div class="text-muted mt-1"><i class="bi bi-chat-left-text" aria-hidden="true"></i> {{ $log->reviewer_remarks }}</div>@endif
                        </td>
                        <td data-label="Status"><x-status-badge :status="$log->status" /></td>
                        @if($mode === 'student')
                            <td class="text-nowrap sb-stack-hide-empty">
                                @unless($log->isApproved())
                                    <form method="POST" action="{{ route('student.ojt.attendance.destroy', [$placement, $log]) }}" data-confirm="Delete this time entry?" data-confirm-title="Delete entry">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endunless
                            </td>
                        @elseif($mode === 'employer')
                            <td class="text-nowrap sb-stack-block sb-stack-hide-empty">
                                @unless($locked)
                                    <form method="POST" action="{{ route('employer.interns.attendance.review', [$placement, $log]) }}" class="d-flex flex-column gap-1" style="min-width: 190px;">
                                        @csrf @method('PATCH')
                                        <input type="text" name="reviewer_remarks" class="form-control form-control-sm" placeholder="Remarks (required to reject)" maxlength="1000" aria-label="Remarks for {{ $log->work_date->format('M d') }}">
                                        <div class="d-flex gap-1">
                                            @unless($log->isApproved())
                                                <button type="submit" name="status" value="approved" class="btn btn-sm btn-success flex-fill">Approve</button>
                                            @endunless
                                            @if($log->status !== 'rejected')
                                                <button type="submit" name="status" value="rejected" class="btn btn-sm btn-outline-danger flex-fill">Reject</button>
                                            @endif
                                        </div>
                                    </form>
                                @endunless
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $mode === 'readonly' ? 5 : 6 }}" class="sb-stack-block"><x-empty-state icon="bi-clock" title="No time entries yet" message="Daily time entries appear here once the student logs them." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
