<?php

use Illuminate\Support\Facades\Artisan;

it('skips gracefully on sqlite (the test database driver) instead of failing', function () {
    Artisan::call('db:health');

    expect(Artisan::output())->toContain('only applies to MySQL');
});
