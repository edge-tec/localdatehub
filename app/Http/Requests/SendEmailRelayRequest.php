<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRelayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxKb = (int) config('email_relay.attachments.max_size_kb', 5120);
        $maxCount = (int) config('email_relay.attachments.max_count', 3);
        $extensions = implode(',', (array) config('email_relay.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'txt']));

        return [
            'sender_name' => ['required', 'string', 'min:2', 'max:100'],
            'sender_email' => ['required', 'email:rfc', 'max:190'],
            'subject' => ['required', 'string', 'min:3', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'attachments' => ['nullable', 'array', "max:{$maxCount}"],
            'attachments.*' => ['file', "max:{$maxKb}", "mimes:{$extensions}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => 'You can attach up to 3 files per message.',
            'attachments.*.max' => 'Each attachment must be 5 MB or smaller.',
            'attachments.*.mimes' => 'Allowed attachment formats: JPG, PNG, WEBP, PDF, TXT.',
        ];
    }
}
