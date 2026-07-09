<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CmsPageController extends Controller
{
    public function home(): Response
    {
        $page = CmsPage::query()
            ->where('is_homepage', true)
            ->where('status', 'published')
            ->with([
                'pageSections' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->with(['section' => fn ($query) => $query->where('status', 'active')]),
                'navigationItems',
            ])
            ->first();

        return Inertia::render('public/home', [
            'page' => $page,
        ]);
    }

    public function show(string $slug): Response
    {
        $page = CmsPage::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'pageSections' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->with(['section' => fn ($query) => $query->where('status', 'active')]),
            ])
            ->firstOrFail();

        return Inertia::render('public/page', [
            'page' => $page,
        ]);
    }

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $filters = $this->tableFilters($request, ['status' => 'all']);
        $baseQuery = CmsPage::query();
        $pages = (clone $baseQuery)->with(['pageSections.section']);

        $this->applyExactFilter($pages, $filters, 'status');
        $this->applySearch($pages, $filters['search'], [
            'title_en',
            'title_ar',
            'slug',
            'excerpt_en',
            'excerpt_ar',
        ]);

        return Inertia::render('admin/cms/index', [
            'pages' => $this->paginateTable($pages, $request, $filters, [
                'created_at',
                'title_en',
                'slug',
                'status',
            ]),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['draft', 'published', 'archived'], $filters),
            'pageOptions' => CmsPage::query()->orderBy('title_en')->get(['id', 'title_en']),
            'builderPages' => CmsPage::query()
                ->with(['pageSections' => fn ($query) => $query->with('section')->orderBy('sort_order')])
                ->orderByDesc('is_homepage')
                ->orderBy('title_en')
                ->get(),
            'sections' => CmsSection::query()->latest()->get(),
            'navigationItems' => NavigationItem::query()->with('children')->whereNull('parent_id')->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:255', 'unique:cms_pages,slug'],
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string'],
            'excerpt_ar' => ['nullable', 'string'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_title_ar' => ['nullable', 'string', 'max:255'],
            'seo_description_en' => ['nullable', 'string'],
            'seo_description_ar' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'is_homepage' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        CmsPage::query()->create([
            ...$data,
            'slug' => $data['slug'] ?: Str::slug($data['title_en']),
            'is_homepage' => (bool) ($data['is_homepage'] ?? false),
            'is_visible' => (bool) ($data['is_visible'] ?? true),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return to_route('cms.index')->with('success', 'Page created successfully.');
    }

    public function update(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_ar' => ['required', 'string', 'max:255'],
            'excerpt_en' => ['nullable', 'string'],
            'excerpt_ar' => ['nullable', 'string'],
            'seo_title_en' => ['nullable', 'string', 'max:255'],
            'seo_title_ar' => ['nullable', 'string', 'max:255'],
            'seo_description_en' => ['nullable', 'string'],
            'seo_description_ar' => ['nullable', 'string'],
            'status' => ['required', 'string'],
            'is_homepage' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        $cmsPage->update([
            ...$data,
            'is_homepage' => (bool) ($data['is_homepage'] ?? false),
            'is_visible' => (bool) ($data['is_visible'] ?? true),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return to_route('cms.index')->with('success', 'Page updated successfully.');
    }

    public function storeSection(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'section_type' => ['required', 'string'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'content_en' => ['nullable', 'array'],
            'content_ar' => ['nullable', 'array'],
            'settings_json' => ['nullable', 'array'],
            'status' => ['required', 'string'],
        ]);

        CmsSection::query()->create($data);

        return to_route('cms.index')->with('success', 'Section created successfully.');
    }

    public function updateSection(Request $request, CmsSection $cmsSection): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'section_type' => ['required', 'string'],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'content_en' => ['nullable', 'array'],
            'content_ar' => ['nullable', 'array'],
            'settings_json' => ['nullable', 'array'],
            'status' => ['required', 'string'],
        ]);

        $cmsSection->update($data);

        return to_route('cms.index')->with('success', 'Section updated successfully.');
    }

    public function attachSection(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'cms_section_id' => ['required', 'integer', 'exists:cms_sections,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ]);

        CmsPageSection::query()->updateOrCreate(
            [
                'cms_page_id' => $cmsPage->id,
                'cms_section_id' => $data['cms_section_id'],
            ],
            [
                'sort_order' => $data['sort_order'] ?? 0,
                'is_visible' => (bool) ($data['is_visible'] ?? true),
            ],
        );

        return to_route('cms.index')->with('success', 'Section attached to page.');
    }

    public function updatePageSection(Request $request, CmsPageSection $cmsPageSection): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
            'settings_json' => ['nullable', 'array'],
        ]);

        $cmsPageSection->update([
            'sort_order' => $data['sort_order'],
            'is_visible' => (bool) ($data['is_visible'] ?? true),
            'settings_json' => $data['settings_json'] ?? null,
        ]);

        return to_route('cms.index')->with('success', 'Page section updated.');
    }

    public function reorderPageSections(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer', 'exists:cms_page_sections,id'],
        ]);

        $validIds = $cmsPage->pageSections()
            ->whereIn('id', $data['ordered_ids'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless(count($validIds) === count($data['ordered_ids']), 422, 'Section order does not match this page.');

        foreach (array_values($data['ordered_ids']) as $index => $id) {
            CmsPageSection::query()
                ->whereKey($id)
                ->where('cms_page_id', $cmsPage->id)
                ->update(['sort_order' => $index + 1]);
        }

        return to_route('cms.index')->with('success', 'Page sections reordered.');
    }

    public function destroyPageSection(CmsPageSection $cmsPageSection): RedirectResponse
    {
        $actor = $this->actor(request());
        $this->requireRoles($actor, ['superadmin']);

        $cmsPageSection->delete();

        return to_route('cms.index')->with('success', 'Page section removed.');
    }
}
