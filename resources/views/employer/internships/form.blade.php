@extends('layouts.app')

@section('title', $internship->exists ? 'Edit Internship' : 'New Internship')

@section('content')
<x-page-header :title="$internship->exists ? 'Edit Internship' : 'Post New Internship'" subtitle="Describe the role and the competencies you are looking for.">
    <a href="{{ route('employer.internships.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Internships</a>
</x-page-header>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $internship->exists ? route('employer.internships.update', $internship) : route('employer.internships.store') }}">
            @csrf
            @if($internship->exists) @method('PUT') @endif

            @include('employer.internships.partials.fields')

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> {{ $internship->exists ? 'Update' : 'Post' }} Internship</button>
            </div>
        </form>
    </div>
</div>
@endsection
