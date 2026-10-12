<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\Student;
use App\Support\StudentIdFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentIdRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $studentNumber = $this->input('student_id_number');
        if (is_string($studentNumber)) {
            $studentNumber = trim($studentNumber);
            $this->merge([
                'student_id_number' => $studentNumber === '' ? null : mb_strtoupper($studentNumber),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isRole(UserRole::Admin) ?? false;
    }

    public function rules(): array
    {
        /** @var Student $student */
        $student = $this->route('student');
        $studentNumber = $this->input('student_id_number');
        $studentNumberChanged = $studentNumber !== $student->student_id_number;

        return [
            'student_id_number' => [
                'nullable',
                'string',
                'max:50',
                ...($studentNumberChanged
                    ? [Rule::unique('students', 'student_id_number')->ignore($student->id)]
                    : []),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                /** @var Student $student */
                $student = $this->route('student');
                $studentNumber = $this->input('student_id_number');

                if (filled($studentNumber)
                    && $studentNumber !== $student->student_id_number
                    && ! StudentIdFormat::matches($studentNumber)) {
                    $validator->errors()->add('student_id_number', 'Enter a student ID in the format SCC-21-00013240.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'student_id_number.unique' => 'This student ID is already in use.',
        ];
    }
}
