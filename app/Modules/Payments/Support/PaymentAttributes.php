<?php

namespace App\Modules\Payments\Support;

use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

final class PaymentAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forCreate(User $actor, Lease $lease, array $data): array
    {
        return [
            'portfolio_id' => $lease->portfolio_id,
            'lease_id' => $lease->id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'recorded_by_user_id' => $actor->id,
            'reference' => $this->optional($data['reference'] ?? null) ?? $this->nextReference(),
            'type' => $data['type'],
            'method' => $data['method'],
            'status' => $data['status'],
            'received_on' => $data['received_on'],
            'amount' => $data['amount'],
            'currency' => Str::upper((string) $lease->currency),
            'notes' => $this->notes($data),
        ];
    }

    /** @param array<string, mixed> $data */
    public function notes(array $data): ?string
    {
        return $this->optional($data['notes'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function forUpdate(array $data): array
    {
        return [
            'status' => $data['status'],
            'notes' => $this->notes($data),
        ];
    }

    private function nextReference(): string
    {
        do {
            $reference = 'PAY-'.Str::upper(Str::random(10));
        } while (Payment::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
