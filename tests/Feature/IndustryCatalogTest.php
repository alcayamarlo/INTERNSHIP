<?php

use App\Enums\CompanyInvitationStatus;
use App\Enums\UserRole;
use App\Mail\CompanyInvitationMail;
use App\Models\CompanyInvitation;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Models\User;
use App\Services\IndustryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

const IC_KEY = 'secret-industry-key-9f3a';

/** The answer the Oanor sectors-list endpoint gives, with a few of the 20 sectors. */
function icApiResponse(?array $titles = null): array
{
    $titles ??= ['Agriculture, Forestry, Fishing and Hunting', 'Manufacturing', 'Information', 'Finance and Insurance', 'Educational Services',
        'Health Care and Social Assistance', 'Construction', 'Retail Trade', 'Utilities', 'Public Administration', 'API Only Sector'];

    return ['data' => ['count' => count($titles), 'level' => 'sector', 'sectors' => collect($titles)->map(fn ($t, $i) => ['code' => (string) (11 + $i), 'title' => $t])->all()], 'status' => 'ok', 'success' => true];
}

function icWithKey(): void
{
    config(['services.industry.key' => IC_KEY, 'services.industry.url' => 'https://api.oanor.test/v1/naicsx/sectors-list']);
    Cache::flush();
}

it('loads the sector titles from the API with the key in a header, and keeps them for 24 hours', function () {
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response(icApiResponse(), 200)]);

    $first = app(IndustryCatalog::class)->sectors();
    $second = app(IndustryCatalog::class)->sectors();

    expect($first)->toContain('API Only Sector', 'Manufacturing')->and($second)->toBe($first);
    Http::assertSentCount(1); // the second call came from the cache
    Http::assertSent(fn ($request) => $request->hasHeader('x-oanor-key', IC_KEY) && $request->method() === 'GET');
});

it('falls back to the last list that worked, then to the stored list', function () {
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::sequence()->push(icApiResponse(), 200)->pushStatus(500)]);

    $good = app(IndustryCatalog::class)->sectors();
    expect($good)->toContain('API Only Sector');

    // The 24-hour copy is gone and the API is down: the last good list is used.
    Cache::forget('industry.sectors');
    expect(app(IndustryCatalog::class)->sectors())->toBe($good);

    // Never fetched anything and the API is down: the stored NAICS list is used.
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response('Server Error', 500)]);
    expect(app(IndustryCatalog::class)->sectors())->toBe(config('industries.sectors'))->toHaveCount(20);
});

it('stops asking a dead API for a few minutes, so pages never wait on it again and again', function () {
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response('Server Error', 500)]);

    foreach (range(1, 4) as $page) {
        app(IndustryCatalog::class)->sectors();
    }
    Http::assertSentCount(1); // asked once, then left alone

    // After the pause it tries again.
    $this->travel(11)->minutes();
    app(IndustryCatalog::class)->sectors();
    Http::assertSentCount(2);
});

it('does not call the API without a key, or when it answers with something unusable', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    Http::fake();
    expect(app(IndustryCatalog::class)->sectors())->toBe(config('industries.sectors'));
    Http::assertNothingSent();

    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response(icApiResponse(['Only One']), 200)]); // not the 20 sectors
    expect(app(IndustryCatalog::class)->sectors())->toBe(config('industries.sectors'));
});

it('refreshes the list with the artisan command', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    $this->artisan('industries:refresh')->expectsOutputToContain('INDUSTRY_API_KEY is not set')->assertFailed();

    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response(icApiResponse(), 200)]);
    $this->artisan('industries:refresh')->expectsOutputToContain('reloaded from the API (11 sectors)')->assertSuccessful();
});

function icInvitation(): string
{
    Mail::fake();
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    Coordinator::create(['user_id' => $coordinator->id, 'institution_id' => Institution::create(['name' => 'Industry College'])->id]);
    test()->actingAs($coordinator)->post(route('coordinator.companies.invitations.store'), [
        'company_name' => 'Pick Co', 'address' => 'Cebu City', 'email' => 'pick@co.test', 'phone' => '09171234567',
    ])->assertSessionHasNoErrors();
    $url = null;
    Mail::assertSent(CompanyInvitationMail::class, function ($mail) use (&$url) {
        $url = $mail->url;

        return true;
    });
    auth()->logout();

    return basename(parse_url($url, PHP_URL_PATH));
}

