<?php

namespace App\Modules\Portfolios\Requests;

use App\Modules\Portfolios\Support\PortfolioOptions;
use App\Support\PortfolioModules;

trait HasPortfolioValidationAttributes
{
    /** @param array<string, mixed>|null $fallbackModules */
    protected function preparePortfolioInput(?array $fallbackModules = null): void
    {
        $input = $this->all();
        $moduleSettings = $this->input('module_settings');
        $hasModuleFields = collect(PortfolioOptions::moduleKeys())
            ->contains(fn (string $module): bool => array_key_exists(
                PortfolioOptions::moduleField($module),
                $input,
            ));

        if ($hasModuleFields) {
            $moduleSettings = PortfolioModules::normalize($fallbackModules);

            foreach (PortfolioOptions::moduleKeys() as $module) {
                $field = PortfolioOptions::moduleField($module);

                if (array_key_exists($field, $input)) {
                    $moduleSettings[$module] = filter_var($input[$field], FILTER_VALIDATE_BOOLEAN);
                }
            }
        } elseif (is_array($moduleSettings) && $fallbackModules !== null) {
            $moduleSettings = [
                ...PortfolioModules::normalize($fallbackModules),
                ...$moduleSettings,
            ];
        } elseif (! is_array($moduleSettings)) {
            $moduleSettings = PortfolioModules::normalize($fallbackModules);
        }

        $this->merge([
            'name_en' => $this->trimmed('name_en'),
            'name_ar' => $this->trimmed('name_ar'),
            'code' => $this->upperOrNull('code'),
            'contact_email' => $this->lowerOrNull('contact_email'),
            'contact_phone' => $this->nullableTrimmed('contact_phone'),
            'city' => $this->nullableTrimmed('city'),
            'country' => $this->nullableTrimmed('country'),
            'address' => $this->nullableTrimmed('address'),
            'address_ar' => $this->nullableTrimmed('address_ar'),
            'default_currency' => $this->upperOrNull('default_currency'),
            'module_settings' => $moduleSettings,
        ]);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name_en' => trans('app.portfolios.name_en'),
            'name_ar' => trans('app.portfolios.name_ar'),
            'code' => trans('app.portfolios.code'),
            'contact_email' => trans('app.portfolios.contact_email'),
            'contact_phone' => trans('app.portfolios.contact_phone'),
            'city' => trans('app.portfolios.city'),
            'country' => trans('app.portfolios.country'),
            'address' => trans('app.portfolios.address_en'),
            'address_ar' => trans('app.portfolios.address_ar'),
            'default_currency' => trans('app.portfolios.default_currency'),
            'status' => trans('app.portfolios.status'),
            'module_settings' => trans('app.portfolios.modules'),
        ];
    }

    private function trimmed(string $key): string
    {
        return trim((string) $this->input($key, ''));
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = $this->trimmed($key);

        return $value === '' ? null : $value;
    }

    private function upperOrNull(string $key): ?string
    {
        $value = $this->nullableTrimmed($key);

        return $value === null ? null : mb_strtoupper($value);
    }

    private function lowerOrNull(string $key): ?string
    {
        $value = $this->nullableTrimmed($key);

        return $value === null ? null : mb_strtolower($value);
    }
}
