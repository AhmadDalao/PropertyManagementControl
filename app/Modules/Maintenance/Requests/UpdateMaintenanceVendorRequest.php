<?php

namespace App\Modules\Maintenance\Requests;

use App\Modules\Maintenance\Support\MaintenanceOptions;
use App\Modules\Maintenance\Support\MaintenanceVendorOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMaintenanceVendorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'service_category' => ['required', Rule::in(MaintenanceOptions::CATEGORIES)],
            'status' => ['required', Rule::in(MaintenanceVendorOptions::STATUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => trans('app.maintenance_vendors.name'),
            'contact_name' => trans('app.maintenance_vendors.contact_name'),
            'phone' => trans('app.maintenance_vendors.phone'),
            'email' => trans('app.maintenance_vendors.email'),
            'service_category' => trans('app.maintenance_vendors.category'),
            'status' => trans('app.maintenance_vendors.status'),
            'notes' => trans('app.maintenance_vendors.notes'),
        ];
    }
}
