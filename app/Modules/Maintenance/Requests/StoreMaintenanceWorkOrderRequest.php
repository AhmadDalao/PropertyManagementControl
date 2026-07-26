<?php

namespace App\Modules\Maintenance\Requests;

use App\Models\MaintenanceRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMaintenanceWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $maintenance = $this->route('maintenanceRequest');

        return $actor !== null
            && $maintenance instanceof MaintenanceRequest
            && $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])
            && ($actor->hasRole('superadmin')
                || (int) $actor->portfolio_id === (int) $maintenance->portfolio_id);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->sharedRules([
            'required',
            Rule::in(['draft', 'scheduled']),
        ]);
    }

    /**
     * @param  array<int, mixed>  $statusRules
     * @return array<string, array<int, mixed>>
     */
    private function sharedRules(array $statusRules): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:maintenance_vendors,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => $statusRules,
            'scheduled_at' => ['nullable', 'date'],
            'estimated_amount' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,999999999999.99'],
            'scope' => ['required', 'string', 'max:5000'],
            'tenant_access_required' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'vendor_id' => trans('app.work_orders.vendor'),
            'assigned_to_user_id' => trans('app.work_orders.internal_owner'),
            'status' => trans('app.work_orders.status'),
            'scheduled_at' => trans('app.work_orders.scheduled_at'),
            'estimated_amount' => trans('app.work_orders.estimated_amount'),
            'scope' => trans('app.work_orders.scope'),
            'tenant_access_required' => trans('app.work_orders.tenant_access_required'),
        ];
    }
}
