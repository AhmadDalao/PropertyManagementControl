<?php

namespace App\Modules\Notifications\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NotificationIndexRequest extends FormRequest
{
    /** @var list<string> */
    public const TYPES = ['all', 'maintenance_request', 'payment', 'lease', 'document'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['all', 'unread', 'read'])],
            'type' => ['nullable', Rule::in(self::TYPES)],
            'search' => ['nullable', 'string', 'max:120'],
        ];
    }

    /** @return array{status:string,type:string,search:string} */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'status' => (string) (($validated['status'] ?? null) ?: 'all'),
            'type' => (string) (($validated['type'] ?? null) ?: 'all'),
            'search' => trim((string) ($validated['search'] ?? '')),
        ];
    }
}
