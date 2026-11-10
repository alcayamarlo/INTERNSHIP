<?php

namespace App\Services;

/**
 * Converts a resume's stored content (the version 3 JSON the PDF template reads) to and from the
 * Resume Editor's form data, and holds the editor's validation rules.
 *
 * Stored content keeps label => value maps (contact, personal, education, entry rows). The editor
 * works with ordered lists instead, so rows can be added, removed, and reordered.
 */
class ResumeContent
{
    /** The only content version the editor understands (CompanyVisibleProfile::resume). */
    public const EDITABLE_VERSION = 3;

    /** Editor contact field => label printed on the resume. */
    public const CONTACT_FIELDS = [
        'mobile' => 'Mobile',
        'email' => 'Email',
        'city_address' => 'City address',
        'provincial_address' => 'Provincial address',
    ];

    /** Entry sections, in resume order. */
    public const ENTRY_SECTIONS = ['certificates', 'projects', 'experiences', 'references'];

    /** Row-list sections (label + value pairs). */
    public const ROW_SECTIONS = ['personal', 'education'];

    public const MAX_ENTRIES = 30;

    public const MAX_ROWS = 25;

    public const MAX_ENTRY_ROWS = 10;

    public function isEditable(?array $content): bool
    {
        return is_array($content) && (int) ($content['version'] ?? 1) === self::EDITABLE_VERSION;
    }

    /** Stored content => editor data. */
    public function toEditor(array $content): array
    {
        $pairs = fn ($map): array => collect(is_array($map) ? $map : [])
            ->map(fn ($value, $label) => ['label' => (string) $label, 'value' => (string) $value])
            ->values()->all();

        $data = [
            'name' => (string) ($content['name'] ?? ''),
            'course' => (string) ($content['course'] ?? ''),
            'school' => (string) ($content['school'] ?? ''),
            'summary' => (string) ($content['summary'] ?? ''),
            'contact' => collect(self::CONTACT_FIELDS)
                ->map(fn (string $label) => (string) ($content['contact'][$label] ?? ''))
                ->all(),
            'personal' => $pairs($content['personal'] ?? []),
            'education' => $pairs($content['education'] ?? []),
            'skills' => array_values(array_map('strval', $content['skills'] ?? [])),
            'awards' => collect($content['awards'] ?? [])->flatMap(fn ($group) => collect($group['awards'] ?? [])->map(fn ($award) => [
                'group' => (string) ($group['label'] ?? ''),
                'title' => (string) ($award['title'] ?? ''),
                'meta' => (string) ($award['meta'] ?? ''),
            ]))->values()->all(),
        ];

        foreach (self::ENTRY_SECTIONS as $section) {
            $data[$section] = collect($content[$section] ?? [])->map(fn ($entry) => [
                'title' => (string) ($entry['title'] ?? ''),
                'rows' => $pairs($entry['rows'] ?? []),
                'skills' => array_values(array_map('strval', $entry['skills'] ?? [])),
                'description' => (string) ($entry['description'] ?? ''),
            ])->values()->all();
        }

        return $data;
    }

