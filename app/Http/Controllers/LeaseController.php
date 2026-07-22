<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Modules\Leases\Actions\LeaseDocuments;
use App\Modules\Leases\Actions\ManageLeases;
use App\Modules\Leases\Presenters\LeaseDetailPresenter;
use App\Modules\Leases\Presenters\LeaseFormPresenter;
use App\Modules\Leases\Queries\LeaseIndexQuery;
use App\Modules\Leases\Requests\StoreLeaseRequest;
use App\Modules\Leases\Requests\UpdateLeaseRequest;
use App\Modules\Leases\Requests\UploadSignedContractRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseController extends Controller
{
    public function __construct(
        private readonly LeaseIndexQuery $indexQuery,
        private readonly LeaseFormPresenter $formPresenter,
        private readonly LeaseDetailPresenter $detailPresenter,
        private readonly ManageLeases $leases,
        private readonly LeaseDocuments $documents,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/leases/index', $this->indexQuery->handle($request, $actor));
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $actor,
                defaults: $request->only(['portfolio_id', 'tenant_profile_id', 'asset_id']),
            ),
        ]);
    }

    public function show(Request $request, Lease $lease): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($lease, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, Lease $lease): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $lease),
        ]);
    }

    public function store(StoreLeaseRequest $request): RedirectResponse
    {
        $lease = $this->leases->create($this->actor($request), $request->validated());

        return to_route('leases.show', $lease)
            ->with('success', trans('app.messages.lease_created', ['code' => $lease->code]));
    }

    public function update(UpdateLeaseRequest $request, Lease $lease): RedirectResponse
    {
        $this->leases->update($this->actor($request), $lease, $request->validated());

        return to_route('leases.show', $lease)
            ->with('success', trans('app.messages.lease_updated', ['code' => $lease->code]));
    }

    public function destroy(Request $request, Lease $lease): RedirectResponse
    {
        $this->leases->terminate($this->actor($request), $lease);

        return to_route('leases.index')
            ->with('success', trans('app.messages.lease_terminated', ['code' => $lease->code]));
    }

    public function uploadSignedContract(
        UploadSignedContractRequest $request,
        Lease $lease
    ): RedirectResponse {
        $file = $request->file('signed_contract');
        abort_unless($file instanceof UploadedFile, 422);
        $this->documents->uploadSigned($this->actor($request), $lease, $file);

        return to_route('leases.show', $lease)
            ->with('success', trans('app.messages.signed_contract_uploaded'));
    }

    public function contract(Request $request, Lease $lease): StreamedResponse
    {
        return $this->documents->contract($this->actor($request), $lease);
    }

    public function statement(Request $request, Lease $lease): StreamedResponse
    {
        return $this->documents->statement($this->actor($request), $lease);
    }
}
