<?php

namespace App\Modules\Expenses\Presenters;

use App\Modules\Expenses\Data\ExpenseDetailData;
use App\Modules\Expenses\Support\ExpenseEvidenceLinks;
use App\Modules\Shared\ResourcePresenter;

final class ExpenseEvidencePresenter
{
    public function __construct(
        private readonly ResourcePresenter $resources,
        private readonly ExpenseEvidenceLinks $links,
    ) {}

    /** @return array<string, mixed> */
    public function present(ExpenseDetailData $data): array
    {
        $expense = $data->expense;

        return [
            'enabled' => $data->documentsEnabled,
            'can_upload' => $data->documentsEnabled && $expense->portfolio?->status === 'active',
            'upload_url' => $data->documentsEnabled ? $this->links->upload($expense) : null,
            'documents' => $data->documentsEnabled
                ? $this->resources->documentStrip($expense->documents)
                : [],
        ];
    }
}
