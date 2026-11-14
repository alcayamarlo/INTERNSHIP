<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Performance Appraisal Report</title>
<style>
    @page { margin: 28px 40px 36px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; }
    .titles { text-align: center; margin: 12px 0 10px; line-height: 1.35; }
    .titles .college { font-size: 12px; font-weight: bold; }
    .titles .form { font-size: 11.5px; font-weight: bold; }
    .titles .who { font-size: 11px; }
    .info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .info td { padding: 1px 0; vertical-align: top; }
    .info .label { width: 105px; }
    .info .value { padding-right: 10px; }
    .info .period { font-weight: bold; text-transform: uppercase; }
    .instruction { margin: 6px 0 8px; text-align: justify; line-height: 1.35; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { border: 1px solid #111827; padding: 5px 6px; }
    table.grid th { font-size: 10px; text-transform: uppercase; }
    .factor-name { font-weight: bold; text-transform: uppercase; }
    .num { text-align: center; width: 62px; }
    .total td { font-weight: bold; }
    .remarks-title { text-align: center; font-weight: bold; text-transform: uppercase; }
    .comment { height: 120px; vertical-align: top; text-align: justify; line-height: 1.35; white-space: pre-line; }
    .certify { width: 100%; margin-top: 26px; border-collapse: collapse; }
    .certify td { vertical-align: bottom; padding: 0; }
    .sign-line { border-bottom: 1px solid #111827; text-align: center; height: 18px; width: 220px; }
    .sign-name { text-align: center; font-size: 9px; padding-top: 2px; }
</style>
</head>
<body>
@php
    use App\Models\Appraisal;
    $student = $placement?->student;
    $supervisorName = $appraisal->supervisor?->name ?? $placement?->employer?->contact_person;
    $columns = ['self', 'supervisor', 'adviser'];
@endphp

@include('ojt.partials.pdf-header', ['header' => $header])

<div class="titles">
    <div class="college">{{ $header['college_name'] }}</div>
    <div class="form">PERFORMANCE APPRAISAL REPORT</div>
    <div class="who">OJT / PRACTICUM TRAINEE</div>
</div>

<table class="info">
    <tr>
        <td class="label">Name of Trainee</td><td class="value">: {{ $student?->user?->name }}</td>
        <td class="label">Period of Evaluation</td><td class="value">: <span class="period">{{ $appraisal->period_label }}</span></td>
    </tr>
    <tr>
        <td class="label">Company / Agency</td><td class="value">: {{ $placement?->employer?->company_name }}</td>
        <td class="label">Inclusive Dates</td><td class="value">: {{ $appraisal->inclusiveDates() }}</td>
    </tr>
    <tr>
        <td class="label">Supervisor's Name</td><td class="value">: {{ $supervisorName }}</td>
        <td class="label">Designation</td><td class="value">: {{ $placement?->getAttribute('department') }}</td>
    </tr>
</table>

<p class="instruction"><strong>Instruction:</strong> {{ Appraisal::INSTRUCTION }}</p>

<table class="grid">
    <thead>
        <tr>
            <th>Factors</th>
            <th class="num">Points (%)</th>
            <th class="num">Self</th>
            <th class="num">Supervisor</th>
            <th class="num">Adviser</th>
        </tr>
    </thead>
    <tbody>
        @foreach(Appraisal::FACTORS as $key => $factor)
            <tr>
                <td><div class="factor-name">{{ $factor['label'] }}</div>{{ $factor['description'] }}</td>
                <td class="num">{{ $factor['points'] }}%</td>
                @foreach($columns as $column)
                    @php $value = $appraisal->{$column.'_scores'}[$key] ?? null; @endphp
                    <td class="num">{{ $value === null ? '' : $value.'%' }}</td>
                @endforeach
            </tr>
        @endforeach
        <tr class="total">
            <td style="text-align: center;">TOTAL</td>
            <td class="num">{{ Appraisal::maxTotal() }}%</td>
            @foreach($columns as $column)
                <td class="num">{{ $appraisal->{$column.'_total'} === null ? '' : $appraisal->{$column.'_total'}.'%' }}</td>
            @endforeach
        </tr>
        <tr>
            <td colspan="5" class="remarks-title">Use the space below for your remarks: (strengths / areas of improvement / suggestions)</td>
        </tr>
    </tbody>
</table>
<table class="grid" style="border-top: 0;">
    <tr>
        <th style="width: 50%; border-top: 0;">Supervisor's Comment</th>
        <th style="width: 50%; border-top: 0;">Student's Comment</th>
    </tr>
    <tr>
        <td class="comment">{{ $appraisal->supervisor_comment }}</td>
        <td class="comment">{{ $appraisal->student_comment }}</td>
    </tr>
</table>

<table class="certify">
    <tr>
        <td style="width: 140px;">Certified True and Correct&nbsp;&nbsp;:</td>
        <td style="width: 230px;"><div class="sign-line"></div><div class="sign-name">{{ $supervisorName }}</div></td>
        <td style="width: 40px;"></td>
        <td>Date&nbsp;&nbsp;: {{ $appraisal->supervisor_submitted_at?->timezone('Asia/Manila')->format('F j, Y') }}</td>
    </tr>
</table>
</body>
</html>
