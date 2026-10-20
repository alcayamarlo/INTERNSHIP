{{--
    Shared analytics charts (Chart.js). Used by the admin dashboard and the
    coordinator analytics page. Props:
    - analyticsUrl (required): the analytics.charts endpoint URL.
    - months (optional, default 12): how many months of trend data to request.
    Rendered with @include (not an <x-...> component), so props are passed as
    a plain array and defaulted here with PHP, not @props.
--}}
@php($months ??= 12)

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2><i class="bi bi-graph-up" aria-hidden="true"></i> Applications per Month</h2></div>
            <div class="card-body"><div class="sb-chart"><canvas id="applicationsChart" role="img" aria-label="Applications per month"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2><i class="bi bi-speedometer2" aria-hidden="true"></i> Placement Rate Trend</h2></div>
            <div class="card-body"><div class="sb-chart"><canvas id="placementRateChart" role="img" aria-label="Placement rate by month"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2><i class="bi bi-pie-chart" aria-hidden="true"></i> Application Status</h2></div>
            <div class="card-body"><div class="sb-chart"><canvas id="statusChart" role="img" aria-label="Application status breakdown"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2><i class="bi bi-bar-chart" aria-hidden="true"></i> Competency Levels</h2></div>
            <div class="card-body"><div class="sb-chart"><canvas id="competencyChart" role="img" aria-label="Competency levels"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2><i class="bi bi-stars" aria-hidden="true"></i> Top Required Skills</h2></div>
            <div class="card-body"><div class="sb-chart"><canvas id="skillsChart" role="img" aria-label="Top required skills"></canvas></div></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const analyticsUrl = "{{ $analyticsUrl }}?months={{ (int) $months }}";
        const chartColors = {
            red: '#e11d2e',
            green: '#187443',
            amber: '#8a5700',
            danger: '#b42332',
            blue: '#235e91',
            purple: '#684c8d',
            muted: '#7a5f63',
        };
        const refreshChartTheme = () => {
            const theme = getComputedStyle(document.documentElement);
            Object.assign(chartColors, {
                red: theme.getPropertyValue('--sb-red').trim() || '#e11d2e',
                green: theme.getPropertyValue('--sb-success-ink').trim() || '#187443',
                amber: theme.getPropertyValue('--sb-warning-ink').trim() || '#8a5700',
                danger: theme.getPropertyValue('--sb-danger-ink').trim() || '#b42332',
                blue: theme.getPropertyValue('--sb-blue').trim() || '#235e91',
                purple: theme.getPropertyValue('--sb-purple').trim() || '#684c8d',
                muted: theme.getPropertyValue('--sb-muted').trim() || '#7a5f63',
            });
            Chart.defaults.color = chartColors.muted;
            const gridColor = theme.getPropertyValue('--sb-ink').trim() || '#1a0b0d';

            document.querySelectorAll('canvas').forEach(canvas => {
                const chart = Chart.getChart(canvas);
                if (!chart) return;

                chart.options.color = chartColors.muted;
                Object.values(chart.options.scales || {}).forEach(scale => {
                    scale.ticks.color = chartColors.muted;
                    scale.grid.color = gridColor;
                });
                chart.data.datasets.forEach(dataset => {
                    if (canvas.id === 'applicationsChart') {
                        dataset.borderColor = chartColors.red;
                        dataset.backgroundColor = 'rgba(225,29,46,.12)';
                    } else if (canvas.id === 'placementRateChart') {
                        dataset.borderColor = chartColors.green;
                        dataset.backgroundColor = 'rgba(24,116,67,.12)';
                    } else if (canvas.id === 'statusChart') {
                        dataset.backgroundColor = [chartColors.red, chartColors.green, chartColors.amber, chartColors.danger, chartColors.blue, chartColors.purple];
                    } else {
                        dataset.backgroundColor = chartColors.red;
                    }
                });
                chart.update('none');
            });
        };
        refreshChartTheme();
        new MutationObserver(refreshChartTheme).observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme'],
        });
        Chart.defaults.font.family = 'Figtree, sans-serif';
        Chart.defaults.font.size = 12;

        fetch(analyticsUrl)
            .then(r => r.json())
            .then(data => {
                new Chart(document.getElementById('applicationsChart'), {
                    type: 'line',
                    data: {
                        labels: data.applications_per_month.map(i => i.month),
                        datasets: [{ label: 'Applications', data: data.applications_per_month.map(i => i.total), borderColor: chartColors.red, backgroundColor: 'rgba(225,29,46,.12)', fill: true }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
                new Chart(document.getElementById('placementRateChart'), {
                    type: 'line',
                    data: {
                        labels: data.placement_rate_by_month.map(i => i.month),
                        datasets: [{ label: 'Placement rate %', data: data.placement_rate_by_month.map(i => i.rate), borderColor: chartColors.green, backgroundColor: 'rgba(24,116,67,.12)', fill: true }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } }
                });
                new Chart(document.getElementById('statusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: data.placements.map(i => i.status),
                        datasets: [{ data: data.placements.map(i => i.total), backgroundColor: [chartColors.red, chartColors.green, chartColors.amber, chartColors.danger, chartColors.blue, chartColors.purple] }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%' }
                });
                new Chart(document.getElementById('competencyChart'), {
                    type: 'bar',
                    data: {
                        labels: data.competency_levels.map(i => i.proficiency_level),
                        datasets: [{ label: 'Count', data: data.competency_levels.map(i => i.total), backgroundColor: chartColors.red }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
                new Chart(document.getElementById('skillsChart'), {
                    type: 'bar',
                    data: {
                        labels: data.top_skills.map(i => i.requirement_name),
                        datasets: [{ label: 'Demand', data: data.top_skills.map(i => i.total), backgroundColor: chartColors.red }]
                    },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            });
    })();
</script>
@endpush
