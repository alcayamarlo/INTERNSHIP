@extends('layouts.app')

@section('title', 'Your devices')

@section('content')
<div class="sb-dash">
<x-page-header title="Your devices" :subtitle="'Where your account is signed in. After '.$idleMinutes.' minutes without activity you are signed out automatically.'" />

<div class="card">
    @if($devices->isNotEmpty())
        <div class="table-responsive">
            <table class="table mb-0 sb-table-stack" data-devices>
                <thead>
                    <tr>
                        <th scope="col">Device</th>
                        <th scope="col">Last active (Philippine time)</th>
                        <th scope="col">IP code</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($devices as $device)
                        <tr>
                            <td class="sb-stack-block">
                                <span class="sb-row-title">{{ $device['device'] }}</span>
                                @if($device['current'])<x-status-badge status="active" label="This device" />@endif
                            </td>
                            <td data-label="Last active">{{ $device['last_active']->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                            <td data-label="IP code">{{ $device['ip'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-empty-state icon="bi-laptop" title="No sign-ins to show" :message="$tracked ? 'Only this browser is signed in.' : 'The device list needs the database session driver (SESSION_DRIVER=database).'" />
    @endif
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h6 mb-1">Sign out of all other devices</h2>
        <p class="text-muted small mb-3">Use this if you signed in on a shared or lost computer or phone. This device stays signed in. Changing your password does the same.</p>
        <form method="POST" action="{{ route('account.devices.destroy-others') }}" class="row g-2 align-items-end" style="max-width: 32rem">
            @csrf
            @method('DELETE')
            <div class="col-sm">
                <label class="form-label" for="devicesPassword">Your password</label>
                <input id="devicesPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-sm-auto">
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out other devices</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
