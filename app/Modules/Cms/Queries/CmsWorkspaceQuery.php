<?php

namespace App\Modules\Cms\Queries;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use App\Modules\Cms\Support\CmsOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class CmsWorkspaceQuery
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly CmsPageDirectoryQuery $pages,
        private readonly CmsWorkspaceInsightsQuery $insights,
        private readonly CmsSectionLibraryQuery $sections,
        private readonly CmsNavigationDirectoryQuery $navigation,
    ) {}

    /** @return array<string, mixed> */
    public function handle(Request $request, User $actor): array
    {
        $this->access->ensureAdmin($actor);
        $view = (string) $request->query('view', 'pages');
        $view = in_array($view, CmsOptions::WORKSPACE_VIEWS, true) ? $view : 'pages';

        return [
            'view' => $view,
            ...$this->pages->handle($request),
            'workspaceStats' => $this->insights->handle(),
            ...$this->sections->handle(),
            ...$this->navigation->handle(),
        ];
    }

    /** @return Builder<CmsPage> */
    public function forExport(Request $request, User $actor): Builder
    {
        $this->access->ensureAdmin($actor);

        return $this->pages->forExport($request);
    }
}