function icForm(array $overrides = []): array
{
    return ['contact_person' => 'Ian Olmoguez', 'password' => 'Secret#2026', 'password_confirmation' => 'Secret#2026', ...$overrides];
}

it('offers the sectors in a searchable dropdown with Other, loaded with integrity checks, and never exposes the key', function () {
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response(icApiResponse(), 200)]);
    $token = icInvitation();

    $page = $this->get(route('company-invitations.show', $token))->assertOk();
    $page->assertSee('<option value="API Only Sector"', false)
        ->assertSee('Other (please specify)')
        ->assertSee('data-industry-select', false)
        // Pinned Tom Select with Subresource Integrity (script and stylesheet).
        ->assertSee('tom-select/2.4.3/js/tom-select.complete.min.js', false)
        ->assertSee('integrity="sha384-A4TlP4zHVPP9oVIQ8pTVaV6MhvBkDx1sRpmEUfAlO5e7xpodrdr6uNmUV7alXAWW"', false)
        ->assertSee('integrity="sha384-9WZVlhSSWFURxa6PKH4XvmAFEXGmxb7RSnTJAppGXULTEWfQ2O/Ot/UU2ak0cAKX"', false)
        ->assertDontSee(IC_KEY);
});

it('accepts a listed sector or typed Other text on the invite form, and refuses anything else', function () {
    Cache::flush();
    config(['services.industry.key' => null]); // the stored list is in use
    $token = icInvitation();

    $this->post(route('company-invitations.accept', $token), icForm())->assertSessionHasErrors(['industry' => 'Choose your industry.']);
    $this->post(route('company-invitations.accept', $token), icForm(['industry' => 'Space Mining Unlimited']))->assertSessionHasErrors(['industry' => 'Choose an industry from the list.']);
    $this->post(route('company-invitations.accept', $token), icForm(['industry' => IndustryCatalog::OTHER]))->assertSessionHasErrors(['industry_other' => 'Type your industry.']);
    $this->post(route('company-invitations.accept', $token), icForm(['industry' => IndustryCatalog::OTHER, 'industry_other' => str_repeat('x', 101)]))->assertSessionHasErrors('industry_other');
    expect(CompanyInvitation::sole()->status)->toBe(CompanyInvitationStatus::Sent);

    $this->post(route('company-invitations.accept', $token), icForm(['industry' => IndustryCatalog::OTHER, 'industry_other' => '  Call Center Outsourcing  ']))->assertRedirect(route('home'));
    expect(Employer::where('company_name', 'Pick Co')->sole()->industry)->toBe('Call Center Outsourcing');
});

it('stores the chosen sector on the invite form', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    $token = icInvitation();

    $this->post(route('company-invitations.accept', $token), icForm(['industry' => 'Health Care and Social Assistance', 'industry_other' => 'ignored text']))->assertRedirect(route('home'));
    expect(Employer::where('company_name', 'Pick Co')->sole()->industry)->toBe('Health Care and Social Assistance');
});

it('uses the same dropdown on the company profile, and keeps an older free-text industry under Other', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    $user = User::factory()->create(['role' => UserRole::Employer]);
    $employer = Employer::create(['user_id' => $user->id, 'company_name' => 'Old Co', 'industry' => 'Software stuff', 'contact_person' => 'Ian']);
    $this->actingAs($user);

    // The older value is not lost: "Other" is selected and the text box holds it.
    $this->get(route('employer.profile.edit'))->assertOk()
        ->assertSee('<option value="'.IndustryCatalog::OTHER.'" selected>', false)
        ->assertSee('value="Software stuff"', false)
        ->assertSee('data-industry-select', false);

    $update = fn (array $industry) => $this->put(route('employer.profile.update'), ['company_name' => 'Old Co', ...$industry]);
    $update(['industry' => 'Information'])->assertSessionHasNoErrors();
    expect($employer->fresh()->industry)->toBe('Information');
    $update(['industry' => 'Made Up Industry'])->assertSessionHasErrors('industry');
    $update(['industry' => IndustryCatalog::OTHER, 'industry_other' => 'Software stuff'])->assertSessionHasNoErrors();
    expect($employer->fresh()->industry)->toBe('Software stuff');
    // The listed one is selected when it is the saved value.
    $employer->update(['industry' => 'Information']);
    $this->get(route('employer.profile.edit'))->assertSee('<option value="Information" selected>', false);
});
