<?php

namespace App\Http\Controllers;

use App\Models\ShowcaseDataset;
use App\Modules\ShowcaseData\Actions\PurgeShowcaseDataset;
use App\Modules\ShowcaseData\Actions\RetryShowcaseDataset;
use App\Modules\ShowcaseData\Actions\StartShowcaseDataset;
use App\Modules\ShowcaseData\Presenters\ShowcaseDataPagePresenter;
use App\Modules\ShowcaseData\Requests\PurgeShowcaseDatasetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowcaseDataController extends Controller
{
    public function __construct(
        private readonly ShowcaseDataPagePresenter $page,
        private readonly StartShowcaseDataset $start,
        private readonly RetryShowcaseDataset $retry,
        private readonly PurgeShowcaseDataset $purge,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);

        return Inertia::render('admin/showcase-data/index', $this->page->present());
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $this->start->handle($actor);

        return to_route('showcase-data.index')->with('success', trans('app.showcase.generation_started'));
    }

    public function retry(
        Request $request,
        ShowcaseDataset $showcaseDataset,
    ): RedirectResponse {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $this->retry->handle($showcaseDataset);

        return to_route('showcase-data.index')->with('success', trans('app.showcase.retry_started'));
    }

    public function destroy(
        PurgeShowcaseDatasetRequest $request,
        ShowcaseDataset $showcaseDataset,
    ): RedirectResponse {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $this->purge->handle($showcaseDataset);

        return to_route('showcase-data.index')->with('success', trans('app.showcase.purge_complete'));
    }
}
