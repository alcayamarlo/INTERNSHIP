@extends('layouts.app')

@section('title', 'Messages')

@section('content')
<div class="msgr msgr--index">
    @include('messages.partials.sidebar')

    <section class="msgr-chat msgr-chat--empty" aria-label="Chat">
        <x-empty-state icon="bi-chat-dots" title="Select a conversation" message="Choose a conversation or contact to start messaging." />
    </section>
</div>
@endsection
