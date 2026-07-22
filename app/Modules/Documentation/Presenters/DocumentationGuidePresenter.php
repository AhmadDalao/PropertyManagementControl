<?php

namespace App\Modules\Documentation\Presenters;

use App\Models\User;
use App\Modules\Documentation\Support\DocumentationAccess;
use App\Modules\Documentation\Support\DocumentationCatalog;

class DocumentationGuidePresenter
{
    public function __construct(
        private readonly DocumentationCatalog $catalog,
        private readonly DocumentationAccess $access,
    ) {}

    /** @return array<string, mixed>|null */
    public function present(User $actor, string $slug): ?array
    {
        $guides = $this->catalog->items($actor, 'guides');
        $selected = $guides->firstWhere('slug', $slug);

        if (! is_array($selected)) {
            return null;
        }

        return [
            'audience' => $this->access->primaryRole($actor),
            'guide' => $selected,
            'relatedGuides' => $guides
                ->where('slug', '!=', $slug)
                ->take(4)
                ->values()
                ->all(),
        ];
    }
}
