<?php

namespace App\Modules\Assets\Support;

use App\Models\Asset;
use Illuminate\Validation\ValidationException;

class AssetInputGuard
{
    /** @param array<string, mixed> $data */
    public function ensureCreate(array $data): void
    {
        $this->ensureOptions($data, AssetOptions::MUTABLE_STATUSES);
    }

    /** @param array<string, mixed> $data */
    public function ensureUpdate(Asset $asset, array $data): void
    {
        $statuses = $asset->status === 'archived'
            ? ['archived']
            : AssetOptions::MUTABLE_STATUSES;
        $this->ensureOptions($data, $statuses);

        if ($asset->status === 'archived' && ($data['status'] ?? null) !== 'archived') {
            $this->fail('status', trans('app.errors.asset_archived_locked'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $statuses
     */
    private function ensureOptions(array $data, array $statuses): void
    {
        $this->ensureIn('asset_type', $data, AssetOptions::TYPES);
        $this->ensureIn('usage_type', $data, AssetOptions::USAGES);
        $this->ensureIn('status', $data, $statuses);
        $this->ensureIn('occupancy_status', $data, AssetOptions::OCCUPANCIES);

        foreach (['valuation_amount', 'area'] as $field) {
            if (isset($data[$field]) && (float) $data[$field] < 0) {
                $this->fail($field, trans('validation.min.numeric', [
                    'attribute' => trans("app.assets.{$field}"),
                    'min' => 0,
                ]));
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $allowed
     */
    private function ensureIn(string $field, array $data, array $allowed): void
    {
        if (! isset($data[$field]) || ! in_array($data[$field], $allowed, true)) {
            $this->fail($field, trans('validation.in', [
                'attribute' => trans("app.assets.{$field}"),
            ]));
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
