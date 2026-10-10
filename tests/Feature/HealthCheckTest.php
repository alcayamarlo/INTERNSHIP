<?php

use Illuminate\Support\Facades\DB;

it('does not disclose health check exception details', function () {
    DB::shouldReceive('connection')
        ->once()
        ->andThrow(new RuntimeException('database password must remain private'));

    $this->get('/health')
        ->assertServiceUnavailable()
        ->assertJsonPath('checks.database', 'unavailable')
        ->assertDontSee('database password must remain private');
});