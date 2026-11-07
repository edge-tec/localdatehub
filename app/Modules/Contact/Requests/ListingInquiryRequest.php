<?php

namespace App\Modules\Contact\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListingInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:25'],
            'message' => ['required', 'string', 'min:5', 'max:3000'],
            'contact_hp' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please provide your name or preferred handle.',
            'email.required' => 'A valid return email address is required for communication.',
            'message.required' => 'Please write your message to the listing owner.',
            'message.min' => 'Message must be at least 5 characters.',
        ];
    }
}
