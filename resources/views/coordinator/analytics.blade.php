@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
<x-page-header title="Analytics" subtitle="Trends for the students in your institution." />

<div class="alert alert-info">
    <i class="bi bi-shield-check" aria-hidden="true"></i>
    These charts only include students you can access. Top required skills and active employers reflect the whole platform, since those don't depend on any one institution.
</div>

@include('analytics.charts', ['analyticsUrl' => route('analytics.charts')])
@endsection
