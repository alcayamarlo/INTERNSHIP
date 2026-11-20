{{-- OJT Monitoring > Requests and reminders. Request a document for every ongoing OJT (once per period) and remind
     whoever's turn it is (no limit). Needs $panel (OjtReminderService counts). Without JavaScript every form shows. --}}
@php
    $types = \App\Services\OjtReminderService::TYPES;
    $selected = in_array(old('type'), array_keys($types), true) ? old('type') : 'monthly';
    $waitHours = \App\Services\OjtReminderService::waitHours();
    $remindAt = \Carbon\CarbonImmutable::parse((string) config('ojt_reminders.daily_log_remind_at', '16:00'))->format('g:i A');
    $help = [
        'daily' => "Students with no time logged today: {$panel['not_logged']}".($panel['waiting'] ? " ({$panel['waiting']} reminded in the last {$waitHours} hours)" : '')
            .". A student can be reminded again {$waitHours} hours after the last reminder; students who have not logged by {$remindAt} are also reminded automatically.",
        'weekly' => 'Weekly reports are due every week on their own, so there is nothing to request. Remind reaches students with missing reports and supervisors with reports waiting to be certified.',
        'monthly' => 'Request once per month for every ongoing OJT. Remind reaches whoever\'s turn it is on each open monthly evaluation: the student or the supervisor.',
        'final' => 'Request once for every ongoing OJT (already requested or evaluated ones are skipped). Remind reaches the supervisors whose final evaluation is still due.',
    ];
@endphp
<section class="card" aria-labelledby="ojtRequestsHeading" data-ojt-requests>
    <div class="card-header d-flex flex-wrap align-items-baseline justify-content-between gap-2">
        <h2 class="h6 mb-0" id="ojtRequestsHeading"><i class="bi bi-send" aria-hidden="true"></i> Requests and reminders</h2>
        <small class="text-muted">Request once per period. Remind as often as you need.</small>
    </div>
    <div class="card-body d-grid gap-3">
        {{-- Remind: the document select also drives which request form shows. --}}
        <form method="POST" action="{{ route('coordinator.ojt.reminders') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label class="form-label" for="ojtType">Document</label>
                <select id="ojtType" name="type" class="form-select" data-ojt-type>
                    @foreach($types as $key => [$label])
                        <option value="{{ $key }}" @selected($selected === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8 d-flex flex-wrap align-items-center gap-2">
                <button type="submit" name="audience" value="students" class="btn btn-outline-primary"><i class="bi bi-bell" aria-hidden="true"></i> Remind students</button>
                <button type="submit" name="audience" value="supervisors" class="btn btn-outline-primary"><i class="bi bi-bell" aria-hidden="true"></i> Remind supervisors</button>
            </div>
            @foreach($help as $key => $text)
                <p class="col-12 small text-muted mb-0" data-ojt-for="{{ $key }}" @if($key !== $selected) hidden @endif>{{ $text }}</p>
            @endforeach
        </form>

        {{-- Request: monthly evaluation (one month, once per student). --}}
        <form method="POST" action="{{ route('coordinator.ojt.requests') }}" class="row g-3 align-items-end border-top pt-3" data-ojt-for="monthly" @if($selected !== 'monthly') hidden @endif novalidate>
            @csrf
            <input type="hidden" name="type" value="monthly">
            <div class="col-md-4">
                <label class="form-label" for="ojtMonth">Month to evaluate</label>
                <select id="ojtMonth" name="period_month" class="form-select @if(old('type') === 'monthly') @error('period_month') is-invalid @enderror @endif" required>
                    @foreach(range(0, 5) as $back)
                        @php $month = now()->startOfMonth()->subMonths($back); @endphp
                        <option value="{{ $month->format('Y-m') }}" @selected(old('period_month') === $month->format('Y-m'))>{{ $month->format('F Y') }}</option>
                    @endforeach
                </select>
                @if(old('type') === 'monthly') @error('period_month')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
            </div>
            <div class="col-md-4">
                <label class="form-label" for="ojtMonthDue">Due date</label>
                <input id="ojtMonthDue" type="date" name="due_on" class="form-control @if(old('type') === 'monthly') @error('due_on') is-invalid @enderror @endif" required min="{{ now()->toDateString() }}" value="{{ old('type') === 'monthly' ? old('due_on') : now()->addDays(7)->toDateString() }}">
                @if(old('type') === 'monthly') @error('due_on')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
            </div>
            <div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-calendar-plus" aria-hidden="true"></i> Request monthly evaluation</button></div>
        </form>

        {{-- Request: final evaluation (once per student). --}}
        <form method="POST" action="{{ route('coordinator.ojt.requests') }}" class="row g-3 align-items-end border-top pt-3" data-ojt-for="final" @if($selected !== 'final') hidden @endif novalidate>
            @csrf
            <input type="hidden" name="type" value="final">
            <div class="col-md-4">
                <label class="form-label" for="ojtFinalDue">Due date</label>
                <input id="ojtFinalDue" type="date" name="due_on" class="form-control @if(old('type') === 'final') @error('due_on') is-invalid @enderror @endif" required min="{{ now()->toDateString() }}" value="{{ old('type') === 'final' ? old('due_on') : now()->addDays(7)->toDateString() }}">
                @if(old('type') === 'final') @error('due_on')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
            </div>
            <div class="col-md-4"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Request final evaluation</button></div>
        </form>
    </div>
</section>

@push('scripts')
<script>
    (function () {
        var panel = document.querySelector('[data-ojt-requests]');
        var select = panel && panel.querySelector('[data-ojt-type]');
        if (!select) return;
        function show() {
            [].forEach.call(panel.querySelectorAll('[data-ojt-for]'), function (el) {
                el.hidden = el.getAttribute('data-ojt-for') !== select.value;
            });
        }
        select.addEventListener('change', show);
        show();
    })();
</script>
@endpush
