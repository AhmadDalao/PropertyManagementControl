<?php

namespace App\Modules\Users\Requests;

use App\Models\User;
use App\Modules\Users\Support\UserAccess;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateManagerPropertyAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $manager = $this->route('user');

        return $actor instanceof User
            && $manager instanceof User
            && $actor->hasAnyRole(['superadmin', 'owner'])
            && $manager->hasRole('property_manager')
            && app(UserAccess::class)->canManage($actor, $manager);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'asset_ids' => ['present', 'array', 'max:250'],
            'asset_ids.*' => ['integer', 'distinct', 'exists:assets,id'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'asset_ids' => trans('app.users.assigned_properties'),
            'asset_ids.*' => trans('app.users.assigned_property'),
        ];
    }
}
