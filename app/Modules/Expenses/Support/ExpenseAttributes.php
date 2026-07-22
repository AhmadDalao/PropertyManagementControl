<?php

namespace App\Modules\Expenses\Support;

use App\Models\Portfolio;
use App\Models\User;

final class ExpenseAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array{asset_id:int|null,maintenance_request_id:int|null}  $references
     * @return array<string, mixed>
     */
    public function forCreate(User $actor, Portfolio $portfolio, array $data, array $references): array
    {
        return [
            'portfolio_id' => $portfolio->id,
            'asset_id' => $references['asset_id'],
            'maintenance_request_id' => $references['maintenance_request_id'],
            'created_by_user_id' => $actor->id,
            'currency' => strtoupper($portfolio->default_currency ?: 'SAR'),
            ...$this->mutable($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{asset_id:int|null,maintenance_request_id:int|null}  $references
     * @return array<string, mixed>
     */
    public function forUpdate(array $data, array $references): array
    {
        return [
            'asset_id' => $references['asset_id'],
            'maintenance_request_id' => $references['maintenance_request_id'],
            ...$this->mutable($data),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mutable(array $data): array
    {
        return [
            'category' => $data['category'],
            'title' => trim((string) $data['title']),
            'description' => $this->optional($data['description'] ?? null),
            'incurred_on' => $data['incurred_on'],
            'amount' => $data['amount'],
            'vendor_name' => $this->optional($data['vendor_name'] ?? null),
            'status' => $data['status'],
        ];
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
