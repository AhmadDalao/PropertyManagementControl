<?php

namespace App\Http\Controllers;

use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NavigationItemController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:navigation_items,id'],
            'cms_page_id' => ['nullable', 'integer', 'exists:cms_pages,id'],
            'location' => ['required', 'string'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $this->ensureNavigationParentIsValid($data['parent_id'] ?? null);

        NavigationItem::query()->create([
            ...$data,
            'target' => $data['target'] ?? '_self',
            'sort_order' => $data['sort_order'] ?? 0,
            'is_visible' => (bool) ($data['is_visible'] ?? true),
        ]);

        return to_route('cms.index')->with('success', 'Navigation item created.');
    }

    public function update(Request $request, NavigationItem $navigationItem): RedirectResponse
    {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:navigation_items,id'],
            'cms_page_id' => ['nullable', 'integer', 'exists:cms_pages,id'],
            'location' => ['required', 'string'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $this->ensureNavigationParentIsValid($data['parent_id'] ?? null, $navigationItem);

        $navigationItem->update([
            ...$data,
            'target' => $data['target'] ?? '_self',
            'is_visible' => (bool) ($data['is_visible'] ?? true),
        ]);

        return to_route('cms.index')->with('success', 'Navigation item updated.');
    }

    public function destroy(NavigationItem $navigationItem): RedirectResponse
    {
        $this->requireRoles($this->actor(request()), ['superadmin']);
        $navigationItem->delete();

        return to_route('cms.index')->with('success', 'Navigation item deleted.');
    }

    private function ensureNavigationParentIsValid(?int $parentId, ?NavigationItem $navigationItem = null): void
    {
        if (! $parentId || ! $navigationItem) {
            return;
        }

        abort_if($parentId === $navigationItem->id, 422, 'A navigation item cannot be its own parent.');

        $childrenIds = $navigationItem->children()->pluck('id')->all();
        abort_if(in_array($parentId, $childrenIds, true), 422, 'A navigation item cannot be moved under its child.');
    }
}
