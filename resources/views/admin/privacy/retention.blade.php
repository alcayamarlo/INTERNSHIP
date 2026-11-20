@extends('layouts.app')

@section('title', 'Retention review')

@section('content')
<div class="sb-dash">
<x-page-header title="Retention review" :subtitle="'Student accounts not used since '.$cutoff->timezone('Asia/Manila')->format('M d, Y').' ('.$years.'-year retention period). Nothing is deleted from here: opening a request sends it to the approval queue.'" />

<div class="card">
    @if($candidates->isNotEmpty())
        <div class="table-responsive">
            <table class="table mb-0 sb-table-stack" data-retention-list>
                <thead><tr><th scope="col">Student</th><th scope="col">Last activity</th><th scope="col">Basis</th><th scope="col"><span class="visually-hidden">Action</span></th></tr></thead>
                <tbody>
                    @foreach($candidates as $row)
                        <tr>
                            <td class="sb-stack-block"><span class="sb-row-title">{{ $row->student->user->name }}</span><small class="sb-row-sub">{{ $row->student->student_id_number ?? 'No school ID' }}</small></td>
                            <td data-label="Last activity">{{ $row->last_activity->timezone('Asia/Manila')->format('M d, Y') }}</td>
                            <td data-label="Basis">{{ $row->basis }}</td>
                            <td class="text-md-end">
                                <form method="POST" action="{{ route('admin.retention.request', $row->student) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Open deletion request</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-hourglass-split" title="No accounts past the retention period" message="Accounts are listed here when they have not been used, and no OJT has ended, for {{ $years }} years. Change the period with PRIVACY_RETENTION_YEARS." />
    @endif
</div>
</div>
@endsection
