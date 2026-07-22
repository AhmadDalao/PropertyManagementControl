<?php

namespace App\Modules\Leases\Support;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

final class LeaseInputGuard
{
    /** @param array<string, mixed> $data */
    public function validateCreate(array $data): void
    {
        $errors = $this->errors();
        $this->option($errors, $data, 'status', LeaseOptions::CREATE_STATUSES);
        $this->option($errors, $data, 'payment_frequency', LeaseOptions::PAYMENT_FREQUENCIES);
        $this->requiredId($errors, $data, 'tenant_profile_id');
        $this->requiredId($errors, $data, 'asset_id');
        $start = $this->date($errors, $data, 'started_at', true);
        $end = $this->date($errors, $data, 'ends_at', true);
        $this->date($errors, $data, 'signed_at');

        if ($start && $end && $end <= $start) {
            $errors['ends_at'] = $this->message('validation.after', [
                'attribute' => $this->message('app.leases.end_date'),
                'date' => $this->message('app.leases.start_date'),
            ]);
        }

        foreach (['rent_amount', 'deposit_amount', 'tax_amount', 'discount_amount'] as $field) {
            $this->amount($errors, $data, $field, $field === 'rent_amount');
        }

        if ($this->number($data['discount_amount'] ?? 0) > $this->number($data['rent_amount'] ?? 0) + $this->number($data['tax_amount'] ?? 0)) {
            $errors['discount_amount'] = $this->message('app.errors.lease_discount_exceeds_charges');
        }

        $currency = strtoupper(trim((string) ($data['currency'] ?? 'SAR')));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            $errors['currency'] = $this->message('validation.size.string', [
                'attribute' => $this->message('app.leases.currency'),
                'size' => 3,
            ]);
        }

        $billingDay = $data['billing_day'] ?? null;

        if ($billingDay !== null && $billingDay !== '' && filter_var($billingDay, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 31],
        ]) === false) {
            $errors['billing_day'] = $this->message('validation.between.numeric', [
                'attribute' => $this->message('app.leases.billing_day'),
                'min' => 1,
                'max' => 31,
            ]);
        }

        $this->text($errors, $data, 'terms_en');
        $this->text($errors, $data, 'terms_ar');
        $this->text($errors, $data, 'notes');
        $this->throw($errors);
    }

    /** @param array<string, mixed> $data */
    public function validateUpdate(array $data): void
    {
        $errors = $this->errors();
        $this->option($errors, $data, 'status', LeaseOptions::STATUSES);
        $this->date($errors, $data, 'signed_at');
        $this->text($errors, $data, 'terms_en');
        $this->text($errors, $data, 'terms_ar');
        $this->text($errors, $data, 'notes');
        $this->throw($errors);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $allowed
     */
    private function option(array &$errors, array $data, string $field, array $allowed): void
    {
        if (! in_array($data[$field] ?? null, $allowed, true)) {
            $errors[$field] = $this->message('validation.in', [
                'attribute' => $this->message("app.leases.{$field}"),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function requiredId(array &$errors, array $data, string $field): void
    {
        if (filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[$field] = $this->message('validation.required', [
                'attribute' => $this->message("app.leases.{$field}"),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function date(array &$errors, array $data, string $field, bool $required = false): ?DateTimeImmutable
    {
        $value = trim((string) ($data[$field] ?? ''));

        if ($value === '' && ! $required) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (! $date || $date->format('Y-m-d') !== $value) {
            $errors[$field] = $this->message($required && $value === '' ? 'validation.required' : 'validation.date', [
                'attribute' => $this->message("app.leases.{$field}"),
            ]);

            return null;
        }

        return $date;
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function amount(array &$errors, array $data, string $field, bool $required): void
    {
        $value = $data[$field] ?? null;

        if ((! $required && ($value === null || $value === ''))) {
            return;
        }

        if (! is_numeric($value) || $this->number($value) < 0) {
            $errors[$field] = $this->message('validation.min.numeric', [
                'attribute' => $this->message("app.leases.{$field}"),
                'min' => 0,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function text(array &$errors, array $data, string $field): void
    {
        if (mb_strlen((string) ($data[$field] ?? '')) > 50000) {
            $errors[$field] = $this->message('validation.max.string', [
                'attribute' => $this->message("app.leases.{$field}"),
                'max' => 50000,
            ]);
        }
    }

    private function number(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /** @return array<string, string> */
    private function errors(): array
    {
        return [];
    }

    /** @param array<string, mixed> $replace */
    private function message(string $key, array $replace = []): string
    {
        $message = trans($key, $replace);

        return is_string($message) ? $message : $key;
    }

    /** @param array<string, string> $errors */
    private function throw(array $errors): void
    {
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
