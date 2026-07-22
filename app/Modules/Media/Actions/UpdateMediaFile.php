<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Data\MediaRelocation;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaAttributes;
use App\Modules\Media\Support\MediaFileStorage;
use App\Modules\Media\Support\MediaInputGuard;
use App\Modules\Media\Support\MediaOptions;
use App\Modules\Media\Support\MediaPortfolioResolver;
use App\Modules\Media\Support\MediaUsage;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdateMediaFile
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly MediaInputGuard $input,
        private readonly MediaPortfolioResolver $portfolios,
        private readonly MediaUsage $usage,
        private readonly MediaFileStorage $files,
        private readonly MediaAttributes $attributes,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, MediaFile $mediaFile, array $data): MediaFile
    {
        $this->access->ensureCanManage($actor, $mediaFile);
        $data = $this->input->validateUpdate($data);
        $relocation = null;

        try {
            $updated = DB::transaction(function () use ($actor, $mediaFile, $data, &$relocation): MediaFile {
                $locked = MediaFile::query()->lockForUpdate()->whereKey($mediaFile->id)->firstOrFail();
                $this->access->ensureCanManage($actor, $locked);
                $requestedPortfolioId = array_key_exists('portfolio_id', $data)
                    ? $data['portfolio_id']
                    : $locked->portfolio_id;
                $portfolioId = $this->portfolios->forUpdate(
                    $actor,
                    $locked->portfolio_id,
                    $requestedPortfolioId,
                    lock: true,
                );
                $visibility = (string) $data['visibility'];

                if ($portfolioId !== $locked->portfolio_id
                    || MediaOptions::diskFor($visibility) !== $locked->disk) {
                    $this->usage->ensureUnused($locked);
                }

                $relocation = $this->files->prepareRelocation($locked, $portfolioId, $visibility);
                $locked->update($this->attributes->forUpdate($locked, $portfolioId, $relocation, $data));

                return $locked->fresh(['portfolio', 'uploadedBy']);
            });
        } catch (Throwable $exception) {
            if ($relocation instanceof MediaRelocation) {
                $this->files->discardTarget($relocation);
            }

            throw $exception;
        }

        if ($relocation instanceof MediaRelocation) {
            $this->files->removeSource($relocation);
        }

        return $updated;
    }
}
