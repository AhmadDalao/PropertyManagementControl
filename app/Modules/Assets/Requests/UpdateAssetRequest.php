<?php

namespace App\Modules\Assets\Requests;

use App\Models\Asset;
use App\Modules\Assets\Support\AssetOptions;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    use HasAssetValidationAttributes;

    public function authorize(): bool
    {
        $user = $this->user();
        $asset = $this->route('asset');

        if (! $user?->hasAnyRole(['superadmin', 'owner', 'property_manager']) || ! $asset instanceof Asset) {
            return false;
        }

        return $user->hasRole('superadmin') || $user->portfolio_id === $asset->portfolio_id;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $asset = $this->route('asset');
        $statuses = $asset instanceof Asset && $asset->status === 'archived'
            ? ['archived']
            : AssetOptions::MUTABLE_STATUSES;

        return $this->assetRules($statuses);
    }
}
