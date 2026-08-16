<?php

namespace App\Modules\Payments\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Number;
use IntlDateFormatter;

final class PaymentDisplayFormatter
{
    public function money(float $amount, ?string $currency): string
    {
        $currency = $currency ?: 'SAR';
        $formatted = Number::currency(
            $amount,
            in: $currency,
            locale: app()->isLocale('ar') ? 'ar-SA' : 'en',
        );

        return $formatted !== false
            ? $formatted
            : number_format($amount, 2).' '.$currency;
    }

    public function date(?CarbonInterface $date): ?string
    {
        if (! $date) {
            return null;
        }

        $locale = app()->isLocale('ar') ? 'ar-SA' : 'en';
        $formatter = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE,
            config('app.timezone'),
            IntlDateFormatter::GREGORIAN,
        );
        $formatted = $formatter->format($date);

        return $formatted !== false
            ? $formatted
            : $date->locale(app()->getLocale())->translatedFormat('j M Y');
    }
}
