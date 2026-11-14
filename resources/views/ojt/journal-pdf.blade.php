<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Activity Narrative Report</title>
<style>
    @page { margin: 28px 40px 36px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; }
    .titles { text-align: center; margin: 12px 0 10px; line-height: 1.35; }
    .titles .college { font-size: 12px; font-weight: bold; }
    .titles .form { font-size: 11.5px; font-weight: bold; text-transform: uppercase; }
    .titles .who { font-size: 11px; }
    .info { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .info td { padding: 1px 0; vertical-align: top; }
    .info .label { width: 105px; }
    .info .value { padding-right: 10px; }
    .info .period { font-weight: bold; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid th, table.grid td { border: 1px solid #111827; padding: 4px 6px; vertical-align: top; }
    table.grid th { font-size: 10px; text-transform: uppercase; text-align: center; }
    .nowrap { white-space: nowrap; }
    .center { text-align: center; }
    .box { width: 100%; border-collapse: collapse; margin-top: 10px; }
    .box th { border: 1px solid #111827; border-bottom: 0; text-align: left; padding: 4px 6px; font-size: 10px; text-transform: uppercase; }
    .box td { border: 1px solid #111827; padding: 6px 8px; height: 90px; vertical-align: top; text-align: justify; line-height: 1.45; white-space: pre-line; }
    .certify { width: 100%; margin-top: 30px; border-collapse: collapse; }
    .certify td { vertical-align: bottom; padding: 0; }
    .sign-line { border-bottom: 1px solid #111827; height: 18px; width: 220px; }
    .sign-name { text-align: center; font-size: 9px; padding-top: 2px; width: 220px; }
</style>
</head>
<body>
@php
    $student = $placement?->student;
    $supervisorName = $entry->reviewer?->name ?? $placement?->employer?->contact_person;
@endphp

@include('ojt.partials.pdf-header', ['header' => $header])

<div class="titles">
    <div class="college">{{ $header['college_name'] }}</div>
    <div class="form">{{ $entry->week_number ? 'Week '.$entry->week_number.' – ' : '' }}Activity Narrative Report</div>
    <div class="who">OJT / PRACTICUM TRAINEE</div>
</div>

<table class="info">
    <tr>
        <td class="label">Name of Trainee</td><td class="value">: {{ $student?->user?->name }}</td>
        <td class="label">Period of Evaluation</td><td class="value">: <span class="period">{{ $periodLabel }}</span></td>
    </tr>
    <tr>
        <td class="label">Company / Agency</td><td class="value">: {{ $placement?->employer?->company_name }}</td>
        <td class="label">Inclusive Dates</td><td class="value">: {{ $entry->inclusiveDates() }}</td>
    </tr>
    <tr>
        <td class="label">Supervisor's Name</td><td class="value">: {{ $supervisorName }}</td>
        <td class="label">Designation</td><td class="value">: {{ $placement?->department }}</td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr><th style="width: 22%;">Dates</th><th style="width: 8%;">Day</th><th>Activity (+ies)</th><th style="width: 18%;">Total Hours Rendered</th></tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                <td class="nowrap">{{ $row['date_label'] }}</td>
                <td class="center">{{ $row['day'] }}</td>
                <td>{{ $row['activity'] }}</td>
                <td class="center nowrap">{{ $row['hours_label'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@foreach(['Difficulties Encountered' => $entry->challenges, 'Actions Taken' => $entry->actions_taken, 'Learning Insights' => $entry->learnings] as $heading => $text)
    <table class="box">
        <tr><th>{{ $heading }}</th></tr>
        <tr><td>{{ $text }}</td></tr>
    </table>
@endforeach

<table class="certify">
    <tr>
        <td style="width: 140px;">Certified True and Correct&nbsp;&nbsp;:</td>
        <td style="width: 230px;"><div class="sign-line"></div><div class="sign-name">{{ $entry->isCertified() ? $supervisorName : '' }}</div></td>
        <td style="width: 40px;"></td>
        <td>Date&nbsp;&nbsp;: {{ $entry->isCertified() ? $entry->reviewed_at?->timezone('Asia/Manila')->format('F j, Y') : '' }}</td>
    </tr>
</table>
</body>
</html>
