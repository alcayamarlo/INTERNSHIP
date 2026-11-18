<?php

namespace App\Services;

use App\Jobs\RefreshIndustryCatalog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The industries a company can choose on the invite form and its profile: every NAICS 2022 title, from the
 * 20 main sectors down to the national industries (about 1,400 different titles).
 *
 * The complete list is stored with the app (database/data/naics-2022.json, from the U.S. Census Bureau), so the
 * dropdown is full even without an API key. When INDUSTRY_API_KEY is set, the server reloads the list from the
 * Oanor NAICS API in the background (a queued job, the weekly schedule, or `php artisan industries:refresh`),
 * never during a page request, and keeps it for 8 days. The key stays in .env and is never sent to the browser.
 * The last list that worked is the first fallback, the stored list the second. When the API fails the server
 * stops asking for a few minutes. "Other (please specify)" lets a company enter an industry that is not listed.
 */
class IndustryCatalog
{
    /** The dropdown value for "Other (please specify)". */
    public const OTHER = '__other__';

    public const OTHER_LABEL = 'Other (please specify)';

    /** The longest title offered; the longest one in NAICS 2022 is 118 characters. */
    public const MAX_TITLE = 120;

    private const CACHE = 'industry.catalog';

    private const LAST_GOOD = 'industry.catalog.last_good';

    private const BACKOFF = 'industry.catalog.backoff';

    private const QUEUED = 'industry.catalog.queued';

    /** Below this many different titles the API answer is an error page or a changed API, not NAICS. */
    private const MIN_TITLES = 500;

    /** The API's levels after the sectors, from the broadest to the most specific. */
    private const LEVELS = ['subsector', 'industry_group', 'industry', 'national_industry'];

    /**
     * The API has no "list everything" call: /search needs a word `q` and matches it inside the title. Every title
     * has a vowel, so searching each vowel and merging the answers returns every title.
     */
    private const QUERIES = ['a', 'e', 'i', 'o', 'u'];

    /** The most the API returns in one call, and the furthest it can page. */
    private const PAGE = 100;

    private const MAX_OFFSET = 5000;

    private ?string $failure = null;

    /** @var array{sectors: list<string>, more: list<string>}|null */
    private static ?array $stored = null;

    /**
     * The dropdown, in two groups: the 20 main sectors, then every other title from A to Z.
     *
     * @return array{sectors: list<string>, more: list<string>}
     */
    public function groups(): array
    {
        if (is_array($cached = Cache::get(self::CACHE)) && ($cached['sectors'] ?? []) !== []) {
            return $cached;
        }

        // Nothing fresh: show what we have now and reload in the background, so no page waits on the API.
        // With the "sync" queue a job would run inside this request (about 3 minutes), so then only the weekly
        // schedule and `php artisan industries:refresh` reload the list.
        if ($this->configured() && config('queue.default') !== 'sync' && ! Cache::has(self::BACKOFF) && Cache::add(self::QUEUED, true, now()->addMinutes(15))) {
            RefreshIndustryCatalog::dispatch();
        }

        $lastGood = Cache::get(self::LAST_GOOD);

        return is_array($lastGood) && ($lastGood['sectors'] ?? []) !== [] ? $lastGood : $this->stored();
    }

    /** @return list<string> every title offered, the main sectors first */
    public function all(): array
    {
        $groups = $this->groups();

        return [...$groups['sectors'], ...$groups['more']];
    }

    /** @return array{sectors: list<string>, more: list<string>} the list stored with the app */
    public function stored(): array
    {
        if (self::$stored !== null) {
            return self::$stored;
        }

        $rows = json_decode((string) @file_get_contents(config('industries.file')), true);
        $sectors = [];
        $others = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            [$code, $title] = [(string) ($row[0] ?? ''), (string) ($row[1] ?? '')];
            // The 20 sectors have 2-digit codes (or a range such as 31-33).
            if (strlen($code) === 2 || str_contains($code, '-')) {
                $sectors[] = $title;
            } else {
                $others[] = $title;
            }
        }

