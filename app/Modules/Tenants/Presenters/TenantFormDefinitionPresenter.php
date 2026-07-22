<?php

namespace App\Modules\Tenants\Presenters;

use App\Modules\Tenants\Data\TenantFormData;

final class TenantFormDefinitionPresenter
{
    public function __construct(
        private readonly TenantFormFieldsPresenter $fields,
        private readonly TenantFormValuesPresenter $values,
    ) {}

    /** @return array{fields:array<int, array<string, mixed>>,initialValues:array<string, mixed>} */
    public function present(TenantFormData $data): array
    {
        return [
            'fields' => $this->fields->present($data),
            'initialValues' => $this->values->present($data),
        ];
    }
}
