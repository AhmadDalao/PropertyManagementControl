<?php

namespace App\Modules\Expenses\Support;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;

final class ExpenseInputGuard
{
    /** @param array<string, mixed> $data */
    public function validateCreate(array $data): void
    {
        $this->validate($data);
    }

    /** @param array<string, mixed> $data */
    public function validateUpdate(array $data): void
    {
        $this->validate($data);
    }

    /** @param array<string, mixed> $data */
    private function validate(array $data): void
    {
        $errors = [];
        $this->optionalId($errors, $data, 'portfolio_id');
        $this->optionalId($errors, $data, 'asset_id');
        $this->optionalId($errors, $data, 'maintenance_request_id');
        $this->option($errors, $data, 'category', ExpenseOptions::CATEGORIES);
        $this->requiredText($errors, $data, 'title', 255);
        $this->optionalText($errors, $data, 'description', 5000);
        $this->optionalText($errors, $data, 'vendor_name', 255);
        $this->date($errors, $data, 'incurred_on');
        $this->amount($errors, $data);
        $this->currency($errors, $data);
        $this->option($errors, $data, 'status', ExpenseOptions::MUTABLE_STATUSES);

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function optionalId(array &$errors, array $data, string $field): void
    {
        $value = $data[$field] ?? null;

        if ($value !== null && $value !== ''
            && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[$field] = $this->message('validation.integer', $field);
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
            $errors[$field] = $this->message('validation.in', $field);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function requiredText(array &$errors, array $data, string $field, int $max): void
    {
        $value = $data[$field] ?? null;

        if (! is_string($value) || trim($value) === '') {
            $errors[$field] = $this->message('validation.required', $field);

            return;
        }

        $this->textLength($errors, $field, $value, $max);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function optionalText(array &$errors, array $data, string $field, int $max): void
    {
        $value = $data[$field] ?? null;

        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            $errors[$field] = $this->message('validation.string', $field);

            return;
        }

        $this->textLength($errors, $field, $value, $max);
    }

    /** @param array<string, string> $errors */
    private function textLength(array &$errors, string $field, string $value, int $max): void
    {
        if (mb_strlen($value) > $max) {
            $errors[$field] = $this->message('validation.max.string', $field, ['max' => $max]);
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
            $errors[$field] = $this->message(
                $raw === null || $raw === '' ? 'validation.required' : 'validation.date',
                $field,
            );

            return;
        }

        if ($date > new DateTimeImmutable('today')) {
            $errors[$field] = $this->message('validation.before_or_equal', $field, ['date' => now()->toDateString()]);
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
            $errors['amount'] = $this->message('validation.between.numeric', 'amount', [
                'min' => '0.01',
                'max' => '999999999999.99',
            ]);
        }
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $data
     */
    private function currency(array &$errors, array $data): void
    {
        $value = $data['currency'] ?? null;

        if (! is_string($value) || preg_match('/^[A-Z]{3}$/', strtoupper(trim($value))) !== 1) {
            $errors['currency'] = $this->message('validation.regex', 'currency');
        }
    }

    /** @param array<string, mixed> $replace */
    private function message(string $key, string $field, array $replace = []): string
    {
        $attributeKey = match ($field) {
            'portfolio_id' => 'portfolio',
            'asset_id' => 'asset',
            'maintenance_request_id' => 'maintenance_request',
            'title' => 'expense_title',
            default => $field,
        };
        $message = trans($key, [
            'attribute' => trans("app.expenses.{$attributeKey}"),
            ...$replace,
        ]);

        return is_string($message) ? $message : $key;
    }
}
