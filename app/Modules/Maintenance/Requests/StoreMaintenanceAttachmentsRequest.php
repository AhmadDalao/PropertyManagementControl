<?php

namespace App\Modules\Maintenance\Requests;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Modules\Maintenance\Support\MaintenanceAccess;
use App\Modules\Maintenance\Support\MaintenanceAttachmentRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceAttachmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $maintenanceRequest = $this->route('maintenanceRequest');

        if (! $actor instanceof User || ! $maintenanceRequest instanceof MaintenanceRequest) {
            return false;
        }

        app(MaintenanceAccess::class)->ensureCanAccess($actor, $maintenanceRequest);

        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return MaintenanceAttachmentRules::required();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return MaintenanceAttachmentRules::attributes();
    }
}
