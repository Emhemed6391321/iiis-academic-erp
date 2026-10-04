<?php

namespace App\Http\Requests;

use App\Services\StudentTransferWorkflow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentTransferTransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['APPROVE', 'REJECT', 'ACCEPT', 'MEMO', 'HQ_APPROVE'])],
            'step'   => ['nullable', 'string', Rule::in(['CENTRAL_MEMO', 'RECEIVING_BRANCH', 'HQ_FINAL', StudentTransferWorkflow::STAGE_ACADEMIC_REVIEWED, StudentTransferWorkflow::STAGE_DESTINATION_ACCEPTED, StudentTransferWorkflow::STAGE_HQ_APPROVED])],
            'notes'  => ['nullable', 'string', 'max:1000'],
            'statement' => ['nullable', 'string', 'max:1000'],
            'decision_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'نوع الإجراء مطلوب.',
            'action.in'       => 'الإجراء المحدد غير صالح.',
            'step.in'         => 'المرحلة المحددة غير مطابقة لبروتوكول المصافحة.',
        ];
    }
}
