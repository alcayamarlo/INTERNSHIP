<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Activity Narrative Report</title>
<style>
    @page { size: letter portrait; margin: 30px 44px 30px; }
    body { font-family: Helvetica, Arial, sans-serif; font-size: {{ $fontSize }}px; color: #000; }
    .titles { text-align: center; margin: 10px 0 9px; line-height: 1.35; }
    .titles .college, .titles .form { font-weight: bold; font-size: {{ $fontSize + 1.5 }}px; text-transform: uppercase; }
    .titles .who { font-size: {{ $fontSize + 0.5 }}px; }
    .info { width: 100%; border-collapse: collapse; margin-bottom: 9px; }
    .info td { padding: 2px 0; vertical-align: bottom; }
    .info .label { white-space: nowrap; width: 1%; padding-right: 3px; }
    .info .colon { width: 8px; }
    .info .value { border-bottom: 1px solid #000; padding-left: 3px; }
    .info .gap { width: 22px; }
    .info .period { font-weight: bold; }
    table.grid { width: 100%; border-collapse: collapse; border: 2px solid #000; }
    table.grid th, table.grid td { border: 1px solid #000; padding: 3px 5px; vertical-align: top; }
    table.grid th { font-weight: bold; text-align: center; vertical-align: middle; font-size: {{ $fontSize - 0.5 }}px; }
    table.grid td { height: 22px; }
    .nowrap { white-space: nowrap; }
    .center { text-align: center; }
    .box { width: 100%; border-collapse: collapse; margin-top: 9px; border: 1.5px solid #000; }
    .box th { background: #e5e7eb; border-bottom: 1px solid #000; text-align: left; padding: 3px 6px; font-weight: bold; }
    .box td { padding: 5px 7px; height: 72px; vertical-align: top; text-align: justify; line-height: 1.4; white-space: pre-line; }
    .certify { width: 100%; margin-top: 26px; border-collapse: collapse; }
    .certify td { vertical-align: bottom; padding: 0; }
    .sign-line { border-bottom: 1px solid #000; height: 18px; }
    .sign-name { text-align: center; padding-top: 2px; font-weight: bold; height: 14px; }
    .date-line { border-bottom: 1px solid #000; padding-left: 3px; height: 14px; }
</style>
</head>
<body>
@include('ojt.partials.pdf-header', ['header' => \App\Support\OjtFormHeader::data()])

<div class="titles">
    <div class="college">{{ config('ojt_forms.college_name') }}</div>
    <div class="form">{{ $header['week_no'] ? 'Week '.$header['week_no'].' – ' : '' }}Activity Narrative Report</div>
    <div class="who">OJT / PRACTICUM TRAINEE</div>
</div>

<table class="info">
    <tr>
        <td class="label">Name of Trainee</td><td class="colon">:</td><td class="value">{{ $header['full_name'] }}</td>
        <td class="gap"></td>
        <td class="label">Period of Evaluation</td><td class="colon">:</td><td class="value period">{{ $header['period_of_evaluation'] }}</td>
    </tr>
    <tr>
        <td class="label">Company / Agency</td><td class="colon">:</td><td class="value">{{ $header['company'] }}</td>
        <td class="gap"></td>
        <td class="label">Inclusive Dates</td><td class="colon">:</td><td class="value">{{ $header['inclusive_dates'] }}</td>
    </tr>
    <tr>
        <td class="label">Supervisor's Name</td><td class="colon">:</td><td class="value">{{ $header['supervisor_name'] }}</td>
        <td class="gap"></td>
        <td class="label">Designation</td><td class="colon">:</td><td class="value">{{ $header['designation'] }}</td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th style="width: 20%;">DATES</th>
            <th style="width: 8%;">DAY</th>
            <th>ACTIVITY (+IES)</th>
            <th style="width: 17%;">TOTAL HOURS RENDERED</th>
        </tr>
    </thead>
    <tbody>
        @foreach(array_slice($rows, 0, 5) as $row)
            @php
                // Blank when the day has no time record; "-" only for a record without hours.
                $hasRecord = ($row['minutes'] ?? 0) > 0 || trim((string) ($row['activity'] ?? '')) !== '';
                $hours = ($row['minutes'] ?? 0) > 0 ? $row['hours_label'] : ($hasRecord ? '-' : '');
            @endphp
            <tr>
                <td class="nowrap">{{ $row['date_label'] }}</td>
                <td class="center">{{ $row['day'] }}</td>
                <td>{{ $row['activity'] }}</td>
                <td class="center nowrap">{{ $hours }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

@foreach(['DIFFICULTIES ENCOUNTERED' => $entry->challenges, 'ACTIONS TAKEN' => $entry->actions_taken, 'LEARNING INSIGHTS' => $entry->learnings] as $heading => $text)
    <table class="box">
        <tr><th>{{ $heading }}</th></tr>
        <tr><td>{{ trim((string) $text) }}</td></tr>
    </table>
@endforeach

{{-- The labels sit level with the lines; the supervisor's printed name goes under the signature line. --}}
<table class="certify">
    <tr>
        <td style="width: 165px;">Certified True and Correct&nbsp;&nbsp;:</td>
        <td style="width: 230px;"><div class="sign-line"></div></td>
        <td style="width: 34px;"></td>
        <td style="width: 44px;">Date&nbsp;&nbsp;:</td>
        <td><div class="date-line">{{ $certifiedOn }}</div></td>
    </tr>
    <tr>
        <td></td>
        <td><div class="sign-name">{{ $certifiedBy }}</div></td>
        <td colspan="3"></td>
    </tr>
</table>
</body>
</html>
