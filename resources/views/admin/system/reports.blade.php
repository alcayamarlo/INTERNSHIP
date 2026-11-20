@extends('layouts.app')

@section('title', 'System Reports')

@section('content')
<div class="sb-dash">
<x-page-header title="System Reports" subtitle="Generate platform-wide reports, and back up all users' data">
    <form action="{{ route('admin.backup') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-secondary" data-loading-text="Backing up... this can take a minute"><i class="bi bi-database-down" aria-hidden="true"></i> Back up all users' data</button>
    </form>
</x-page-header>

<div class="card mb-4" data-backups-card>
    <div class="card-header"><h2><i class="bi bi-shield-lock" aria-hidden="true"></i> Encrypted backups</h2></div>
    <div class="card-body">
        <p class="small mb-2"><strong>What a backup holds:</strong> every user of every role (admins, coordinators, students, companies, supervisors), student profiles, resumes, certificates, portfolio and ID files, company logos and photos, messages, applications, OJT records and the system settings. It is one AES-256 encrypted file; it opens only with <code>BACKUP_PASSWORD</code>.</p>
        <p class="small text-muted mb-3"><strong>Not in it:</strong> the <code>.env</code> file and <code>APP_KEY</code> (keep those with the password, away from this server: personal data in a restored backup can only be read with the same <code>APP_KEY</code>). Check a backup from a terminal with <code>php artisan backup:verify &lt;file&gt;</code>, or with Verify below.</p>
        @if(count($backups))
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th scope="col">Backup</th><th scope="col">Made (Philippine time)</th><th scope="col">Size</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach($backups as $backup)
                            <tr>
                                <td>{{ $backup['name'] }}</td>
                                <td>{{ $backup['at']->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                                <td>{{ number_format($backup['size'] / 1048576, 1) }} MB</td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.backups.download', $backup['name']) }}"><i class="bi bi-download" aria-hidden="true"></i> Download</a>
                                    <form action="{{ route('admin.backups.verify', $backup['name']) }}" method="POST" class="d-inline">@csrf<button type="submit" class="btn btn-sm btn-outline-primary"><i class="bi bi-patch-check" aria-hidden="true"></i> Verify</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="small text-muted mb-0">No backups on this server yet. A backup is also made every night at 2:00 AM.</p>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h2><i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Generate Report</h2></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.reports') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="reportType">Report Type</label>
                        <select id="reportType" class="form-select" name="type" required>
                            <option value="placement">Placement Report</option>
                            <option value="student">Student Report</option>
                            <option value="competency">Competency Report</option>
                            <option value="employer">Employer Report</option>
                            <option value="internship">Internship Report</option>
                            <option value="ojt">OJT Hours &amp; Evaluation Report</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reportFormat">Format</label>
                        <select id="reportFormat" class="form-select" name="format" required>
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-download" aria-hidden="true"></i> Generate &amp; Download</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h2><i class="bi bi-clock-history" aria-hidden="true"></i> Report History</h2></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th scope="col">Type</th><th scope="col">Format</th><th scope="col">Generated By</th><th scope="col">Date</th></tr></thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ ucfirst($report->type) }}</td>
                                <td><span class="sb-tag">{{ strtoupper($report->format) }}</span></td>
                                <td>{{ $report->generator?->name ?? '—' }}</td>
                                <td>{{ $report->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><x-empty-state icon="bi-file-earmark-bar-graph" title="No reports generated yet" message="Choose a report type and format to generate one." /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())<div class="card-footer">{{ $reports->links() }}</div>@endif
        </div>
    </div>
</div>
</div>
@endsection
