@extends('layouts.app')

@section('title', 'Details needed')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-2"><i class="bi bi-person-exclamation text-warning" aria-hidden="true"></i> This {{ $document }} can't be produced yet</h1>
                <p class="mb-3" role="alert">{{ $message }}</p>
                <p class="small text-muted">Forms are never printed with blank personal details. Fill these in and try again:</p>
                <ul class="mb-4">
                    @foreach($missing as $label)
                        <li>{{ $label }}</li>
                    @endforeach
                </ul>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($links as $link)
                        <a href="{{ $link['url'] }}" class="btn btn-primary">{{ $link['label'] }}</a>
                    @endforeach
                    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
