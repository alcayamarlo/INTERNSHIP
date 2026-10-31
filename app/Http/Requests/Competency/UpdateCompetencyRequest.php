<?php

namespace App\Http\Requests\Competency;

use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;

class UpdateCompetencyRequest extends StoreCompetencyRequest
{
    public function authorize(): bool
    {
        $competency = $this->route('competency');

        return $this->user()?->isRole(UserRole::Student)
            && $competency
            && (int) $competency->student_id === (int) $this->user()->student?->id;
    }

    protected function fileIsRequired(): bool
    {
        return false;
    }

    protected function allowedLevels(): array
    {
        $levels = parent::allowedLevels();
        if ($this->route('competency')?->proficiency_level === ProficiencyLevel::Expert) {
            $levels[] = ProficiencyLevel::Expert->value;
        }

        return $levels;
    }
}
