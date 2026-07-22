<?php

namespace App\Modules\Leases\Requests;

use App\Models\Lease;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Shared\Rules\ValidPdf;
use Illuminate\Foundation\Http\FormRequest;

final class UploadSignedContractRequest extends FormRequest
{
    use HasLeaseValidationAttributes;

    public function authorize(): bool
    {
        $actor = $this->user();
        $lease = $this->route('lease');

        return $actor !== null
            && $lease instanceof Lease
            && app(LeaseAccess::class)->canManage($actor, $lease);
    }

    /**
     * @return array<string, array<int, mixed>>
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
                new ValidPdf,
            ],
        ];
    }
}
