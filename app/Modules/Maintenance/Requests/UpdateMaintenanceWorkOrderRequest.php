<?php

namespace App\Modules\Maintenance\Requests;

use App\Modules\Maintenance\Support\MaintenanceWorkOrderOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMaintenanceWorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();

        return $actor !== null
            && $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'vendor_id' => ['required', 'integer', 'exists:maintenance_vendors,id'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(MaintenanceWorkOrderOptions::STATUSES)],
            'scheduled_at' => ['nullable', 'date'],
            'estimated_amount' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,999999999999.99'],
            'final_amount' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,999999999999.99'],
            'scope' => ['required', 'string', 'max:5000'],
            'completion_notes' => ['nullable', 'string', 'max:5000'],
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
            'final_amount' => trans('app.work_orders.final_amount'),
            'completion_notes' => trans('app.work_orders.completion_notes'),
        ];
    }
}
