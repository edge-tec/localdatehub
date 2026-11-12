<?php

namespace App\Modules\Contact\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_reason' => [
                'required',
                'string',
                'in:underage_minor,commercial_sex,non_consensual_content,fraud_scam,harassment,other',
            ],
            'report_details' => ['required', 'string', 'min:10', 'max:3000'],
            'reporter_name' => ['nullable', 'string', 'max:100'],
            'reporter_email' => ['nullable', 'email', 'max:150'],
            'report_hp' => ['nullable'],
            'cf-turnstile-response' => [new \App\Rules\ValidTurnstile($this->ip())],
        ];
    }

    public function messages(): array
    {
        return [
            'report_reason.required' => 'Please select the primary reason for your report.',
            'report_reason.in' => 'Please select a valid report violation category.',
            'report_details.required' => 'Please explain why this listing violates community standards.',
            'report_details.min' => 'Please provide at least 10 characters explaining the issue.',
        ];
    }
}
