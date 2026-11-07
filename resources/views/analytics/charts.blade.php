{{--
    Shared analytics charts (Chart.js). Used by the admin dashboard and the
    coordinator analytics page. Props:
    - analyticsUrl (required): the analytics.charts endpoint URL.
    - months (optional, default 12): how many months of trend data to request.
    - variant (optional, default 'default'): 'admin' uses the admin dashboard
      layout (Applications per Month and Competency Levels first) and styling
      (red series, light grid, rounded bars, draw-in animation).
    Rendered with @include (not an <x-...> component), so props are passed as
    a plain array and defaulted here with PHP, not @props.
--}}
@php
    $months ??= 12;
    $variant ??= 'default';
    $isAdmin = $variant === 'admin';

    // [canvas id, title, icon, aria-label]
    $chartCards = [
        'applicationsChart' => ['Applications per Month', 'bi-graph-up', 'Applications per month'],
        'placementRateChart' => ['Placement Rate Trend', 'bi-speedometer2', 'Placement rate by month'],
        'statusChart' => ['Application Status', 'bi-pie-chart', 'Application status breakdown'],
        'competencyChart' => ['Competency Levels', 'bi-bar-chart', 'Competency levels'],
        'skillsChart' => ['Top Required Skills', 'bi-stars', 'Top required skills'],
    ];
    $order = $isAdmin
        ? ['applicationsChart', 'competencyChart', 'placementRateChart', 'statusChart', 'skillsChart']
        : array_keys($chartCards);
@endphp

@if($isAdmin)
    <section class="ad-charts" aria-labelledby="adInsights">
        <div class="ad-section-head ad-reveal" style="--ad-i: 8">
            <h2 id="adInsights">Platform insights</h2>
            <p>Track applications, competencies, and the skills employers need most.</p>
        </div>
        <div class="ad-chart-grid">
            @foreach($order as $index => $chartId)
                @php [$title, $icon, $label] = $chartCards[$chartId]; @endphp
                <div class="ad-card ad-chart-card ad-reveal {{ $chartId === 'skillsChart' ? 'ad-chart-card--wide' : '' }}" style="--ad-i: {{ 9 + $index }}">
                    <div class="ad-card-head"><h2><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $title }}</h2></div>
                    <div class="sb-chart ad-chart"><canvas id="{{ $chartId }}" role="img" aria-label="{{ $label }} chart"></canvas></div>
                </div>
            @endforeach
        </div>
    </section>
@else
    <div class="row g-4 mb-4">
        @foreach($order as $chartId)
            @php [$title, $icon, $label] = $chartCards[$chartId]; @endphp
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header"><h2><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $title }}</h2></div>
                    <div class="card-body"><div class="sb-chart"><canvas id="{{ $chartId }}" role="img" aria-label="{{ $label }}"></canvas></div></div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@push('scripts')
