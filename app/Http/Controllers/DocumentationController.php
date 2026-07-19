<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PortfolioModules;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $role = $this->primaryRole($actor);
        $workflowTracks = $this->documentationItems($actor, 'workflows')->all();
        $quickStarts = $this->documentationItems($actor, 'quick_starts')->all();
        $guides = $this->documentationItems($actor, 'guides')->all();
        $pageShortcuts = $this->documentationItems($actor, 'page_shortcuts')->all();
        $controlChecks = $this->documentationItems($actor, 'control_checks')->all();

        return Inertia::render('admin/documentation/index', [
            'audience' => $role,
            'guides' => $guides,
            'quickStarts' => $quickStarts,
            'roleGuides' => $this->documentationItems($actor, 'role_guides', false)->all(),
            'workflowTracks' => $workflowTracks,
            'pageShortcuts' => $pageShortcuts,
            'controlChecks' => $controlChecks,
            'moduleStatus' => collect(PortfolioModules::definitions())
                ->map(fn (array $module) => [
                    ...$module,
                    'enabled' => PortfolioModules::enabledForUser($actor, $module['key']),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function show(Request $request, string $guide): Response
    {
        $actor = $this->actor($request);
        $guides = $this->documentationItems($actor, 'guides');
        $selected = $guides->firstWhere('slug', $guide);

        abort_if($selected === null, 404);

        return Inertia::render('admin/documentation/show', [
            'audience' => $this->primaryRole($actor),
            'guide' => $selected,
            'relatedGuides' => $guides
                ->where('slug', '!=', $guide)
                ->take(4)
                ->values()
                ->all(),
        ]);
    }

    private function primaryRole(User $actor): string
    {
        return $actor->getRoleNames()->first() ?? 'user';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function canSeeDocumentationItem(User $actor, array $item): bool
    {
        $roles = $item['roles'] ?? null;

        if (is_array($roles) && ! $actor->hasAnyRole($roles)) {
            return false;
        }

        $module = $item['module'] ?? null;

        if (is_string($module) && ! PortfolioModules::enabledForUser($actor, $module)) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function documentationItems(User $actor, string $collection, bool $scope = true): Collection
    {
        return collect(config("property_docs.{$collection}", []))
            ->when(
                $scope,
                fn (Collection $items) => $items->filter(
                    fn (array $item) => $this->canSeeDocumentationItem($actor, $item),
                ),
            )
            ->map(function (array $item) use ($collection): array {
                $translationKey = $this->documentationTranslationKey($collection, $item);
                $localized = trans("property_docs.{$collection}.{$translationKey}");
                $slug = $collection === 'guides'
                    ? Str::slug((string) $item['title'])
                    : null;

                if (is_array($localized)) {
                    $item = array_replace_recursive($item, $localized);
                }

                return $slug === null ? $item : [...$item, 'slug' => $slug];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function documentationTranslationKey(string $collection, array $item): string
    {
        return match ($collection) {
            'workflows' => (string) $item['key'],
            'role_guides' => (string) $item['role'],
            'page_shortcuts' => Str::slug((string) $item['label']),
            default => Str::slug((string) $item['title']),
        };
    }
}
