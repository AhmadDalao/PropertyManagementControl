<?php

namespace App\Modules\Cms\Actions;

use App\Models\CmsPage;
use App\Models\User;
use App\Modules\Cms\Queries\CmsWorkspaceQuery;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CmsPageWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly CmsWorkspaceQuery $cms,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('cms-pages', [
            'Title',
            'Arabic Title',
            'Slug',
            'Status',
            'Homepage',
            'Visible',
            'Published At',
        ], $this->cms->forExport($request, $actor), fn (CmsPage $page): array => [
            $page->title_en,
            $page->title_ar,
            $page->slug,
            $this->workbook->option($page->status),
            $this->workbook->yesNo($page->is_homepage),
            $this->workbook->yesNo($page->is_visible),
            $this->workbook->date($page->published_at, true),
        ]);
    }
}
