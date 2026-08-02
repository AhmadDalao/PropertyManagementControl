<?php

namespace App\Modules\Leases\Actions;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Exports\Support\SimpleDocx;
use App\Modules\Leases\Presenters\LeaseContractWordPresenter;
use App\Modules\Leases\Support\LeaseAccess;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class LeaseContractWordExport
{
    public function __construct(
        private LeaseAccess $access,
        private LeaseContractWordPresenter $presenter,
        private SimpleDocx $documents,
    ) {}

    public function download(User $actor, Lease $lease): StreamedResponse
    {
        $this->access->ensureCanManage($actor, $lease);
        $lease->loadMissing(
            'tenantProfile.user',
            'leaseable',
            'installments',
            'portfolio.owner',
            'managedBy',
        );
        $path = $this->documents->create($this->presenter->present($lease));
        $content = (string) file_get_contents($path);
        @unlink($path);

        return response()->streamDownload(
            static fn () => print ($content),
            "lease-contract-{$lease->code}.docx",
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        );
    }
}