        return self::$stored = $this->shape($sectors, $others);
    }

    /** Everything a submitted value may be: the current list, the stored list, or "Other". */
    public function acceptedChoices(): array
    {
        $stored = $this->stored();

        return array_values(array_unique([...$this->all(), ...$stored['sectors'], ...$stored['more'], self::OTHER]));
    }

    /** Whether a stored industry is one of the listed titles (an older free-text value is not). */
    public function isListed(?string $industry): bool
    {
        return filled($industry) && in_array($industry, $this->acceptedChoices(), true) && $industry !== self::OTHER;
    }

    /** Validation rules for the dropdown value and the "Other" text. */
    public function rules(): array
    {
        return [
            'industry' => ['required', 'string', \Illuminate\Validation\Rule::in($this->acceptedChoices())],
            'industry_other' => ['nullable', 'string', 'max:100', 'required_if:industry,'.self::OTHER],
        ];
    }

    public function messages(): array
    {
        return [
            'industry.required' => 'Choose your industry.',
            'industry.in' => 'Choose an industry from the list.',
            'industry_other.required_if' => 'Type your industry.',
        ];
    }

    /** The text to store for validated input: the chosen title, or what was typed for "Other". */
    public function resolve(array $validated): string
    {
        return $validated['industry'] === self::OTHER
            ? trim((string) $validated['industry_other'])
            : $validated['industry'];
    }

    /**
     * Reloads the whole list from the API now (about 115 calls, so about 3 minutes). Run from the command, the
     * schedule or the queue, never from a page. @return string "api", or why it could not
     */
    public function refresh(): string
    {
        Cache::forget(self::BACKOFF);
        if (! $this->configured()) {
            Cache::forget(self::QUEUED);

            return 'INDUSTRY_API_KEY is not set';
        }

        $fresh = $this->fetch();
        Cache::forget(self::QUEUED);
        if (! $fresh) {
            Cache::put(self::BACKOFF, true, now()->addMinutes(10));

            return 'the API did not return a usable list'.($this->failure ? " ({$this->failure})" : '');
        }

        Cache::put(self::CACHE, $fresh, now()->addDays(8));
        Cache::forever(self::LAST_GOOD, $fresh);

        return 'api';
    }

    private function configured(): bool
    {
        return filled(config('services.industry.key')) && filled(config('services.industry.url'));
    }

    /**
     * The sectors, then each level in pages of 100. null when any call fails or the answer is not NAICS.
     *
     * @return array{sectors: list<string>, more: list<string>}|null
     */
    private function fetch(): ?array
    {
        $this->failure = null;
        try {
            $sectors = $this->titles($this->call(config('services.industry.url'), [], 'sectors'), 'data.sectors');
            if (count($sectors) < 10) {
                $this->failure ??= 'the sector list was too short';

                return null;
            }

            $others = [];
            foreach (self::LEVELS as $level) {
                foreach (self::QUERIES as $word) {
                    for ($offset = 0; $offset < self::MAX_OFFSET; $offset += self::PAGE) {
                        $query = ['q' => $word, 'level' => $level, 'limit' => self::PAGE, 'offset' => $offset];
                        $response = $this->call(config('services.industry.search_url'), $query, $level);
                        array_push($others, ...$this->titles($response, 'data.results'));

                        $total = (int) $response->json('data.total', 0);
                        if (count((array) $response->json('data.results', [])) < self::PAGE || ($total > 0 && $offset + self::PAGE >= $total)) {
                            break;
                        }
                    }
                }
            }
        } catch (\UnexpectedValueException) {
            return null; // a call was refused or failed: $this->failure says which
        } catch (\Throwable $exception) {
            report($exception);
            $this->failure ??= 'the API could not be reached';

            return null;
        }

        $shaped = $this->shape($sectors, $others);
        if (count($shaped['sectors']) + count($shaped['more']) < self::MIN_TITLES) {
            $this->failure ??= 'too few industries came back';

            return null;
        }

        return $shaped;
    }

    /** One API call, paused a little first so the free plan's 2 calls a second are never passed. */
    private function call(string $url, array $query, string $what): \Illuminate\Http\Client\Response
    {
        $pause = (int) config('services.industry.pause_ms', 600);
        if ($pause > 0) {
            usleep($pause * 1000);
        }

        $response = Http::timeout(8)->retry(1, 300, throw: false)
            ->withHeaders(['x-oanor-key' => (string) config('services.industry.key'), 'Accept' => 'application/json'])
            ->get($url, $query);

        if (! $response->successful()) {
            $this->failure = "HTTP {$response->status()} for {$what}";
            throw new \UnexpectedValueException($this->failure);
        }

        return $response;
    }

    /** @return list<string> */
    private function titles(?\Illuminate\Http\Client\Response $response, string $path): array
    {
        return collect($response?->json($path, []))
            ->map(fn ($row) => is_array($row) ? trim((string) ($row['title'] ?? '')) : '')
            ->filter(fn (string $title) => $title !== '')
            ->values()
            ->all();
    }

    /**
     * The dropdown shape: the sectors in the order given, and every other distinct title from A to Z.
     * Titles that are too long, or that repeat a sector, are left out.
     *
     * @param  list<string>  $sectors
     * @param  list<string>  $others
     * @return array{sectors: list<string>, more: list<string>}
     */
    private function shape(array $sectors, array $others): array
    {
        $fits = fn (string $title) => $title !== '' && mb_strlen($title) <= self::MAX_TITLE;
        $sectors = array_values(array_unique(array_filter($sectors, $fits)));
        $more = array_values(array_diff(array_unique(array_filter($others, $fits)), $sectors));
        sort($more, SORT_STRING | SORT_FLAG_CASE);

        return ['sectors' => $sectors, 'more' => $more];
    }
}
