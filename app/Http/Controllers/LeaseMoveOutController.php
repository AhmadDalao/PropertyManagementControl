<?php

namespace App\Http\Controllers;

use App\Models\Lease;
use App\Modules\LeaseMoveOuts\Actions\CancelLeaseMoveOut;
use App\Modules\LeaseMoveOuts\Actions\CompleteLeaseMoveOut;
use App\Modules\LeaseMoveOuts\Actions\PlanLeaseMoveOut;
use App\Modules\LeaseMoveOuts\Presenters\LeaseMoveOutFormPresenter;
use App\Modules\LeaseMoveOuts\Queries\LeaseMoveOutIndexQuery;
use App\Modules\LeaseMoveOuts\Requests\UpsertLeaseMoveOutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LeaseMoveOutController extends Controller
{
    public function index(Request $request, LeaseMoveOutIndexQuery $moveOuts): Response
    {
        return Inertia::render(
            'admin/lease-move-outs/index',
            $moveOuts->handle($request, $this->actor($request)),
        );
    }

    public function edit(Request $request, Lease $lease, LeaseMoveOutFormPresenter $forms): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $forms->present($this->actor($request), $lease),
        ]);
    }

    public function update(
        UpsertLeaseMoveOutRequest $request,
        Lease $lease,
        PlanLeaseMoveOut $moveOuts,
    ): RedirectResponse {
        $moveOuts->handle($this->actor($request), $lease, $request->validated());

        return to_route('leases.show', $lease)
            ->with('success', trans('app.messages.move_out_plan_saved', ['code' => $lease->code]));
    }

    public function complete(
        Request $request,
        Lease $lease,
        CompleteLeaseMoveOut $moveOuts,
    ): RedirectResponse {
        $moveOuts->handle($this->actor($request), $lease);

        return to_route('leases.show', $lease)
            ->with('success', trans('app.messages.move_out_completed', ['code' => $lease->code]));
    }

    public function destroy(
        Request $request,
        Lease $lease,
        CancelLeaseMoveOut $moveOuts,
    ): RedirectResponse {
        $moveOuts->handle($this->actor($request), $lease);

        return to_route('leases.show', $lease)
            ->with('success', trans('app.messages.move_out_cancelled', ['code' => $lease->code]));
    }
}
