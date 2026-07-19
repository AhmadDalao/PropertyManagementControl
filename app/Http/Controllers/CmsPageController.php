<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\NavigationItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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
            'sections' => CmsSection::query()
                ->withCount('pageSections')
                ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                ->orderBy('name_en')
                ->get(),
            'navigationItems' => NavigationItem::query()
                ->with(['children', 'page'])
                ->whereNull('parent_id')
                ->orderBy('location')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->cmsPageFormPage(),
        ]);
    }

    public function builder(Request $request, CmsPage $cmsPage): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $cmsPage->loadMissing(['pageSections.section', 'navigationItems']);

        return Inertia::render('admin/cms/builder', [
            'page' => $cmsPage,
            'sections' => CmsSection::query()
                ->withCount('pageSections')
                ->orderBy('name_en')
                ->get(),
            'timeline' => $this->activityTimeline($cmsPage),
        ]);
    }

    public function edit(Request $request, CmsPage $cmsPage): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->cmsPageFormPage($cmsPage),
        ]);
    }

    public function createSection(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        return Inertia::render('admin/cms/section-form', [
            'section' => null,
            'sectionTypes' => $this->sectionTypes(),
        ]);
    }

    public function editSection(Request $request, CmsSection $cmsSection): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        return Inertia::render('admin/cms/section-form', [
            'section' => $cmsSection,
            'sectionTypes' => $this->sectionTypes(),
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

        if ($data['is_homepage'] ?? false) {
            CmsPage::query()->where('is_homepage', true)->update(['is_homepage' => false]);
        }

        $cmsPage = CmsPage::query()->create([
            ...$data,
            'slug' => $data['slug'] ?: Str::slug($data['title_en']),
            'is_homepage' => (bool) ($data['is_homepage'] ?? false),
            'is_visible' => (bool) ($data['is_visible'] ?? true),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return to_route('cms.pages.show', $cmsPage)->with('success', 'Page created successfully.');
    }

    public function update(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $data = $request->validate([
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($cmsPage->id)],
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

        if ($data['is_homepage'] ?? false) {
            CmsPage::query()
                ->whereKeyNot($cmsPage->id)
                ->where('is_homepage', true)
                ->update(['is_homepage' => false]);
        }

        $cmsPage->update([
            ...$data,
            'slug' => $data['slug'] ?: Str::slug($data['title_en']),
            'is_homepage' => (bool) ($data['is_homepage'] ?? false),
            'is_visible' => (bool) ($data['is_visible'] ?? true),
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);

        return to_route('cms.pages.show', $cmsPage)->with('success', 'Page updated successfully.');
    }

    public function destroy(Request $request, CmsPage $cmsPage): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $cmsPage->update([
            'status' => 'archived',
            'is_visible' => false,
            'is_homepage' => false,
        ]);

        return to_route('cms.index')->with('success', 'Page archived successfully.');
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

    public function destroySection(Request $request, CmsSection $cmsSection): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        $cmsSection->update(['status' => 'archived']);

        return to_route('cms.index')->with('success', 'Section archived successfully.');
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

        return to_route('cms.pages.show', $cmsPage)->with('success', 'Section attached to page.');
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

        return to_route('cms.pages.show', $cmsPageSection->page)->with('success', 'Page section updated.');
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

        return to_route('cms.pages.show', $cmsPage)->with('success', 'Page sections reordered.');
    }

    public function destroyPageSection(CmsPageSection $cmsPageSection): RedirectResponse
    {
        $actor = $this->actor(request());
        $this->requireRoles($actor, ['superadmin']);

        $cmsPageSection->delete();

        return to_route('cms.pages.show', $cmsPageSection->page)->with('success', 'Page section removed.');
    }

    private function cmsPageFormPage(?CmsPage $cmsPage = null): array
    {
        return [
            'title' => $cmsPage ? 'Edit '.$cmsPage->title_en : 'Create CMS page',
            'description' => 'Create the bilingual page shell, then compose sections in the visual builder.',
            'backHref' => $cmsPage ? route('cms.pages.show', $cmsPage) : route('cms.index'),
            'backLabel' => $cmsPage ? 'Page builder' : 'Website control',
            'action' => $cmsPage ? route('cms.pages.update', $cmsPage) : route('cms.pages.store'),
            'method' => $cmsPage ? 'put' : 'post',
            'submitLabel' => $cmsPage ? 'Update page' : 'Create page',
            'fields' => [
                ['name' => 'slug', 'label' => 'Slug', 'help' => 'Leave blank to generate from English title.'],
                ['name' => 'title_en', 'label' => 'English title', 'required' => true],
                ['name' => 'title_ar', 'label' => 'Arabic title', 'required' => true],
                ['name' => 'excerpt_en', 'label' => 'English excerpt', 'type' => 'textarea'],
                ['name' => 'excerpt_ar', 'label' => 'Arabic excerpt', 'type' => 'textarea'],
                ['name' => 'seo_title_en', 'label' => 'SEO title EN'],
                ['name' => 'seo_title_ar', 'label' => 'SEO title AR'],
                ['name' => 'seo_description_en', 'label' => 'SEO description EN', 'type' => 'textarea'],
                ['name' => 'seo_description_ar', 'label' => 'SEO description AR', 'type' => 'textarea'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['draft', 'published', 'archived'])],
                ['name' => 'is_homepage', 'label' => 'Set as homepage', 'type' => 'checkbox'],
                ['name' => 'is_visible', 'label' => 'Visible in public site', 'type' => 'checkbox'],
            ],
            'initialValues' => [
                'slug' => $cmsPage?->slug ?? '',
                'title_en' => $cmsPage?->title_en ?? '',
                'title_ar' => $cmsPage?->title_ar ?? '',
                'excerpt_en' => $cmsPage?->excerpt_en ?? '',
                'excerpt_ar' => $cmsPage?->excerpt_ar ?? '',
                'seo_title_en' => $cmsPage?->seo_title_en ?? '',
                'seo_title_ar' => $cmsPage?->seo_title_ar ?? '',
                'seo_description_en' => $cmsPage?->seo_description_en ?? '',
                'seo_description_ar' => $cmsPage?->seo_description_ar ?? '',
                'status' => $cmsPage?->status ?? 'draft',
                'is_homepage' => (bool) ($cmsPage?->is_homepage ?? false),
                'is_visible' => (bool) ($cmsPage?->is_visible ?? true),
            ],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function sectionTypes(): array
    {
        return collect([
            'hero',
            'role_cards',
            'workflow',
            'dashboard_preview',
            'feature_grid',
            'operations_strip',
            'faq',
            'final_cta',
            'metrics',
            'content',
        ])->map(fn (string $type) => [
            'label' => Str::of($type)->replace('_', ' ')->title()->toString(),
            'value' => $type,
        ])->all();
    }
}
