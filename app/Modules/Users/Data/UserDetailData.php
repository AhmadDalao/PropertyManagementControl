<?php

namespace App\Modules\Users\Data;

use App\Models\AssetStakeholder;
use App\Models\Document;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class UserDetailData
{
    /**
     * @param  Collection<int, AssetStakeholder>  $stakeholders
     * @param  Collection<int, MaintenanceRequest>  $maintenance
     * @param  Collection<int, Document>  $documents
     */
    public function __construct(
        public User $user,
        public Collection $stakeholders,
        public Collection $maintenance,
        public Collection $documents,
    ) {}
}
