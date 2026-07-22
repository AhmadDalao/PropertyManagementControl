<?php

namespace App\Modules\Cms\Support;

use App\Models\CmsPage;
use Illuminate\Validation\ValidationException;

final class NavigationDestination
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function resolve(array $data): array
    {
        $pageId = isset($data['cms_page_id']) ? (int) $data['cms_page_id'] : null;

        if (! $pageId) {
            return $data;
        }

        $page = CmsPage::query()->lockForUpdate()->findOrFail($pageId);

        if (($data['is_visible'] ?? true) && ($page->status !== 'published' || ! $page->is_visible)) {
            throw ValidationException::withMessages([
                'cms_page_id' => trans('app.errors.navigation_public_page_required'),
            ]);
        }

        $data['url'] = $page->is_homepage ? '/' : "/pages/{$page->slug}";

        return $data;
    }
}
