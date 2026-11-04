{{-- Shared by the statistics page and its PDF export. --}}
<table class="table table-sm stats-table">
    <tbody>
        @foreach($labels as $key => $label)
            <tr><th scope="row">{{ $label }}</th><td class="text-end" data-total="{{ $key }}">{{ number_format($stats['totals'][$key]) }}</td></tr>
        @endforeach
        <tr><th scope="row">Placement rate</th><td class="text-end" data-rate="placement">{{ $stats['rates']['placement'] }}%</td></tr>
        <tr><th scope="row">Completion rate</th><td class="text-end" data-rate="completion">{{ $stats['rates']['completion'] }}%</td></tr>
    </tbody>
</table>

<h3 class="h6 mt-4">By program</h3>
<table class="table table-sm stats-table">
    <thead><tr><th>Program</th><th class="text-end">Enrolled</th><th class="text-end">Placed</th><th class="text-end">Completed</th></tr></thead>
    <tbody>
        @forelse($stats['programs'] as $row)
            <tr><td>{{ $row['program'] }}</td><td class="text-end">{{ $row['enrolled'] }}</td><td class="text-end">{{ $row['placed'] }}</td><td class="text-end">{{ $row['completed'] }}</td></tr>
        @empty
            <tr><td colspan="4">No students yet.</td></tr>
        @endforelse
    </tbody>
</table>

<h3 class="h6 mt-4">Partner companies</h3>
<table class="table table-sm stats-table">
    <thead><tr><th>Company</th><th class="text-end">Interns</th><th class="text-end">Completed</th><th class="text-end">Avg. evaluation</th><th class="text-end">Would hire</th></tr></thead>
    <tbody>
        @forelse($stats['companies'] as $row)
            <tr>
                <td>{{ $row['company'] }}</td>
                <td class="text-end">{{ $row['interns'] }}</td>
                <td class="text-end">{{ $row['completed'] }}</td>
                <td class="text-end">{{ $row['average_score'] !== null ? number_format($row['average_score'], 2).' / 5' : '—' }}</td>
                <td class="text-end">{{ $row['would_hire'] !== null ? $row['would_hire'].'%' : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No placements yet.</td></tr>
        @endforelse
    </tbody>
</table>
