<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Modules\Payments\Actions\ManagePayments;
use App\Modules\Payments\Actions\PaymentReceipts;
use App\Modules\Payments\Presenters\PaymentDetailPresenter;
use App\Modules\Payments\Presenters\PaymentFormPresenter;
use App\Modules\Payments\Queries\PaymentIndexQuery;
use App\Modules\Payments\Requests\StorePaymentRequest;
use App\Modules\Payments\Requests\UpdatePaymentRequest;
use App\Modules\Payments\Support\PaymentAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentIndexQuery $indexQuery,
        private readonly PaymentFormPresenter $formPresenter,
        private readonly PaymentDetailPresenter $detailPresenter,
        private readonly ManagePayments $payments,
        private readonly PaymentReceipts $receipts,
        private readonly PaymentAccess $access,
    ) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/payments/index', $this->indexQuery->handle($request, $actor));
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $actor,
                defaults: $request->only('lease_id'),
            ),
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        $actor = $this->actor($request);
        $this->access->ensureCanAccess($actor, $payment);

        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($payment, $actor),
        ]);
    }

    public function edit(Request $request, Payment $payment): Response
    {
        $actor = $this->actor($request);
        $this->access->ensureCanManage($actor, $payment);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($actor, $payment),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $this->payments->create($this->actor($request), $request->validated());

        return to_route('payments.show', $payment)
            ->with('success', trans('app.messages.payment_created'));
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->payments->update($this->actor($request), $payment, $request->validated());

        return to_route('payments.show', $payment)
            ->with('success', trans('app.messages.payment_updated'));
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $this->payments->void($this->actor($request), $payment);

        return to_route('payments.index')
            ->with('success', trans('app.messages.payment_voided'));
    }

    public function receipt(Request $request, Payment $payment): StreamedResponse
    {
        return $this->receipts->download($this->actor($request), $payment);
    }
}
