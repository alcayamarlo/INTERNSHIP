<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:placement,student,competency,employer,internship'],
            'format' => ['required', 'in:pdf,csv'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Please select a report type.',
            'format.required' => 'Please select an export format.',
        ];
    }
}
