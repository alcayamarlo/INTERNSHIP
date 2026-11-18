<?php

use App\Enums\CompanyInvitationStatus;
use App\Enums\UserRole;
use App\Mail\CompanyInvitationMail;
use App\Models\CompanyInvitation;
use App\Models\Coordinator;
use App\Models\Employer;
use App\Models\Institution;
use App\Jobs\RefreshIndustryCatalog;
use App\Models\User;
use App\Services\IndustryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

const IC_KEY = 'secret-industry-key-9f3a';

/** The titles the fake Oanor API serves: the 20 sectors, a few subsectors, 520 industry groups, a few industries. */
function icTitles(string $level, int $offset = 0, int $limit = 100): array
{
    $all = match ($level) {
        'subsector' => ['Crop Production', 'Animal Production and Aquaculture', 'Software Publishers'],
        'industry_group' => array_map(fn ($i) => "Test Industry Group {$i}", range(1, 520)),
        'industry' => ['API Only Industry', 'Software Publishers'],
        default => [], // national_industry: none
    };

    return array_slice($all, $offset, $limit);
}

function icFake(): void
{
    $titles = app(IndustryCatalog::class)->stored()['sectors'];
    $sectors = array_map(fn ($title, $i) => ['code' => (string) (11 + $i), 'title' => $title], $titles, array_keys($titles));

    Http::fake([
        'api.oanor.test/v1/naicsx/sectors-list' => Http::response(['data' => ['count' => 20, 'level' => 'sector', 'sectors' => $sectors], 'success' => true], 200),
        'api.oanor.test/v1/search*' => function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if (blank($query['q'] ?? null)) {
                return Http::response(['success' => false, 'message' => 'q is required, e.g. software'], 400); // as the real API does
            }
            $results = array_map(fn ($title) => ['code' => '1', 'level' => $query['level'], 'title' => $title], icTitles($query['level'], (int) $query['offset'], (int) $query['limit']));

            return Http::response(['data' => ['count' => count($results), 'total' => count(icTitles($query['level'], 0, 5000)), 'results' => $results], 'success' => true], 200);
        },
    ]);
}

function icWithKey(): void
{
    config([
        'services.industry.key' => IC_KEY,
        'services.industry.url' => 'https://api.oanor.test/v1/naicsx/sectors-list',
        'services.industry.search_url' => 'https://api.oanor.test/v1/search',
        'services.industry.pause_ms' => 0,
        'queue.default' => 'database', // a real queue: the reload runs in the background, not inside the page
    ]);
    Cache::flush();
}

it('has every NAICS title stored, so the dropdown is full without an API key', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    Http::fake();
    Queue::fake();

    $groups = app(IndustryCatalog::class)->groups();

    expect($groups['sectors'])->toHaveCount(20)->toContain('Manufacturing', 'Information', 'Health Care and Social Assistance')
        ->and($groups['more'])->toContain('Custom Computer Programming Services', 'Software Publishers', 'Full-Service Restaurants', 'Crop Production')
        ->and(count($groups['more']))->toBeGreaterThan(1300)
        // A to Z, no sector repeated, nothing longer than the limit.
        ->and($groups['more'])->toBe(collect($groups['more'])->sort(SORT_STRING | SORT_FLAG_CASE)->values()->all())
        ->and(array_intersect($groups['sectors'], $groups['more']))->toBe([])
        ->and(collect([...$groups['sectors'], ...$groups['more']])->max(fn ($title) => mb_strlen($title)))->toBeLessThanOrEqual(IndustryCatalog::MAX_TITLE);
    Http::assertNothingSent();
    Queue::assertNothingPushed();
});

it('loads every level from the API, 100 titles a call, with the key in a header, and keeps them for 8 days', function () {
    icWithKey();
    icFake();

    expect(app(IndustryCatalog::class)->refresh())->toBe('api');

    $groups = app(IndustryCatalog::class)->groups();
    expect($groups['more'])->toContain('API Only Industry', 'Test Industry Group 1', 'Test Industry Group 520', 'Crop Production')
        ->and(array_intersect($groups['sectors'], $groups['more']))->toBe([]);
    // 1 sectors call, then each level once per vowel: subsector 5 + industry groups 30 (6 pages x 5) + industry 5 + national industry 5.
    Http::assertSentCount(46);
    Http::assertSent(fn ($request) => str_contains($request->url(), 'q=u') && str_contains($request->url(), 'level=subsector'));
    Http::assertSent(fn ($request) => $request->hasHeader('x-oanor-key', IC_KEY) && $request->method() === 'GET');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'level=industry_group') && str_contains($request->url(), 'offset=500') && str_contains($request->url(), 'limit=100'));

    // Later page requests come from the cache: no more calls.
    app(IndustryCatalog::class)->groups();
    Http::assertSentCount(46);
    $this->travel(7)->days();
    expect(Cache::has('industry.catalog'))->toBeTrue();
    $this->travel(2)->days();
    expect(Cache::has('industry.catalog'))->toBeFalse();
});

