<?php

namespace App\Services;

use App\Enums\ProficiencyLevel;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\InternshipRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InternshipService
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function create(Employer $employer, array $data, Request $request): Internship
    {
        return DB::transaction(function () use ($employer, $data, $request) {
            $internship = $employer->internships()->create($this->internshipAttributes($data));
            $this->syncRequirements($request, $internship);
            $internship->programs()->sync($data['program_ids'] ?? []);

            $this->activityLog->log($employer->user, 'internship_create', [
                'internship_id' => $internship->id,
            ]);

            return $internship;
        });
    }

    public function update(Internship $internship, array $data, Request $request): Internship
    {
        return DB::transaction(function () use ($internship, $data, $request) {
            $internship->update($this->internshipAttributes($data));
            $this->syncRequirements($request, $internship);
            $internship->programs()->sync($data['program_ids'] ?? []);

            $this->activityLog->log($internship->employer->user, 'internship_update', [
                'internship_id' => $internship->id,
            ]);

            return $internship;
        });
    }

    public function close(Internship $internship): void
    {
        DB::transaction(function () use ($internship) {
            $internship->update(['status' => 'closed']);

            $this->activityLog->log($internship->employer->user, 'internship_close', [
                'internship_id' => $internship->id,
            ]);
        });
    }

    public function delete(Internship $internship): void
    {
        $internshipId = $internship->id;
        $user = $internship->employer->user;

        DB::transaction(function () use ($internship, $user, $internshipId) {
            $internship->delete();

            $this->activityLog->log($user, 'internship_delete', [
                'internship_id' => $internshipId,
            ]);
        });
    }

    public function syncRequirements(Request $request, Internship $internship): void
    {
        DB::transaction(function () use ($request, $internship) {
            $internship->requirementsList()->delete();

            $names = $request->input('requirement_names', []);
            $levels = $request->input('requirement_levels', []);

            foreach ($names as $index => $name) {
                if (blank($name)) {
                    continue;
                }

                InternshipRequirement::create([
                    'internship_id' => $internship->id,
                    'requirement_name' => $name,
                    'required_level' => $levels[$index] ?? ProficiencyLevel::Intermediate->value,
                ]);
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function internshipAttributes(array $data): array
    {
        return collect($data)->only([
            'title',
            'description',
            'responsibilities',
            'requirements',
            'duration',
            'allowance',
            'work_setup',
            'location',
            'status',
            'requires_character_reference',
            'requested_documents',
        ])->all();
    }
}