<script>
    (function () {
        const analyticsUrl = "{{ $analyticsUrl }}?months={{ (int) $months }}";
        const isAdmin = @json($isAdmin);
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const chartColors = {
            red: '#e11d2e',
            redFill: 'rgba(225,29,46,.12)',
            green: '#187443',
            amber: '#8a5700',
            danger: '#b42332',
            blue: '#235e91',
            purple: '#684c8d',
            muted: '#7a5f63',
            grid: 'rgba(142,13,28,.1)',
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
                grid: theme.getPropertyValue('--sb-chart-grid').trim() || 'rgba(142,13,28,.1)',
            });
            if (isAdmin) {
                // Admin palette lives on <body> (public/css/admin-dashboard.css).
                const admin = getComputedStyle(document.body);
                chartColors.red = admin.getPropertyValue('--ad-chart-red').trim() || '#C8102E';
                chartColors.redFill = admin.getPropertyValue('--ad-chart-fill').trim() || 'rgba(200,16,46,.10)';
                chartColors.grid = admin.getPropertyValue('--ad-chart-grid').trim() || 'rgba(200,16,46,.07)';
                chartColors.muted = admin.getPropertyValue('--ad-muted').trim() || chartColors.muted;
            }
            Chart.defaults.color = chartColors.muted;

            document.querySelectorAll('canvas').forEach(canvas => {
                const chart = Chart.getChart(canvas);
                if (!chart) return;

                chart.options.color = chartColors.muted;
                Object.values(chart.options.scales || {}).forEach(scale => {
                    scale.ticks.color = chartColors.muted;
                    scale.grid.color = chartColors.grid;
                });
                chart.data.datasets.forEach(dataset => {
                    if (canvas.id === 'applicationsChart' || (isAdmin && canvas.id === 'placementRateChart')) {
                        dataset.borderColor = chartColors.red;
                        dataset.backgroundColor = chartColors.redFill;
                        if (isAdmin) dataset.pointBackgroundColor = chartColors.red;
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
        Chart.defaults.font.family = isAdmin ? 'Inter, "Segoe UI", system-ui, sans-serif' : 'Figtree, sans-serif';
        Chart.defaults.font.size = isAdmin ? 13 : 12;

        // ---- Admin-only styling: light y-grid, no axis borders, rounded bars, draw-in lines ----
        const adminScales = (valueAxis = 'y', extra = {}) => {
            const categoryAxis = valueAxis === 'y' ? 'x' : 'y';
            return {
                [categoryAxis]: { grid: { display: false }, border: { display: false } },
                [valueAxis]: { beginAtZero: true, grid: { color: chartColors.grid }, border: { display: false }, ...extra },
            };
        };
        const barStyle = isAdmin ? { borderRadius: 8, maxBarThickness: 40 } : {};
        const barAnimation = reduceMotion ? false : { duration: 800, easing: 'easeOutQuart' };
        // Progressive line: each point starts where the previous one ended, so the line draws left to right.
        const lineDrawIn = (pointCount) => {
            if (reduceMotion) return false;
            const step = 1100 / Math.max(pointCount, 1);
            const fromPreviousY = ctx => ctx.index === 0
                ? ctx.chart.scales.y.getPixelForValue(0)
                : ctx.chart.getDatasetMeta(ctx.datasetIndex).data[ctx.index - 1].getProps(['y'], true).y;
            const delayed = key => ctx => {
                if (ctx.type !== 'data' || ctx[key]) return 0;
                ctx[key] = true;
                return ctx.index * step;
            };
            return {
                x: { type: 'number', easing: 'linear', duration: step, from: NaN, delay: delayed('xStarted') },
                y: { type: 'number', easing: 'linear', duration: step, from: fromPreviousY, delay: delayed('yStarted') },
            };
        };
        const lineStyle = isAdmin ? { tension: .35, borderWidth: 2.5, pointRadius: 3, pointHoverRadius: 5 } : {};

        fetch(analyticsUrl)
            .then(r => r.json())
            .then(data => {
                const red = chartColors.red;
                const redFill = chartColors.redFill;

                // Optional header summary (coordinator analytics page): same formula as the
                // coordinator dashboard, accepted applications ÷ all applications.
                const summary = document.querySelector('[data-analytics-summary]');
                if (summary) {
                    const total = data.placements.reduce((sum, row) => sum + Number(row.total), 0);
                    const accepted = Number(data.placements.find(row => row.status === 'accepted')?.total ?? 0);
                    const rate = total > 0 ? Math.round((accepted / total) * 1000) / 10 : 0;
                    summary.querySelector('[data-summary="total"]').textContent = total;
                    summary.querySelector('[data-summary="placements"]').textContent = accepted;
                    summary.querySelector('[data-summary="rate"]').textContent = `${rate}%`;
                }

                // Short donut cards put the legend beside the donut instead of above it.
                const donutLegendPosition = height => height < 230 ? 'right' : 'top';
                const statusCanvas = document.getElementById('statusChart');

                new Chart(document.getElementById('applicationsChart'), {
                    type: 'line',
                    data: {
                        labels: data.applications_per_month.map(i => i.month),
                        datasets: [{ label: 'Applications', data: data.applications_per_month.map(i => i.total), borderColor: red, backgroundColor: redFill, fill: true, ...lineStyle, ...(isAdmin ? { pointBackgroundColor: red } : {}) }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, animation: lineDrawIn(data.applications_per_month.length), interaction: { intersect: false, mode: 'index' }, scales: adminScales('y'), plugins: { legend: { display: false } } }
                        : { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
                new Chart(document.getElementById('placementRateChart'), {
                    type: 'line',
                    data: {
                        labels: data.placement_rate_by_month.map(i => i.month),
                        datasets: [{ label: 'Placement rate %', data: data.placement_rate_by_month.map(i => i.rate), borderColor: isAdmin ? red : chartColors.green, backgroundColor: isAdmin ? redFill : 'rgba(24,116,67,.12)', fill: true, ...lineStyle, ...(isAdmin ? { pointBackgroundColor: red } : {}) }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, animation: lineDrawIn(data.placement_rate_by_month.length), interaction: { intersect: false, mode: 'index' }, scales: adminScales('y', { min: 0, max: 100 }), plugins: { legend: { display: false } } }
                        : { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } }
                });
                new Chart(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: data.placements.map(i => i.status),
                        datasets: [{ data: data.placements.map(i => i.total), backgroundColor: [red, chartColors.green, chartColors.amber, chartColors.danger, chartColors.blue, chartColors.purple] }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, cutout: '62%', ...(reduceMotion ? { animation: false } : {}), plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, useBorderRadius: true, borderRadius: 3 } } } }
                        : {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '62%',
                            plugins: { legend: { position: donutLegendPosition(statusCanvas.parentElement.clientHeight) } },
                            onResize: (chart, size) => {
                                const position = donutLegendPosition(size.height);
                                if (chart.options.plugins.legend.position === position) return;
                                chart.options.plugins.legend.position = position;
                                chart.update('none');
                            },
                        }
                });
                new Chart(document.getElementById('competencyChart'), {
                    type: 'bar',
                    data: {
                        labels: data.competency_levels.map(i => i.proficiency_level),
                        datasets: [{ label: 'Count', data: data.competency_levels.map(i => i.total), backgroundColor: red, ...barStyle }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, animation: barAnimation, scales: adminScales('y'), plugins: { legend: { display: false } } }
                        : { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
                new Chart(document.getElementById('skillsChart'), {
                    type: 'bar',
                    data: {
                        labels: data.top_skills.map(i => i.requirement_name),
                        datasets: [{ label: 'Demand', data: data.top_skills.map(i => i.total), backgroundColor: red, ...barStyle }]
                    },
                    options: isAdmin
                        ? { indexAxis: 'y', responsive: true, maintainAspectRatio: false, animation: barAnimation, scales: adminScales('x'), plugins: { legend: { display: false } } }
                        : { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
            });
    })();
</script>
@endpush
