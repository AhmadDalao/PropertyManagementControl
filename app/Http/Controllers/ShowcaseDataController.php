<?php

namespace App\Http\Controllers;

use App\Models\ShowcaseDataset;
use App\Services\ShowcaseDatasetService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowcaseDataController extends Controller
{
    public function index(Request $request, ShowcaseDatasetService $service): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $service->tagLegacyData();

        return Inertia::render('admin/showcase-data/index', [
            'datasets' => ShowcaseDataset::query()
                ->with('initiatedBy:id,name')
                ->latest('id')
                ->get()
                ->map(fn (ShowcaseDataset $dataset): array => [
                    'id' => $dataset->id,
                    'key' => $dataset->key,
                    'name' => $dataset->name,
                    'status' => $dataset->status,
                    'target_properties' => $dataset->target_properties,
                    'generated_properties' => $dataset->generated_properties,
                    'progress_percent' => $dataset->target_properties > 0
                        ? round(($dataset->generated_properties / $dataset->target_properties) * 100, 1)
                        : 0,
                    'counts' => is_array($dataset->getAttribute('counts_json'))
                        ? $dataset->getAttribute('counts_json')
                        : [],
                    'failure_details' => $dataset->failure_details,
                    'initiated_by' => $this->relatedName($dataset->getRelation('initiatedBy')),
                    'started_at' => $this->isoDate($dataset->getAttribute('started_at')),
                    'completed_at' => $this->isoDate($dataset->getAttribute('completed_at')),
                    'purged_at' => $this->isoDate($dataset->getAttribute('purged_at')),
                ]),
            'targets' => [
                'portfolios' => 5,
                'buildings' => 40,
                'floors' => 160,
                'units' => 640,
                'owners' => 5,
                'managers' => 20,
                'tenant_accounts' => 480,
                'tenants' => 480,
                'leases' => 480,
                'installments' => 5760,
                'payments' => 1600,
                'maintenance' => 320,
                'expenses' => 240,
                'documents' => 960,
            ],
        ]);
    }

    public function store(Request $request, ShowcaseDatasetService $service): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $service->start($actor);

        return to_route('showcase-data.index')->with('success', trans('app.showcase.generation_started'));
    }

    public function retry(
        Request $request,
        ShowcaseDataset $showcaseDataset,
        ShowcaseDatasetService $service,
    ): RedirectResponse {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $service->retry($showcaseDataset);

        return to_route('showcase-data.index')->with('success', trans('app.showcase.retry_started'));
    }

    public function destroy(
        Request $request,
        ShowcaseDataset $showcaseDataset,
        ShowcaseDatasetService $service,
    ): RedirectResponse {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin']);
        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:100'],
        ]);

        abort_unless(
            hash_equals(trans('app.showcase.confirmation'), trim($data['confirmation'])),
            422,
            trans('app.showcase.purge_confirmation_invalid'),
        );

        $service->purge($showcaseDataset);

        return to_route('showcase-data.index')->with('success', trans('app.showcase.purge_complete'));
    }

    private function relatedName(mixed $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        $name = $model->getAttribute('name');

        return is_string($name) ? $name : null;
    }

    private function isoDate(mixed $value): ?string
    {
        return $value instanceof CarbonInterface
            ? $value->toIso8601String()
            : null;
    }
}
