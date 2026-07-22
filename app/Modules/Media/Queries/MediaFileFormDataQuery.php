<?php

namespace App\Modules\Media\Queries;

use App\Models\MediaFile;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Media\Data\MediaFileFormData;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;

final class MediaFileFormDataQuery
{
    public function __construct(
        private readonly MediaAccess $access,
        private readonly PortfolioScope $portfolios,
    ) {}

    /** @param array<string, mixed> $defaults */
    public function get(User $actor, ?MediaFile $mediaFile = null, array $defaults = []): MediaFileFormData
    {
        $mediaFile
            ? $this->access->ensureCanManage($actor, $mediaFile)
            : $this->access->ensureManager($actor);

        return new MediaFileFormData(
            actor: $actor,
            mediaFile: $mediaFile,
            portfolioOptions: $this->portfolioOptions($actor, $mediaFile),
            defaults: $defaults,
        );
    }

    /** @return array<int, array{id:int,name:string}> */
    private function portfolioOptions(User $actor, ?MediaFile $mediaFile): array
    {
        if (! $actor->hasRole('superadmin')) {
            return [];
        }

        $nameColumn = app()->isLocale('ar') ? 'name_ar' : 'name_en';

        return $this->portfolios
            ->apply(Portfolio::query(), $actor, 'id')
            ->where(function (Builder $portfolios) use ($mediaFile): void {
                $portfolios->where('status', 'active');

                if ($mediaFile?->portfolio_id !== null) {
                    $portfolios->orWhere('id', $mediaFile->portfolio_id);
                }
            })
            ->orderBy($nameColumn)
            ->get(['id', 'name_en', 'name_ar'])
            ->map(fn (Portfolio $portfolio): array => [
                'id' => $portfolio->id,
                'name' => $this->portfolios->localized($portfolio->name_en, $portfolio->name_ar)
                    ?? "#{$portfolio->id}",
            ])
            ->all();
    }
}
