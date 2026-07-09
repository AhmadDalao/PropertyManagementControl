<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PortfolioModules;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $role = $this->primaryRole($actor);
        $workflowTracks = collect(config('property_docs.workflows', []))
            ->filter(fn (array $workflow) => $this->canSeeDocumentationItem($actor, $workflow))
            ->values()
            ->all();
        $quickStarts = collect(config('property_docs.quick_starts', []))
            ->filter(fn (array $quickStart) => $this->canSeeDocumentationItem($actor, $quickStart))
            ->values()
            ->all();
        $guides = collect(config('property_docs.guides', []))
            ->filter(fn (array $guide) => $this->canSeeDocumentationItem($actor, $guide))
            ->values()
            ->all();
        $pageShortcuts = collect(config('property_docs.page_shortcuts', []))
            ->filter(fn (array $shortcut) => $this->canSeeDocumentationItem($actor, $shortcut))
            ->values()
            ->all();
        $controlChecks = collect(config('property_docs.control_checks', []))
            ->filter(fn (array $check) => $this->canSeeDocumentationItem($actor, $check))
            ->values()
            ->all();

        return Inertia::render('admin/documentation/index', [
            'audience' => $role,
            'guides' => $guides,
            'quickStarts' => $quickStarts,
            'roleGuides' => config('property_docs.role_guides', []),
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
}
