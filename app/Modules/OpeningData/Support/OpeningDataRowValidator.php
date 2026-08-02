<?php

namespace App\Modules\OpeningData\Support;

use App\Modules\Assets\Support\AssetOptions;
use App\Modules\Leases\Support\LeaseOptions;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Tenants\Support\TenantOptions;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class OpeningDataRowValidator
{
    /**
     * @param  array<string, array{headers:array<int, string>,rows:array<int, array<string, mixed>>}>  $tables
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    public function validate(array $tables): array
    {
        $issues = [];

        foreach (OpeningDataWorkbookSchema::SHEETS as $sheet => $requiredHeaders) {
            $table = $tables[$sheet] ?? ['headers' => [], 'rows' => []];
            $missing = array_values(array_diff($requiredHeaders, $table['headers']));

            foreach ($missing as $header) {
                $issues[] = $this->issue(
                    $sheet,
                    null,
                    $header,
                    trans('app.opening_data.errors.missing_column', ['column' => $header]),
                );
            }

            $limit = OpeningDataWorkbookSchema::ROW_LIMITS[$sheet];

            if (count($table['rows']) > $limit) {
                $issues[] = $this->issue(
                    $sheet,
                    null,
                    null,
                    trans('app.opening_data.errors.row_limit', ['limit' => $limit]),
                );
            }

            foreach ($table['rows'] as $row) {
                $validator = Validator::make(
                    $row,
                    $this->rules($sheet),
                    [],
                    $this->attributes($sheet),
                );

                foreach ($validator->errors()->toArray() as $field => $messages) {
                    foreach ($messages as $message) {
                        $issues[] = $this->issue(
                            $sheet,
                            (int) ($row['_row'] ?? 0),
                            $field,
                            $message,
                        );
                    }
                }
            }
        }

        if (($tables['Assets']['rows'] ?? []) === []) {
            $issues[] = $this->issue(
                'Assets',
                null,
                null,
                trans('app.opening_data.errors.assets_required'),
            );
        }

        return $issues;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(string $sheet): array
    {
        return match ($sheet) {
            'Assets' => [
                'code' => ['required', 'string', 'max:255'],
                'parent_code' => ['nullable', 'string', 'max:255', 'different:code'],
                'asset_type' => ['required', Rule::in(AssetOptions::TYPES)],
                'usage_type' => ['required', Rule::in(AssetOptions::USAGES)],
                'title_en' => ['required', 'string', 'max:255'],
                'title_ar' => ['required', 'string', 'max:255'],
                'status' => ['required', Rule::in(AssetOptions::MUTABLE_STATUSES)],
                'occupancy_status' => ['required', Rule::in(AssetOptions::OCCUPANCIES)],
                'rentable' => ['required', 'boolean'],
                'valuation_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
                'area' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
                'level_label' => ['nullable', 'string', 'max:255'],
                'unit_label' => ['nullable', 'string', 'max:255'],
                'address_en' => ['nullable', 'string', 'max:5000'],
                'address_ar' => ['nullable', 'string', 'max:5000'],
                'description_en' => ['nullable', 'string', 'max:10000'],
                'description_ar' => ['nullable', 'string', 'max:10000'],
                'latitude' => ['nullable', 'numeric', 'between:-90,90'],
                'longitude' => ['nullable', 'numeric', 'between:-180,180'],
                'zone_en' => ['nullable', 'string', 'max:255'],
                'zone_ar' => ['nullable', 'string', 'max:255'],
            ],
            'Tenants' => [
                'email' => ['required', 'email', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'preferred_locale' => ['required', Rule::in(TenantOptions::LOCALES)],
                'profile_type' => ['required', Rule::in(TenantOptions::PROFILE_TYPES)],
                'national_id' => ['nullable', 'string', 'max:255'],
                'company_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:5000'],
                'status' => ['required', Rule::in(TenantOptions::STATUSES)],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],
            'Leases' => [
                'code' => ['required', 'string', 'max:255'],
                'asset_code' => ['required', 'string', 'max:255'],
                'tenant_email' => ['required', 'email', 'max:255'],
                'status' => ['required', Rule::in(LeaseOptions::CREATE_STATUSES)],
                'payment_frequency' => ['required', Rule::in(LeaseOptions::PAYMENT_FREQUENCIES)],
                'started_at' => ['required', 'date_format:Y-m-d'],
                'ends_at' => ['required', 'date_format:Y-m-d', 'after:started_at'],
                'signed_at' => ['nullable', 'date_format:Y-m-d'],
                'rent_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'deposit_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'tax_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'discount_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
                'billing_day' => ['nullable', 'integer', 'between:1,31'],
                'renewal_notice_days' => ['required', 'integer', 'between:0,3650'],
                'terms_en' => ['required', 'string', 'max:50000'],
                'terms_ar' => ['required', 'string', 'max:50000'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],
            'Payments' => [
                'lease_code' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:255'],
                'received_on' => ['required', 'date_format:Y-m-d'],
                'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
                'method' => ['required', Rule::in(PaymentOptions::METHODS)],
                'type' => ['required', Rule::in(PaymentOptions::TYPES)],
                'notes' => ['nullable', 'string', 'max:5000'],
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    private function attributes(string $sheet): array
    {
        $attributes = [];

        foreach (OpeningDataWorkbookSchema::SHEETS[$sheet] ?? [] as $field) {
            $translated = trans("app.opening_data.columns.{$field}");
            $attributes[$field] = is_string($translated) ? $translated : $field;
        }

        return $attributes;
    }

    /**
     * @return array{sheet:string,row:int|null,field:string|null,message:string}
     */
    private function issue(
        string $sheet,
        ?int $row,
        ?string $field,
        mixed $message,
    ): array {
        return [
            'sheet' => $sheet,
            'row' => $row ?: null,
            'field' => $field,
            'message' => is_string($message) ? $message : (string) $message,
        ];
    }
}