    /**
     * Trims every string and drops blank rows, entries, and skills, so nothing empty reaches the PDF.
     * The editor already leaves blank items out; this keeps hand-made requests honest too.
     */
    public function clean(array $input): array
    {
        $text = fn ($value): string => is_scalar($value) ? trim((string) $value) : '';
        $rows = fn ($rows): array => collect(is_array($rows) ? $rows : [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn ($row) => ['label' => $text($row['label'] ?? ''), 'value' => $text($row['value'] ?? '')])
            ->reject(fn ($row) => $row['label'] === '' && $row['value'] === '')
            ->values()->all();
        $list = fn ($items): array => collect(is_array($items) ? $items : [])
            ->map($text)->reject(fn ($item) => $item === '')->values()->all();

        $data = [
            'name' => $text($input['name'] ?? ''),
            'course' => $text($input['course'] ?? ''),
            'school' => $text($input['school'] ?? ''),
            'summary' => $text($input['summary'] ?? ''),
            'contact' => collect(self::CONTACT_FIELDS)->map(fn ($label, $key) => $text($input['contact'][$key] ?? ''))->all(),
            'personal' => $rows($input['personal'] ?? []),
            'education' => $rows($input['education'] ?? []),
            'skills' => $list($input['skills'] ?? []),
            'awards' => collect(is_array($input['awards'] ?? null) ? $input['awards'] : [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($award) => ['group' => $text($award['group'] ?? ''), 'title' => $text($award['title'] ?? ''), 'meta' => $text($award['meta'] ?? '')])
                ->reject(fn ($award) => $award['title'] === '' && $award['meta'] === '' && $award['group'] === '')
                ->values()->all(),
            'update_profile_summary' => filter_var($input['update_profile_summary'] ?? false, FILTER_VALIDATE_BOOL),
        ];

        foreach (self::ENTRY_SECTIONS as $section) {
            $data[$section] = collect(is_array($input[$section] ?? null) ? $input[$section] : [])
                ->filter(fn ($item) => is_array($item))
                ->map(fn ($entry) => [
                    'title' => $text($entry['title'] ?? ''),
                    'rows' => $rows($entry['rows'] ?? []),
                    'skills' => $list($entry['skills'] ?? []),
                    'description' => $text($entry['description'] ?? ''),
                ])
                ->reject(fn ($entry) => $entry['title'] === '' && $entry['rows'] === [] && $entry['skills'] === [] && $entry['description'] === '')
                ->values()->all();
        }

        return $data;
    }

    /**
     * Validation rules for cleaned editor data. The live preview skips the required name and email,
     * so it still renders while the student is typing.
     */
    public function rules(bool $forPreview = false): array
    {
        $uniqueLabels = function (string $attribute, mixed $rows, \Closure $fail): void {
            $labels = collect(is_array($rows) ? $rows : [])->pluck('label')->filter()->map(fn ($label) => mb_strtolower($label));
            if ($labels->duplicates()->isNotEmpty()) {
                $fail('Each label in this list must be different.');
            }
        };

        $rules = [
            'name' => [$forPreview ? 'nullable' : 'required', 'string', 'max:120'],
            'course' => ['nullable', 'string', 'max:150'],
            'school' => ['nullable', 'string', 'max:150'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'contact.email' => [$forPreview ? 'nullable' : 'required', 'string', 'max:150', ...($forPreview ? [] : ['email'])],
            'contact.mobile' => ['nullable', 'string', 'max:40'],
            'contact.city_address' => ['nullable', 'string', 'max:300'],
            'contact.provincial_address' => ['nullable', 'string', 'max:300'],
            'skills' => ['array', 'max:60'],
            'skills.*' => ['string', 'max:80'],
            'awards' => ['array', 'max:40'],
            'awards.*.group' => ['nullable', 'string', 'max:100'],
            'awards.*.title' => ['required', 'string', 'max:1000'],
            'awards.*.meta' => ['nullable', 'string', 'max:150'],
            'update_profile_summary' => ['boolean'],
        ];

        foreach (self::ROW_SECTIONS as $section) {
            $rules[$section] = ['array', 'max:'.self::MAX_ROWS, $uniqueLabels];
            $rules[$section.'.*.label'] = ['required', 'string', 'max:60'];
            $rules[$section.'.*.value'] = ['required', 'string', 'max:300'];
        }

        foreach (self::ENTRY_SECTIONS as $section) {
            $rules[$section] = ['array', 'max:'.self::MAX_ENTRIES];
            $rules[$section.'.*.title'] = ['required', 'string', 'max:150'];
            $rules[$section.'.*.rows'] = ['array', 'max:'.self::MAX_ENTRY_ROWS, $uniqueLabels];
            $rules[$section.'.*.rows.*.label'] = ['required', 'string', 'max:60'];
            $rules[$section.'.*.rows.*.value'] = ['required', 'string', 'max:300'];
            $rules[$section.'.*.skills'] = ['array', 'max:30'];
            $rules[$section.'.*.skills.*'] = ['string', 'max:80'];
            $rules[$section.'.*.description'] = ['nullable', 'string', 'max:2000'];
        }

        return $rules;
    }

    public function messages(): array
    {
        // Each "*" matches one key segment, and the first matching key wins, so the awards message
        // comes before the general entry title message.
        return [
            'name.required' => 'Enter your name.',
            'contact.email.required' => 'Enter a contact email.',
            'contact.email.email' => 'Enter a valid email address.',
            'awards.*.title.required' => 'Add the award, or remove this entry.',
            '*.*.label.required' => 'Add a label, or clear this row.',
            '*.*.value.required' => 'Add a value, or clear this row.',
            '*.*.rows.*.label.required' => 'Add a label, or clear this row.',
            '*.*.rows.*.value.required' => 'Add a value, or clear this row.',
            '*.*.title.required' => 'Add a title, or remove this entry.',
        ];
    }

    /**
     * Cleaned editor data => stored content. Fields the editor does not own (version, 2x2 photo path,
     * generated date) always come from the resume being edited, never from the request.
     */
    public function fromEditor(array $data, array $base): array
    {
        $map = fn (array $rows): array => collect($rows)->mapWithKeys(fn ($row) => [$row['label'] => $row['value']])->all();

        $content = [
            'version' => self::EDITABLE_VERSION,
            'name' => $data['name'],
            'initials' => CompanyVisibleProfile::initials($data['name']),
            'id_photo_path' => $base['id_photo_path'] ?? null,
            'course' => $data['course'] ?: null,
            'school' => $data['school'] ?: null,
            'summary' => $data['summary'] ?: null,
            'generated_at' => $base['generated_at'] ?? now('Asia/Manila')->toIso8601String(),
            'edited_at' => now('Asia/Manila')->toIso8601String(),
            'contact' => collect(self::CONTACT_FIELDS)
                ->mapWithKeys(fn ($label, $key) => [$label => $data['contact'][$key] ?? ''])
                ->filter(fn ($value) => $value !== '')
                ->all(),
            'personal' => $map($data['personal']),
            'education' => $map($data['education']),
            // Consecutive awards with the same group share one group heading, in the order given.
            'awards' => collect($data['awards'])->reduce(function (array $groups, array $award) {
                $label = $award['group'] !== '' ? $award['group'] : 'Awards';
                $last = array_key_last($groups);
                if ($last === null || $groups[$last]['label'] !== $label) {
                    $groups[] = ['label' => $label, 'awards' => []];
                    $last = array_key_last($groups);
                }
                $groups[$last]['awards'][] = ['title' => $award['title'], 'meta' => $award['meta']];

                return $groups;
            }, []),
            'skills' => $data['skills'],
        ];

        foreach (self::ENTRY_SECTIONS as $section) {
            $content[$section] = collect($data[$section])->map(fn ($entry) => [
                'title' => $entry['title'],
                'rows' => $map($entry['rows']),
                'skills' => $entry['skills'],
                'description' => $entry['description'] ?: null,
            ])->all();
        }

        return $content;
    }
}
