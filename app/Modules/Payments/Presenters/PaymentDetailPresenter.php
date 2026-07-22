<?php

namespace App\Modules\Payments\Presenters;

use App\Models\Payment;
use App\Models\User;
use App\Modules\Payments\Queries\PaymentDetailQuery;
use App\Modules\Payments\Support\PaymentOptions;
use App\Modules\Shared\ResourcePresenter;

final class PaymentDetailPresenter
{
    public function __construct(
        private readonly PaymentDetailQuery $query,
        private readonly PaymentDetailHeaderPresenter $header,
        private readonly PaymentWorkflowPresenter $workflow,
        private readonly PaymentDetailOverviewPresenter $overview,
        private readonly PaymentRelatedPresenter $related,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Payment $payment, User $actor): array
    {
        $data = $this->query->get($payment, $actor);
        $documents = $data->adminMode
            ? $payment->documents
            : $payment->documents
                ->where('is_public', true)
                ->whereIn('type', PaymentOptions::TENANT_DOCUMENT_TYPES);

        return [
            'header' => $this->header->present($data),
            'workflow' => $this->workflow->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'related' => $this->related->present($data),
            'documents' => $this->resources->documentStrip($documents),
            'timeline' => $data->adminMode ? $this->resources->activityTimeline($payment) : [],
        ];
    }
}
