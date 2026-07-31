<?php

namespace App\Modules\Assets\Requests;

use App\Modules\Assets\Support\AssetOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class PropertyExplorerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'property_id' => ['nullable', 'integer', 'min:1', 'exists:assets,id'],
            'node_id' => ['nullable', 'integer', 'min:1', 'exists:assets,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'asset_type' => ['nullable', Rule::in(['all', ...AssetOptions::TYPES])],
            'occupancy_status' => ['nullable', Rule::in(['all', ...AssetOptions::OCCUPANCIES])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     property_id:int|null,
     *     node_id:int|null,
     *     search:string,
     *     asset_type:string,
     *     occupancy_status:string,
     *     page:int
     * }
     */
    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'property_id' => isset($validated['property_id']) ? (int) $validated['property_id'] : null,
            'node_id' => isset($validated['node_id']) ? (int) $validated['node_id'] : null,
            'search' => trim((string) ($validated['search'] ?? '')),
            'asset_type' => (string) ($validated['asset_type'] ?? 'all'),
            'occupancy_status' => (string) ($validated['occupancy_status'] ?? 'all'),
            'page' => max(1, (int) ($validated['page'] ?? 1)),
        ];
    }
}
