<?php

namespace App\Modules\Assets\Actions;

use App\Models\Asset;
use App\Models\User;
use App\Modules\Assets\Support\AssetAccess;
use App\Modules\Assets\Support\AssetAttributes;
use App\Modules\Assets\Support\AssetInputGuard;
use App\Modules\Assets\Support\AssetMetadata;
use App\Modules\Assets\Support\AssetReferenceGuard;
use App\Modules\Assets\Support\AssetStakeholderManager;
use Illuminate\Support\Facades\DB;

class UpdateAsset
{
    public function __construct(
        private readonly AssetAccess $access,
        private readonly AssetAttributes $attributes,
        private readonly AssetInputGuard $input,
        private readonly AssetReferenceGuard $references,
        private readonly AssetStakeholderManager $stakeholders,
        private readonly AssetMetadata $metadata,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, Asset $asset, array $data): Asset
    {
        $this->access->ensureCanManage($actor, $asset);

        return DB::transaction(function () use ($actor, $asset, $data): Asset {
            $locked = Asset::query()->lockForUpdate()->findOrFail($asset->id);
            $this->access->ensureCanManage($actor, $locked);
            $this->input->ensureUpdate($locked, $data);
            $this->references->ensure($data, $locked->portfolio_id, $locked);
            $locked->update([
                ...$this->attributes->from($data),
                'meta_json' => $this->metadata->merge($data, $locked),
            ]);
            $this->stakeholders->sync(
                $locked,
                $this->nullableId($data['primary_owner_user_id'] ?? null),
                $this->nullableId($data['primary_manager_user_id'] ?? null),
            );

            return $locked->refresh();
        }, 3);
    }

    private function nullableId(mixed $value): ?int
    {
        return filled($value) ? (int) $value : null;
    }
}
