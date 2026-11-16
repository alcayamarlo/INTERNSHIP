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
    <section class="sb-dash" aria-labelledby="adInsights">
        <div class="sb-section-head">
            <h2 id="adInsights">Platform insights</h2>
            <p>Track applications, competencies, and the skills employers need most.</p>
        </div>
        <div class="sb-grid sb-grid--2">
            @foreach($order as $index => $chartId)
                @php [$title, $icon, $label] = $chartCards[$chartId]; @endphp
                <div class="card {{ $chartId === 'skillsChart' ? 'sb-span-all' : '' }}">
                    <div class="card-header"><h2><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $title }}</h2></div>
                    <div class="card-body"><div class="sb-chart"><canvas id="{{ $chartId }}" role="img" aria-label="{{ $label }} chart"></canvas></div></div>
                </div>
            @endforeach
        </div>
    </section>
@else
    <div class="sb-grid sb-grid--2">
        @foreach($order as $chartId)
            @php [$title, $icon, $label] = $chartCards[$chartId]; @endphp
            <div class="card {{ $chartId === 'skillsChart' ? 'sb-span-all' : '' }}">
                    <div class="card-header"><h2><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $title }}</h2></div>
                    <div class="card-body"><div class="sb-chart"><canvas id="{{ $chartId }}" role="img" aria-label="{{ $label }}"></canvas></div></div>
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
            red: '#7A010E',
            redFill: 'rgba(122,1,14,.12)',
            green: '#000000',
            amber: '#6B6B72',
            danger: '#7A010E',
            blue: '#3A3A40',
            purple: '#A0A0A6',
            muted: '#6B6B72',
            grid: 'rgba(0,0,0,.08)',
        };
        const refreshChartTheme = () => {
            const theme = getComputedStyle(document.documentElement);
            Object.assign(chartColors, {
                red: theme.getPropertyValue('--burgundy').trim() || '#7A010E',
                green: theme.getPropertyValue('--text').trim() || '#000000',
                amber: theme.getPropertyValue('--muted').trim() || '#6B6B72',
                danger: theme.getPropertyValue('--burgundy-text').trim() || '#7A010E',
                blue: theme.getPropertyValue('--chart-4').trim() || '#3A3A40',
                purple: theme.getPropertyValue('--chart-5').trim() || '#A0A0A6',
                muted: theme.getPropertyValue('--muted').trim() || '#6B6B72',
                grid: theme.getPropertyValue('--line').trim() || 'rgba(0,0,0,.08)',
            });
            if (isAdmin) {
                chartColors.red = theme.getPropertyValue('--burgundy').trim() || '#7A010E';
                chartColors.redFill = 'rgba(122,1,14,.10)';
                chartColors.grid = theme.getPropertyValue('--line').trim() || 'rgba(0,0,0,.07)';
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
                        dataset.backgroundColor = 'rgba(0,0,0,.08)';
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
        // Draw-in once, when the chart is first visible, capped at 600ms. None with reduced motion, and none on
        // the admin dashboard (its five charts dropped frames on a throttled CPU even when drawn one by one).
        const noDrawIn = reduceMotion || isAdmin;
        Chart.defaults.animation = noDrawIn ? false : { duration: 600, easing: 'easeOutQuart' };
        const barAnimation = noDrawIn ? false : { duration: 600, easing: 'easeOutQuart' };
        const draw = (canvas, config) => {
            if (!canvas) return;
            if (noDrawIn || !('IntersectionObserver' in window)) {
                new Chart(canvas, config);
                return;
            }
            const watcher = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) return;
                watcher.disconnect();
                new Chart(canvas, config);
            }, { threshold: 0.2 });
            watcher.observe(canvas);
        };
        // Progressive line: each point starts where the previous one ended, so the line draws left to right.
        const lineDrawIn = (pointCount) => {
            if (noDrawIn) return false;
            const step = 600 / Math.max(pointCount, 1);
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

                draw(document.getElementById('applicationsChart'), {
                    type: 'line',
                    data: {
                        labels: data.applications_per_month.map(i => i.month),
                        datasets: [{ label: 'Applications', data: data.applications_per_month.map(i => i.total), borderColor: red, backgroundColor: redFill, fill: true, ...lineStyle, ...(isAdmin ? { pointBackgroundColor: red } : {}) }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, animation: lineDrawIn(data.applications_per_month.length), interaction: { intersect: false, mode: 'index' }, scales: adminScales('y'), plugins: { legend: { display: false } } }
                        : { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
                draw(document.getElementById('placementRateChart'), {
                    type: 'line',
                    data: {
                        labels: data.placement_rate_by_month.map(i => i.month),
                        datasets: [{ label: 'Placement rate %', data: data.placement_rate_by_month.map(i => i.rate), borderColor: isAdmin ? red : chartColors.green, backgroundColor: isAdmin ? redFill : 'rgba(0,0,0,.12)', fill: true, ...lineStyle, ...(isAdmin ? { pointBackgroundColor: red } : {}) }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, animation: lineDrawIn(data.placement_rate_by_month.length), interaction: { intersect: false, mode: 'index' }, scales: adminScales('y', { min: 0, max: 100 }), plugins: { legend: { display: false } } }
                        : { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 } }, plugins: { legend: { display: false } } }
                });
                draw(statusCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: data.placements.map(i => i.status),
                        datasets: [{ data: data.placements.map(i => i.total), backgroundColor: [red, chartColors.green, chartColors.amber, chartColors.danger, chartColors.blue, chartColors.purple] }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, cutout: '62%', ...(noDrawIn ? { animation: false } : {}), plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, boxHeight: 12, useBorderRadius: true, borderRadius: 3 } } } }
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
                draw(document.getElementById('competencyChart'), {
                    type: 'bar',
                    data: {
                        labels: data.competency_levels.map(i => i.proficiency_level),
                        datasets: [{ label: 'Count', data: data.competency_levels.map(i => i.total), backgroundColor: red, ...barStyle }]
                    },
                    options: isAdmin
                        ? { responsive: true, maintainAspectRatio: false, animation: barAnimation, scales: adminScales('y'), plugins: { legend: { display: false } } }
                        : { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });
                draw(document.getElementById('skillsChart'), {
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
