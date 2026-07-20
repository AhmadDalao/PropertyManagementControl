<?php

namespace App\Http\Controllers;

use App\Modules\Wording\TranslationCompletenessService;
use App\Modules\Wording\UiTranslationCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class WordingController extends Controller
{
    public function index(
        Request $request,
        UiTranslationCatalog $catalog,
        TranslationCompletenessService $completeness,
    ): Response {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $allEntries = collect($catalog->entries());
        $search = trim((string) $request->query('search', ''));
        $group = trim((string) $request->query('group', 'all'));
        $state = trim((string) $request->query('state', 'all'));
        $perPage = in_array((int) $request->query('per_page', 25), [10, 25, 50, 100], true)
            ? (int) $request->query('per_page', 25)
            : 25;
        $page = max(1, (int) $request->query('page', 1));
        $filteredEntries = $allEntries
            ->when($group !== 'all', fn ($items) => $items->where('group', $group))
            ->when($state === 'customized', fn ($items) => $items->where('customized', true))
            ->when($state === 'default', fn ($items) => $items->where('customized', false))
            ->when($search !== '', function ($items) use ($search) {
                $needle = mb_strtolower($search);

                return $items->filter(fn (array $entry): bool => collect([
                    $entry['group'],
                    $entry['key'],
                    $entry['english'],
                    $entry['arabic'],
                ])->contains(fn (string $value): bool => str_contains(mb_strtolower($value), $needle)));
            })
            ->values();
        $entries = new LengthAwarePaginator(
            $filteredEntries->forPage($page, $perPage)->values(),
            $filteredEntries->count(),
            $perPage,
            $page,
            ['path' => route('wording.index'), 'query' => $request->query()],
        );
        $contentModule = trim((string) $request->query('content_module', 'all'));
        $missingContent = $completeness->missing($contentModule);

        return Inertia::render('admin/wording/index', [
            'entries' => $entries,
            'groups' => $allEntries
                ->pluck('group')
                ->unique()
                ->values(),
            'customizedCount' => $allEntries
                ->where('customized', true)
                ->count(),
            'totalLabels' => $allEntries->count(),
            'filters' => compact('search', 'group', 'state', 'perPage', 'contentModule'),
            'contentTranslations' => [
                'items' => $missingContent->take(500)->values(),
                'total' => $missingContent->count(),
                'counts' => $completeness->counts(),
                'modules' => [
                    'portfolios',
                    'assets',
                    'documents',
                    'media',
                    'cms_pages',
                    'cms_sections',
                    'navigation',
                    'report_presets',
                ],
            ],
        ]);
    }

    public function update(
        Request $request,
        UiTranslationCatalog $catalog,
    ): RedirectResponse {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $request->merge([
            'english' => trim((string) $request->input('english')),
            'arabic' => trim((string) $request->input('arabic')),
        ]);

        $data = $request->validate([
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:500'],
            'english' => ['required', 'string', 'max:2000'],
            'arabic' => ['required', 'string', 'max:2000'],
        ]);

        $catalog->save(
            $data['group'],
            $data['key'],
            $data['english'],
            $data['arabic'],
        );

        return back()->with('success', trans('app.wording.saved'));
    }

    public function destroy(
        Request $request,
        UiTranslationCatalog $catalog,
    ): RedirectResponse {
        $this->requireRoles($this->actor($request), ['superadmin']);

        $data = $request->validate([
            'group' => ['required', 'string', 'max:100'],
            'key' => ['required', 'string', 'max:500'],
        ]);

        $catalog->reset($data['group'], $data['key']);

        return back()->with('success', trans('app.wording.reset_complete'));
    }
}
