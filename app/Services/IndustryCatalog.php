<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The industries a company can choose on the invite form and its profile: the NAICS sectors.
 *
 * Fetched from the Oanor NAICS API by the server only (the key stays in .env and is never sent to
 * the browser), kept for 24 hours, and backed by two fallbacks so the dropdown is never empty:
 * the last list that worked, then the stored list in config/industries.php. When the API fails the
 * server stops asking for a few minutes, so a page never waits on a dead API again and again.
 * "Other (please specify)" lets a company enter an industry that is not listed.
 */
class IndustryCatalog
{
    /** The dropdown value for "Other (please specify)". */
    public const OTHER = '__other__';

    public const OTHER_LABEL = 'Other (please specify)';

    private const CACHE = 'industry.sectors';

    private const LAST_GOOD = 'industry.sectors.last_good';

    private const BACKOFF = 'industry.sectors.backoff';

    /** @return list<string> sector titles, in the API's order */
    public function sectors(): array
    {
        if (is_array($cached = Cache::get(self::CACHE)) && $cached !== []) {
            return $cached;
        }

        if ($this->configured() && ! Cache::has(self::BACKOFF)) {
            if ($fresh = $this->fetch()) {
                Cache::put(self::CACHE, $fresh, now()->addDay());
                Cache::forever(self::LAST_GOOD, $fresh);

                return $fresh;
            }
            Cache::put(self::BACKOFF, true, now()->addMinutes(10));
        }

        $lastGood = Cache::get(self::LAST_GOOD);

        return is_array($lastGood) && $lastGood !== [] ? $lastGood : $this->stored();
    }

    /** @return list<string> */
    public function stored(): array
    {
        return array_values(config('industries.sectors', []));
    }

    /** Everything a submitted value may be: the current list, the stored list, or "Other". */
    public function acceptedChoices(): array
    {
        return array_values(array_unique([...$this->sectors(), ...$this->stored(), self::OTHER]));
    }

    /** Whether a stored industry is one of the listed sectors (an older free-text value is not). */
    public function isListed(?string $industry): bool
    {
        return filled($industry) && in_array($industry, [...$this->sectors(), ...$this->stored()], true);
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

    /** The text to store for validated input: the chosen sector, or what was typed for "Other". */
    public function resolve(array $validated): string
    {
        return $validated['industry'] === self::OTHER
            ? trim((string) $validated['industry_other'])
            : $validated['industry'];
    }

    /** Reloads the list from the API now. @return string "api", or why it could not */
    public function refresh(): string
    {
        Cache::forget(self::BACKOFF);
        Cache::forget(self::CACHE);
        if (! $this->configured()) {
            return 'INDUSTRY_API_KEY is not set';
        }
        if (! ($fresh = $this->fetch())) {
            Cache::put(self::BACKOFF, true, now()->addMinutes(10));

            return 'the API did not return a usable list';
        }
        Cache::put(self::CACHE, $fresh, now()->addDay());
        Cache::forever(self::LAST_GOOD, $fresh);

        return 'api';
    }

    private function configured(): bool
    {
        return filled(config('services.industry.key')) && filled(config('services.industry.url'));
    }

    /** @return list<string>|null null when the API fails or answers with something unexpected */
    private function fetch(): ?array
    {
        try {
            $response = Http::timeout(3)->retry(1, 200, throw: false)
                ->withHeaders(['x-oanor-key' => (string) config('services.industry.key'), 'Accept' => 'application/json'])
                ->get(config('services.industry.url'));
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
        if (! $response->successful()) {
            return null;
        }

        $titles = collect($response->json('data.sectors', []))
            ->map(fn ($sector) => is_array($sector) ? trim((string) ($sector['title'] ?? '')) : '')
            ->filter(fn (string $title) => $title !== '' && mb_strlen($title) <= 100)
            ->unique()
            ->values();

        // A real answer is the 20 NAICS sectors; anything much smaller is an error page or a changed API.
        return $titles->count() >= 10 ? $titles->all() : null;
    }
}
