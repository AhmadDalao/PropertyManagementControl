<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NavigationItemController extends Controller
{
    public function create(Request $request): Response
    {
        $this->requireRoles($this->actor($request), ['superadmin']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->navigationFormPage(),
        ]);
    }

    public function edit(Request $request, NavigationItem $navigationItem): Response
    {
        $this->requireRoles($this->actor($request), ['superadmin']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->navigationFormPage($navigationItem),
        ]);
    }

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

        return to_route('cms.index')->with('success', trans('app.messages.navigation_created'));
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

        return to_route('cms.index')->with('success', trans('app.messages.navigation_updated'));
    }

    public function destroy(NavigationItem $navigationItem): RedirectResponse
    {
        $this->requireRoles($this->actor(request()), ['superadmin']);
        $navigationItem->delete();

        return to_route('cms.index')->with('success', trans('app.messages.navigation_deleted'));
    }

    private function ensureNavigationParentIsValid(?int $parentId, ?NavigationItem $navigationItem = null): void
    {
        if (! $parentId || ! $navigationItem) {
            return;
        }

        abort_if($parentId === $navigationItem->id, 422, trans('app.errors.navigation_self_parent'));

        $childrenIds = $navigationItem->children()->pluck('id')->all();
        abort_if(in_array($parentId, $childrenIds, true), 422, trans('app.errors.navigation_child_parent'));
    }

    private function navigationFormPage(?NavigationItem $navigationItem = null): array
    {
        $excludedIds = $navigationItem
            ? [$navigationItem->id, ...$navigationItem->children()->pluck('id')->all()]
            : [];
        $parentOptions = NavigationItem::query()
            ->whereNull('parent_id')
            ->whereNotIn('id', $excludedIds)
            ->orderBy('location')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (NavigationItem $item) => [
                'label' => $this->localized($item->title_en, $item->title_ar).' · '.$item->location,
                'value' => $item->id,
            ])
            ->prepend(['label' => 'No parent', 'value' => ''])
            ->values()
            ->all();
        $pageOptions = CmsPage::query()
            ->orderByDesc('is_homepage')
            ->orderBy('title_en')
            ->get()
            ->map(fn (CmsPage $page) => [
                'label' => $this->localized($page->title_en, $page->title_ar).' · /pages/'.$page->slug,
                'value' => $page->id,
            ])
            ->prepend(['label' => 'Custom URL', 'value' => ''])
            ->values()
            ->all();

        return [
            'title' => $navigationItem
                ? trans('app.actions.edit').' '.$this->localized($navigationItem->title_en, $navigationItem->title_ar)
                : 'Create navigation item',
            'description' => 'Add one clear bilingual link to the public header or footer.',
            'backHref' => route('cms.index'),
            'backLabel' => 'Website control',
            'action' => $navigationItem
                ? route('navigation-items.update', $navigationItem)
                : route('navigation-items.store'),
            'method' => $navigationItem ? 'put' : 'post',
            'submitLabel' => $navigationItem ? 'Update navigation' : 'Create navigation',
            'fields' => [
                ['name' => 'location', 'label' => 'Location', 'type' => 'select', 'required' => true, 'options' => [
                    ['label' => 'Header', 'value' => 'header'],
                    ['label' => 'Footer', 'value' => 'footer'],
                ]],
                ['name' => 'parent_id', 'label' => 'Parent item', 'type' => 'select', 'options' => $parentOptions],
                ['name' => 'cms_page_id', 'label' => 'Linked page', 'type' => 'select', 'options' => $pageOptions, 'help' => 'Choose a CMS page or leave this on Custom URL.'],
                ['name' => 'title_en', 'label' => 'English label', 'required' => true],
                ['name' => 'title_ar', 'label' => 'Arabic label', 'required' => true],
                ['name' => 'url', 'label' => 'Custom URL', 'help' => 'Used when no CMS page is selected.'],
                ['name' => 'target', 'label' => 'Open link', 'type' => 'select', 'options' => [
                    ['label' => 'Same tab', 'value' => '_self'],
                    ['label' => 'New tab', 'value' => '_blank'],
                ]],
                ['name' => 'sort_order', 'label' => 'Order', 'type' => 'number', 'min' => 0],
                ['name' => 'is_visible', 'label' => 'Visible on public website', 'type' => 'checkbox'],
            ],
            'initialValues' => [
                'location' => $navigationItem?->location ?? 'header',
                'parent_id' => $navigationItem?->parent_id ?? '',
                'cms_page_id' => $navigationItem?->cms_page_id ?? '',
                'title_en' => $navigationItem?->title_en ?? '',
                'title_ar' => $navigationItem?->title_ar ?? '',
                'url' => $navigationItem?->url ?? '/',
                'target' => $navigationItem?->target ?? '_self',
                'sort_order' => $navigationItem?->sort_order ?? 1,
                'is_visible' => (bool) ($navigationItem?->is_visible ?? true),
            ],
        ];
    }
}
