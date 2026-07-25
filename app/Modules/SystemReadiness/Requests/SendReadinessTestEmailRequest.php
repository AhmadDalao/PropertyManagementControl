<?php

namespace App\Modules\SystemReadiness\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SendReadinessTestEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
