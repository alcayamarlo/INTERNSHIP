<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Performance Evaluation Report</title>
<style>
    @page { margin: 16px 38px 14px; } /* Letter (short bond); the 70px seals in the header need this room */
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #111827; }
    .titles { text-align: center; margin: 6px 0 2px; line-height: 1.3; }
    .titles .college { font-size: 13px; font-weight: bold; }
    .titles .form { font-size: 12px; font-weight: bold; }
    .titles .company { margin-top: 5px; font-size: 12px; font-weight: bold; text-transform: uppercase; text-decoration: underline; }
    .part { font-weight: bold; text-transform: uppercase; margin: 4px 0 1px; }
    .info { border-collapse: collapse; margin-left: 18px; }
    .info td { padding: 0.5px 0; vertical-align: top; }
    .info .label { width: 200px; }
    .info .value { text-transform: uppercase; font-weight: bold; }
    .sign { display: inline-block; border-bottom: 1px solid #111827; width: 220px; height: 12px; }
    table.grid { width: 100%; border-collapse: collapse; margin-bottom: 3px; page-break-inside: avoid; }
    table.grid th, table.grid td { border: 1px solid #111827; padding: 1.5px 5px; vertical-align: top; }
    table.grid th { font-size: 10px; text-transform: uppercase; }
    .group td { font-weight: bold; text-transform: uppercase; }
    .num { text-align: center; width: 80px; }
    .total td { font-weight: bold; }
    .comments { text-align: justify; line-height: 1.35; white-space: pre-line; margin-left: 4px; }
    .signoff { width: 100%; margin-top: 8px; border-collapse: collapse; page-break-inside: avoid; }
    .signoff td { width: 50%; vertical-align: top; padding: 0 10px 0 0; }
    .signoff .line { border-bottom: 1px solid #111827; height: 16px; margin-top: 2px; }
    .signoff .who { font-weight: bold; padding-top: 2px; }
</style>
</head>
<body>
@php
    // $info: the trainee details frozen when the evaluation was submitted, or the current ones.
    // The supervisor assigned to this OJT; the account that submitted is only the fallback.
    $supervisorName = $info['supervisor_name'] ?: $evaluation->evaluator?->name;
    $address = $info['address'] ?? '';
    $from = $placement->start_date;
    $to = $placement->end_date ?? $placement->completed_at;
    $dates = $from ? ($to ? ($from->isSameYear($to) ? $from->format('F j').' - '.$to->format('F j, Y') : $from->format('F j, Y').' - '.$to->format('F j, Y')) : $from->format('F j, Y').' - present') : '';
@endphp

@include('ojt.partials.pdf-header', ['header' => $header])

<div class="titles">
    <div class="college">{{ $header['college_name'] }}</div>
    <div class="form">PERFORMANCE EVALUATION REPORT</div>
    <div>On-the-Job Training Program (PRACTICUM)</div>
    <div class="company">{{ $info['company'] }}</div>
    <div>Name of Company</div>
</div>

<div class="part">Part I. Trainee's Basic Information</div>
<table class="info">
    <tr><td class="label">Name</td><td class="value">: {{ $info['full_name'] }}</td></tr>
    <tr><td class="label">Student ID</td><td class="value">: {{ $info['student_id'] }}</td></tr>
    <tr><td class="label">Course</td><td class="value">: {{ $info['course'] }}</td></tr>
    <tr><td class="label">Address</td><td class="value">: {{ $address }}</td></tr>
    <tr><td class="label">Contact No</td><td class="value">: {{ $info['contact_number'] }}</td></tr>
    <tr><td class="label">Signature</td><td>: <span class="sign"></span></td></tr>
</table>

<div class="part">Part II. Training Details</div>
<table class="info">
    <tr><td class="label">Division / Department Assigned</td><td class="value">: {{ $evaluation->department_assigned }}</td></tr>
    <tr><td class="label">Field of Training Assigned</td><td class="value">: {{ $evaluation->field_of_training }}</td></tr>
    <tr><td class="label">Inclusive Dates of Training</td><td class="value">: {{ $dates }}</td></tr>
    <tr><td class="label">Total Number of Hours Rendered</td><td class="value">: {{ rtrim(rtrim(number_format($approvedHours, 2), '0'), '.') }} HOURS</td></tr>
    <tr><td class="label">Mentor Buddy / Supervisor</td><td class="value">: {{ $supervisorName }}</td></tr>
    <tr><td class="label">Designation / Job Title</td><td class="value">: {{ $evaluation->supervisor_designation }}</td></tr>
    <tr><td class="label">Signature</td><td>: <span class="sign"></span></td></tr>
</table>

<div class="part">Part III. Final Performance Evaluation</div>
@foreach(['Work Performance' => [\App\Models\Evaluation::WORK_FACTORS, 'work_scores', $evaluation->work_total], 'Personality Traits' => [\App\Models\Evaluation::TRAIT_FACTORS, 'trait_scores', $evaluation->trait_total]] as $groupLabel => [$factors, $column, $total])
    <table class="grid">
        <thead><tr><th>Job Factors</th><th class="num">Max Rating</th><th class="num">Actual Rating</th></tr></thead>
        <tbody>
            <tr class="group"><td colspan="3">{{ $groupLabel }}</td></tr>
            @foreach($factors as $key => $factor)
                <tr>
                    <td>{{ $loop->iteration }}. <strong>{{ $factor['label'] }}</strong>@if($column === 'work_scores') ({{ $factor['description'] }})@else {{ $factor['description'] }}@endif</td>
                    <td class="num">{{ $factor['points'] }}%</td>
                    <td class="num">{{ isset($evaluation->{$column}[$key]) ? $evaluation->{$column}[$key].'%' : '' }}</td>
                </tr>
            @endforeach
            <tr class="total"><td style="text-align: center;">TOTAL</td><td class="num">100%</td><td class="num">{{ $total }}%</td></tr>
        </tbody>
    </table>
@endforeach

<div class="part">Part IV. Final Comments / Recommendations:</div>
<div class="comments">{{ $evaluation->comments }}</div>

<table class="signoff">
    <tr>
        <td>
            Evaluated by:
            <div class="line"></div>
            <div class="who">{{ $supervisorName }}</div>
            <div>{{ $evaluation->supervisor_designation }}@if($evaluation->department_assigned), {{ $evaluation->department_assigned }}@endif</div>
            <div>Date: {{ $evaluation->submitted_at?->timezone('Asia/Manila')->format('F j, Y') }}</div>
        </td>
        <td>
            Certified True and Correct:
            <div class="line"></div>
            {{-- Someone else certified it (typed by the supervisor): shown as entered. Otherwise the assigned supervisor. --}}
            <div class="who">{{ $evaluation->certified_by_name ?: $supervisorName }}</div>
            <div>{{ $evaluation->certified_by_name ? $evaluation->certified_by_position : $evaluation->supervisor_designation }}, {{ $info['company'] }}</div>
        </td>
    </tr>
</table>
</body>
</html>
