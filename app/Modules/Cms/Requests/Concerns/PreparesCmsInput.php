<?php

namespace App\Modules\Cms\Requests\Concerns;

use Illuminate\Support\Str;

trait PreparesCmsInput
{
    /** @return array<string, string> */
    protected function cmsValidationAttributes(): array
    {
        $attributes = trans('app.cms.validation_attributes');

        if (! is_array($attributes)) {
            return [];
        }

        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    protected function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key, ''));

        return $value === '' ? null : $value;
    }

    protected function requiredString(string $key): string
    {
        return trim((string) $this->input($key, ''));
    }

    protected function normalizedSlug(): ?string
    {
        $value = $this->nullableString('slug');

        return $value === null ? null : (Str::slug($value) ?: null);
    }
}
