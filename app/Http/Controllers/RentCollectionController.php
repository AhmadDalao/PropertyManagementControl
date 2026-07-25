<?php

namespace App\Http\Controllers;

use App\Models\LeaseInstallment;
use App\Modules\RentCollection\Actions\RecordCollectionFollowUp;
use App\Modules\RentCollection\Presenters\CollectionFollowUpPagePresenter;
use App\Modules\RentCollection\Queries\RentCollectionIndexQuery;
use App\Modules\RentCollection\Requests\StoreCollectionFollowUpRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RentCollectionController extends Controller
{
    public function __construct(private readonly RentCollectionIndexQuery $collections) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/rent-collection/index',
            $this->collections->handle($request, $this->actor($request)),
        );
    }

    public function followUp(
        Request $request,
        LeaseInstallment $leaseInstallment,
        CollectionFollowUpPagePresenter $page,
    ): Response {
        return Inertia::render('admin/rent-collection/follow-up', [
            'collection' => $page->present(
                $this->actor($request),
                $leaseInstallment,
            ),
        ]);
    }

    public function storeFollowUp(
        StoreCollectionFollowUpRequest $request,
        LeaseInstallment $leaseInstallment,
        RecordCollectionFollowUp $followUps,
    ): RedirectResponse {
        $followUps->handle(
            $this->actor($request),
            $leaseInstallment,
            $request->validated(),
        );

        return to_route('rent-collection.follow-up', $leaseInstallment)
            ->with('success', trans('app.messages.collection_follow_up_recorded'));
    }
}
