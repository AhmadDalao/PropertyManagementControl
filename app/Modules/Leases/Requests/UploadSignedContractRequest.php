<?php

namespace App\Modules\Leases\Requests;

use App\Models\Lease;
use Illuminate\Foundation\Http\FormRequest;

class UploadSignedContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $lease = $this->route('lease');

        return $actor !== null
            && $lease instanceof Lease
            && $actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])
            && ($actor->hasRole('superadmin') || $actor->portfolio_id === $lease->portfolio_id);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'signed_contract' => [
                'required',
                'file',
                'extensions:pdf',
                'mimes:pdf',
                'mimetypes:application/pdf',
                'max:10240',
            ],
        ];
    }
}
