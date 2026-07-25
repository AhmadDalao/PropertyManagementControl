<?php

namespace App\Modules\SystemReadiness\Requests;

use App\Modules\SystemReadiness\Support\ReadinessCheckCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateReadinessCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('superadmin') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', Rule::in(app(ReadinessCheckCatalog::class)->allKeys())],
            'confirmed' => ['required', 'boolean'],
            'evidence' => [
                Rule::requiredIf($this->boolean('confirmed')),
                'nullable',
                'string',
                'min:3',
                'max:1000',
            ],
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $key = $this->input('key');
                $catalog = app(ReadinessCheckCatalog::class);

                if (
                    is_string($key)
                    && in_array($key, $catalog->allKeys(), true)
                    && $catalog->scope($key) === 'portfolio'
                    && ! $this->filled('portfolio_id')
                ) {
                    $validator->errors()->add('portfolio_id', trans('app.readiness.portfolio_required'));
                }
            },
        ];
    }

    /** @return array{key: string, confirmed: bool, evidence: ?string, portfolio_id: ?int} */
    public function payload(): array
    {
        $data = $this->validated();

        return [
            'key' => (string) $data['key'],
            'confirmed' => (bool) $data['confirmed'],
            'evidence' => isset($data['evidence']) ? (string) $data['evidence'] : null,
            'portfolio_id' => isset($data['portfolio_id']) ? (int) $data['portfolio_id'] : null,
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'evidence' => trans('app.readiness.evidence'),
            'portfolio_id' => trans('app.readiness.portfolio'),
        ];
    }
}
