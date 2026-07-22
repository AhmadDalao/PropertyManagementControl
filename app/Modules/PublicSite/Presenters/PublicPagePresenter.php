<?php

namespace App\Modules\PublicSite\Presenters;

use App\Modules\PublicSite\Queries\PublicPageQuery;
use App\Modules\PublicSite\Support\LandingContentCatalog;

class PublicPagePresenter
{
    public function __construct(
        private readonly PublicPageQuery $pages,
        private readonly LandingContentCatalog $catalog,
    ) {}

    /** @return array<string, mixed> */
    public function homepage(): array
    {
        return $this->pages->homepage()?->toArray()
            ?? $this->catalog->fallbackPage();
    }

    /** @return array<string, mixed> */
    public function bySlug(string $slug): array
    {
        return $this->pages->bySlug($slug)->toArray();
    }
}
