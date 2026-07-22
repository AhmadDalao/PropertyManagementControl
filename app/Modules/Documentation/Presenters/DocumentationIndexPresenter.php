<?php

namespace App\Modules\Documentation\Presenters;

use App\Models\User;
use App\Modules\Documentation\Support\DocumentationAccess;
use App\Modules\Documentation\Support\DocumentationCatalog;

class DocumentationIndexPresenter
{
    public function __construct(
        private readonly DocumentationCatalog $catalog,
        private readonly DocumentationAccess $access,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor): array
    {
        $audience = $this->access->primaryRole($actor);
        $roleGuide = $this->catalog
            ->items($actor, 'role_guides', false)
            ->firstWhere('role', $audience);

        return [
            'audience' => $audience,
            'roleGuide' => is_array($roleGuide) ? $roleGuide : null,
            'guides' => $this->catalog->items($actor, 'guides')->all(),
            'quickStarts' => $this->catalog->items($actor, 'quick_starts')->all(),
            'workflowTracks' => $this->catalog->items($actor, 'workflows')->all(),
            'pageShortcuts' => $this->catalog->items($actor, 'page_shortcuts')->all(),
            'controlChecks' => $this->catalog->items($actor, 'control_checks')->all(),
            'moduleStatus' => $this->access->moduleStatus($actor),
        ];
    }
}
