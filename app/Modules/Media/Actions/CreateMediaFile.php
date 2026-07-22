<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaAttributes;
use App\Modules\Media\Support\MediaFileStorage;
use App\Modules\Media\Support\MediaInputGuard;
use App\Modules\Media\Support\MediaPortfolioResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CreateMediaFile
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly MediaInputGuard $input,
        private readonly MediaPortfolioResolver $portfolios,
        private readonly MediaFileStorage $files,
        private readonly MediaAttributes $attributes,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data): MediaFile
    {
        $this->access->ensureManager($actor);
        $data = $this->input->validateCreate($data);
        $requestedPortfolioId = array_key_exists('portfolio_id', $data)
            ? $data['portfolio_id']
            : $actor->portfolio_id;
        $portfolioId = $this->portfolios->forCreate($actor, $requestedPortfolioId);
        $file = $data['file'];

        if (! $file instanceof UploadedFile) {
            throw new \LogicException('Validated media input did not contain an uploaded file.');
        }

        $storedFile = $this->files->store($file, $portfolioId, (string) $data['visibility']);

        try {
            return DB::transaction(function () use ($actor, $data, $portfolioId, $storedFile): MediaFile {
                $lockedPortfolioId = $this->portfolios->forCreate($actor, $portfolioId, lock: true);

                return MediaFile::query()
                    ->create($this->attributes->forCreate($actor, $lockedPortfolioId, $storedFile, $data))
                    ->load(['portfolio', 'uploadedBy']);
            }, 3);
        } catch (Throwable $exception) {
            $this->files->delete($storedFile->disk, $storedFile->path);

            throw $exception;
        }
    }
}