it('falls back to the last list that worked, then to the stored list', function () {
    icWithKey();
    icFake();
    Queue::fake();
    app(IndustryCatalog::class)->refresh();
    $good = app(IndustryCatalog::class)->groups();
    expect($good['more'])->toContain('API Only Industry');

    // The 8-day copy is gone and the API is down: the last good list is used.
    Cache::forget('industry.catalog');
    Http::fake(['api.oanor.test/*' => Http::response('Server Error', 500)]);
    expect(app(IndustryCatalog::class)->groups())->toBe($good);

    // Never fetched anything: the stored list is used.
    icWithKey();
    expect(app(IndustryCatalog::class)->groups())->toBe(app(IndustryCatalog::class)->stored());
});

it('never calls the API during a page request: it queues one background reload and stops asking a dead API for a few minutes', function () {
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response('Server Error', 500)]);
    Queue::fake();

    foreach (range(1, 4) as $page) {
        expect(app(IndustryCatalog::class)->groups()['more'])->toContain('Software Publishers'); // the stored list meanwhile
    }
    Http::assertNothingSent();
    Queue::assertPushed(RefreshIndustryCatalog::class, 1); // one job, not one per page

    // The job runs and the API is down: it is left alone for 10 minutes.
    expect(app(IndustryCatalog::class)->refresh())->toContain('HTTP 500 for sectors');
    app(IndustryCatalog::class)->groups();
    Queue::assertPushed(RefreshIndustryCatalog::class, 1);

    $this->travel(11)->minutes();
    app(IndustryCatalog::class)->groups();
    Queue::assertPushed(RefreshIndustryCatalog::class, 2);
});

it('does nothing without a key, and refuses an answer that is not NAICS', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    Http::fake();
    Queue::fake();
    expect(app(IndustryCatalog::class)->refresh())->toBe('INDUSTRY_API_KEY is not set')
        ->and(app(IndustryCatalog::class)->groups())->toBe(app(IndustryCatalog::class)->stored());
    Http::assertNothingSent();
    Queue::assertNothingPushed();

    // Only a handful of titles back (an error page or a changed API): the list is not replaced.
    icWithKey();
    Http::fake(['api.oanor.test/*' => Http::response(['data' => ['sectors' => [['title' => 'Only One']], 'results' => []]], 200)]);
    expect(app(IndustryCatalog::class)->refresh())->toContain('did not return a usable list');
    expect(Cache::has('industry.catalog'))->toBeFalse();
});

it('refreshes the list with the artisan command, and the queued job does the same', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    $this->artisan('industries:refresh')->expectsOutputToContain('INDUSTRY_API_KEY is not set')->assertFailed();

    icWithKey();
    icFake();
    $this->artisan('industries:refresh')->expectsOutputToContain('reloaded from the API')->assertSuccessful();

    icWithKey();
    icFake();
    (new RefreshIndustryCatalog)->handle(app(IndustryCatalog::class));
    expect(app(IndustryCatalog::class)->groups()['more'])->toContain('API Only Industry');
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

it('offers every industry in a searchable dropdown with Other, loaded with integrity checks, and never exposes the key', function () {
    icWithKey();
    icFake();
    app(IndustryCatalog::class)->refresh();
    $token = icInvitation();

    $page = $this->get(route('company-invitations.show', $token))->assertOk();
    $page->assertSee('<option value="API Only Industry"', false)
        ->assertSee('<optgroup label="Main sectors">', false)
        ->assertSee('<optgroup label="All industries (A to Z)">', false)
        ->assertSee('<option value="Manufacturing"', false)
        ->assertSee('Other (please specify)')
        ->assertSee('data-industry-select', false)
        // The password rules are listed one by one.
        ->assertSee('data-pw-rules', false)->assertSee('A special character (! @ # $ %)')
        // Pinned Tom Select with Subresource Integrity (script and stylesheet).
        ->assertSee('tom-select/2.4.3/js/tom-select.complete.min.js', false)
        ->assertSee('integrity="sha384-A4TlP4zHVPP9oVIQ8pTVaV6MhvBkDx1sRpmEUfAlO5e7xpodrdr6uNmUV7alXAWW"', false)
        ->assertSee('integrity="sha384-9WZVlhSSWFURxa6PKH4XvmAFEXGmxb7RSnTJAppGXULTEWfQ2O/Ot/UU2ak0cAKX"', false)
        ->assertDontSee(IC_KEY);
});

it('accepts a listed industry or typed Other text on the invite form, and refuses anything else', function () {
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

it('stores the chosen industry on the invite form, whether a sector or a specific one', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    $token = icInvitation();

    $this->post(route('company-invitations.accept', $token), icForm(['industry' => 'Health Care and Social Assistance', 'industry_other' => 'ignored text']))->assertRedirect(route('home'));
    expect(Employer::where('company_name', 'Pick Co')->sole()->industry)->toBe('Health Care and Social Assistance');
});

it('accepts a specific industry such as a national industry title', function () {
    Cache::flush();
    config(['services.industry.key' => null]);
    $token = icInvitation();

    $this->post(route('company-invitations.accept', $token), icForm(['industry' => 'Custom Computer Programming Services']))->assertRedirect(route('home'));
    expect(Employer::where('company_name', 'Pick Co')->sole()->industry)->toBe('Custom Computer Programming Services');
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
