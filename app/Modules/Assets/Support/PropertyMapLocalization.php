<?php

namespace App\Modules\Assets\Support;

use Illuminate\Database\Eloquent\Model;

class PropertyMapLocalization
{
    /**
     * @param  array<string, mixed>  $map
     */
    public function mapValue(array $map, string $key, string $locale): ?string
    {
        return $this->value($map, "{$key}_{$locale}")
            ?? $this->value($map, $key)
            ?? $this->value(
                $map,
                $locale === 'ar' ? "{$key}_en" : "{$key}_ar",
            );
    }

    /**
     * @param  array<string, mixed>  $map
     */
    public function value(array $map, string $key): ?string
    {
        $value = trim((string) ($map[$key] ?? ''));

        return $value === '' ? null : $value;
    }

    public function text(
        ?string $english,
        ?string $arabic,
        string $locale,
    ): ?string {
        return $locale === 'ar'
            ? ($arabic ?: $english)
            : ($english ?: $arabic);
    }

    public function model(
        mixed $model,
        string $englishAttribute,
        string $arabicAttribute,
        string $locale,
    ): ?string {
        if (! $model instanceof Model) {
            return null;
        }

        $english = $model->getAttribute($englishAttribute);
        $arabic = $model->getAttribute($arabicAttribute);

        return $this->text(
            is_string($english) ? $english : null,
            is_string($arabic) ? $arabic : null,
            $locale,
        );
    }

    public function stakeholderName(mixed $stakeholder): ?string
    {
        if (! $stakeholder instanceof Model) {
            return null;
        }

        $user = $stakeholder->getRelation('user');
        $name = $user instanceof Model ? $user->getAttribute('name') : null;

        return is_string($name) ? $name : null;
    }
}
