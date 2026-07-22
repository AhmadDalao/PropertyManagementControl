<?php

namespace App\Modules\Leases\Presenters;

use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Queries\LeaseFormOptionsQuery;
use App\Modules\Leases\Support\LeaseAccess;

final class LeaseFormPresenter
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly LeaseFormOptionsQuery $options,
        private readonly LeaseCreateFormPresenter $create,
        private readonly LeaseEditFormPresenter $edit,
        private readonly LeaseRenewalFormPresenter $renewal,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Lease $lease = null, array $defaults = []): array
    {
        if ($lease) {
            $this->access->ensureCanManage($actor, $lease);

            return $this->edit->present($lease);
        }

        $this->access->ensureManager($actor);

        return $this->create->present($this->options->get($actor, defaults: $defaults));
    }

    /** @return array<string, mixed> */
    public function renew(User $actor, Lease $lease): array
    {
        return $this->renewal->present($actor, $lease);
    }
}
