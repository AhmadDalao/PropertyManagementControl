<?php

namespace App\Modules\SystemBackups\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SystemBackupIndexRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                Rule::in(['all', 'queued', 'running', 'completed', 'failed', 'pruned']),
            ],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function status(): string
    {
        return (string) ($this->validated('status') ?: 'all');
    }
}
