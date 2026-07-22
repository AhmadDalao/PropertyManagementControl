<?php

namespace App\Modules\Tenants\Support;

final class TenantProfileAttributes
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function from(array $data): array
    {
        return [
            'profile_type' => $data['profile_type'],
            'national_id' => $this->optional($data['national_id'] ?? null),
            'company_name' => $this->optional($data['company_name'] ?? null),
            'emergency_contact_name' => $this->optional($data['emergency_contact_name'] ?? null),
            'emergency_contact_phone' => $this->optional($data['emergency_contact_phone'] ?? null),
            'address' => $this->optional($data['address'] ?? null),
            'status' => $data['status'],
            'notes' => $this->optional($data['notes'] ?? null),
        ];
    }

    private function optional(mixed $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : '';

        return $normalized !== '' ? $normalized : null;
    }
}
