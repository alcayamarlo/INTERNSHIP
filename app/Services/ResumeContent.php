<?php

namespace App\Services;

use App\Enums\SkillType;
use App\Models\Skill;
use App\Models\Student;

/**
 * Converts a resume's stored content (the version 4 JSON the PDF template reads) to and from the
 * Resume Editor's form data, and holds the editor's validation rules.
 *
 * Stored content keeps label => value maps (contact, education, entry rows). The editor works with
 * ordered lists instead, so rows can be added, removed, and reordered.
 *
 * Version 4 never carries personal or sensitive data (birth date, sex, civil status, religion, height
 * and weight, parents, full addresses, emergency contacts): an employer does not need them to shortlist
 * a student, and the Data Privacy Act treats them as sensitive. They stay on the profile for the school.
 */
class ResumeContent
{
    /** The content version the editor and the PDF template work with. */
    public const EDITABLE_VERSION = 4;

    /** Older content the editor can still open: it is upgraded to the current version when opened. */
    public const UPGRADABLE_VERSION = 3;

    /** Editor contact field => label printed on the resume. Only what a first contact needs. */
    public const CONTACT_FIELDS = [
        'mobile' => 'Mobile',
        'email' => 'Email',
        'city' => 'City',
    ];

    /** Entry sections, in resume order. */
    public const ENTRY_SECTIONS = ['certificates', 'projects', 'experiences', 'references'];

    /** Row-list sections (label + value pairs). */
    public const ROW_SECTIONS = ['education'];

    public const MAX_ENTRIES = 30;

    public const MAX_ROWS = 25;

    public const MAX_ENTRY_ROWS = 10;

    public function isEditable(?array $content): bool
    {
        $version = is_array($content) ? (int) ($content['version'] ?? 1) : 0;

        return in_array($version, [self::EDITABLE_VERSION, self::UPGRADABLE_VERSION], true);
    }

    /**
     * Version 3 content (a burgundy sidebar resume with a "Personal data" section) as version 4: the personal
     * data and the provincial address are dropped, the city comes from the profile, and the skills are split
     * into Technical and Soft. Content that is already version 4 is returned as is.
     */
    public function upgrade(array $content, ?Student $student = null): array
    {
        if ((int) ($content['version'] ?? 1) >= self::EDITABLE_VERSION) {
            return $content;
        }

        $contact = collect($content['contact'] ?? [])->only(['Mobile', 'Email'])->all();
        $city = $student ? collect([$student->city, $student->province])->filter()->implode(', ') : '';
        if ($city !== '') {
            $contact['City'] = $city;
        }

        $skills = collect($content['skills'] ?? [])->filter(fn ($skill) => is_string($skill) && $skill !== '')->values();
        $types = Skill::query()->whereIn('name', $skills->all())->pluck('type', 'name');
        $isSoft = fn (string $name): bool => ($types[$name] ?? null) === SkillType::Soft || ($types[$name] ?? null) === SkillType::Soft->value;

        unset($content['personal']);

        return [
            ...$content,
            'version' => self::EDITABLE_VERSION,
            'contact' => $contact,
            'skills' => [
                'technical' => $skills->reject($isSoft)->values()->all(),
                'soft' => $skills->filter($isSoft)->values()->all(),
            ],
            'show_photo' => $content['show_photo'] ?? true,
        ];
    }

    /** Stored content => editor data. */
    public function toEditor(array $content): array
    {
        $pairs = fn ($map): array => collect(is_array($map) ? $map : [])
            ->map(fn ($value, $label) => ['label' => (string) $label, 'value' => (string) $value])
            ->values()->all();
        $names = fn ($list): array => array_values(array_map('strval', is_array($list) ? $list : []));

        $data = [
            'name' => (string) ($content['name'] ?? ''),
            'course' => (string) ($content['course'] ?? ''),
            'school' => (string) ($content['school'] ?? ''),
            'summary' => (string) ($content['summary'] ?? ''),
            'show_photo' => (bool) ($content['show_photo'] ?? true),
            'contact' => collect(self::CONTACT_FIELDS)
                ->map(fn (string $label) => (string) ($content['contact'][$label] ?? ''))
                ->all(),
            'education' => $pairs($content['education'] ?? []),
            'skills_technical' => $names($content['skills']['technical'] ?? []),
            'skills_soft' => $names($content['skills']['soft'] ?? []),
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
            'show_photo' => filter_var($input['show_photo'] ?? true, FILTER_VALIDATE_BOOL),
            'contact' => collect(self::CONTACT_FIELDS)->map(fn ($label, $key) => $text($input['contact'][$key] ?? ''))->all(),
            'education' => $rows($input['education'] ?? []),
            'skills_technical' => $list($input['skills_technical'] ?? []),
            'skills_soft' => $list($input['skills_soft'] ?? []),
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
            'summary' => ['nullable', 'string', 'max:1200'],
            'show_photo' => ['boolean'],
            'contact.email' => [$forPreview ? 'nullable' : 'required', 'string', 'max:150', ...($forPreview ? [] : ['email'])],
            'contact.mobile' => ['nullable', 'string', 'max:40'],
            'contact.city' => ['nullable', 'string', 'max:120'],
            'skills_technical' => ['array', 'max:40'],
            'skills_technical.*' => ['string', 'max:80'],
            'skills_soft' => ['array', 'max:40'],
            'skills_soft.*' => ['string', 'max:80'],
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
            $rules[$section.'.*.description'] = ['nullable', 'string', 'max:800'];
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
            'show_photo' => (bool) $data['show_photo'],
            'course' => $data['course'] ?: null,
            'school' => $data['school'] ?: null,
            'summary' => $data['summary'] ?: null,
            'generated_at' => $base['generated_at'] ?? now('Asia/Manila')->toIso8601String(),
            'edited_at' => now('Asia/Manila')->toIso8601String(),
            'contact' => collect(self::CONTACT_FIELDS)
                ->mapWithKeys(fn ($label, $key) => [$label => $data['contact'][$key] ?? ''])
                ->filter(fn ($value) => $value !== '')
                ->all(),
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
            'skills' => ['technical' => $data['skills_technical'], 'soft' => $data['skills_soft']],
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
