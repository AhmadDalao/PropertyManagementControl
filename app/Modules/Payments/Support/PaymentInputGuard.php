<?php

namespace App\Modules\Payments\Support;

use App\Models\Payment;
use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

final class PaymentInputGuard
{
    /** @param array<string, mixed> $data */
    public function validateCreate(array $data): void
    {
        $errors = $this->errors();
        $this->requiredId($errors, $data, 'lease_id');
        $this->option($errors, $data, 'type', PaymentOptions::TYPES);
        $this->option($errors, $data, 'method', PaymentOptions::METHODS);
        $this->option($errors, $data, 'status', PaymentOptions::CREATE_STATUSES);
        $this->date($errors, $data, 'received_on');
        $this->amount($errors, $data);
        $this->reference($errors, $data);
        $this->text($errors, $data, 'notes', 5000);
        $this->throw($errors);
    }

    /** @param array<string, mixed> $data */
    public function validateUpdate(array $data): void
    {
        $errors = $this->errors();
        $this->option($errors, $data, 'status', PaymentOptions::STATUSES);
        $this->text($errors, $data, 'notes', 5000);
        $this->throw($errors);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function requiredId(array &$errors, array $data, string $field): void
    {
        if (filter_var($data[$field] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[$field] = $this->message('validation.required', [
                'attribute' => $this->message("app.payments.{$field}"),
            ]);
        }
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
                'attribute' => $this->message("app.payments.{$field}"),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function date(array &$errors, array $data, string $field): void
    {
        $raw = $data[$field] ?? null;
        $value = is_string($raw) ? trim($raw) : '';
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if (! $date || $date->format('Y-m-d') !== $value) {
            $errors[$field] = $this->message($raw === null || $raw === '' ? 'validation.required' : 'validation.date', [
                'attribute' => $this->message("app.payments.{$field}"),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function amount(array &$errors, array $data): void
    {
        $raw = $data['amount'] ?? null;
        $value = is_string($raw) || is_int($raw) || is_float($raw)
            ? trim((string) $raw)
            : '';
        $valid = preg_match('/^(?:0|[1-9]\d*)(?:\.\d{1,2})?$/', $value) === 1;
        $amount = $valid ? (float) $value : 0.0;

        if (! $valid || $amount < 0.01 || $amount > 999999999999.99) {
            $errors['amount'] = $this->message('app.errors.payment_amount_invalid');
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function text(array &$errors, array $data, string $field, int $max): void
    {
        $value = $data[$field] ?? null;

        if ($value !== null && ! is_string($value)) {
            $errors[$field] = $this->message('validation.string', [
                'attribute' => $this->message("app.payments.{$field}"),
            ]);

            return;
        }

        if (mb_strlen((string) $value) > $max) {
            $errors[$field] = $this->message('validation.max.string', [
                'attribute' => $this->message("app.payments.{$field}"),
                'max' => $max,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function reference(array &$errors, array $data): void
    {
        $this->text($errors, $data, 'reference', 255);
        $reference = is_string($data['reference'] ?? null)
            ? trim((string) $data['reference'])
            : '';

        if (! isset($errors['reference'])
            && $reference !== ''
            && Payment::query()->where('reference', $reference)->exists()) {
            $errors['reference'] = $this->message('validation.unique', [
                'attribute' => $this->message('app.payments.reference'),
            ]);
        }
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
