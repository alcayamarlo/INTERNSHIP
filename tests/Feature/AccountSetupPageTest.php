<?php

use App\Models\User;

it('renders the front-end account setup flow for a signed-in user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('account-setup'))
        ->assertOk()
        ->assertSee('Add your', false)
        ->assertSee('data-setup-step="2"', false)
        ->assertSee('sendVerificationCode', false);
});

it('sends guests to login', function () {
    $this->get(route('account-setup'))->assertRedirect(route('login'));
});
