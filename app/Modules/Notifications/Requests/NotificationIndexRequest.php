<?php

namespace App\Modules\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['all', 'unread', 'read'])],
        ];
    }

    public function status(): string
    {
        return (string) (($this->validated()['status'] ?? null) ?: 'all');
    }
}
