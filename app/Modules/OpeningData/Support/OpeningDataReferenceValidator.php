<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Portfolio;

final class OpeningDataReferenceValidator
{
    public function __construct(
        private readonly OpeningDataDuplicateValidator $duplicates,
        private readonly OpeningDataAssetReferenceValidator $assets,
        private readonly OpeningDataLeaseReferenceValidator $leases,
        private readonly OpeningDataPaymentReferenceValidator $payments,
    ) {}

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $data
     * @return array<int, array{sheet:string,row:int|null,field:string|null,message:string}>
     */
    public function validate(Portfolio $portfolio, array $data): array
    {
        return [
            ...$this->duplicates->validate($data),
            ...$this->assets->validate($portfolio, $data['Assets'] ?? []),
            ...$this->leases->validate(
                $portfolio,
                $data['Assets'] ?? [],
                $data['Tenants'] ?? [],
                $data['Leases'] ?? [],
            ),
            ...$this->payments->validate(
                $portfolio,
                $data['Leases'] ?? [],
                $data['Payments'] ?? [],
            ),
        ];
    }
}
