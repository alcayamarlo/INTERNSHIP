<?php

namespace App\Http\Requests\Competency;

use App\Enums\UserRole;

/** Adds the missing wizard details to an old Documents-tab certificate; its file can be reused. */
class CompleteLegacyCertificateRequest extends StoreCompetencyRequest
{
    public function authorize(): bool
    {
        $certificate = $this->route('certificate');

        return $this->user()?->isRole(UserRole::Student)
            && $certificate
            && (int) $certificate->student_id === (int) $this->user()->student?->id;
    }

    protected function fileIsRequired(): bool
    {
        return blank($this->route('certificate')?->file_path);
    }
}
