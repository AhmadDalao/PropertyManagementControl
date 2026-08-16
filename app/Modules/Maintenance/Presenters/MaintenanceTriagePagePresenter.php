<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Models\User;

final class MaintenanceTriagePagePresenter
{
    public function __construct(
        private readonly MaintenanceFormPresenter $form,
        private readonly MaintenanceDetailPresenter $detail,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, MaintenanceRequest $request): array
    {
        return [
            'formPage' => $this->form->present($actor, $request),
            'detailPage' => $this->detail->present($request, $actor),
        ];
    }
}
