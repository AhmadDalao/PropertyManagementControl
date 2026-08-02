<?php

namespace App\Modules\EmailDelivery\Actions;

use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Modules\EmailDelivery\Presenters\EmailDeliveryLogPresenter;
use App\Modules\EmailDelivery\Queries\EmailDeliveryIndexQuery;
use App\Modules\Exports\Support\XlsxWorkbook;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class EmailDeliveryWorkbookExport
{
    public function __construct(
        private readonly EmailDeliveryIndexQuery $deliveries,
        private readonly EmailDeliveryLogPresenter $presenter,
        private readonly XlsxWorkbook $workbook,
    ) {}

    /** @param array<string, mixed> $filters */
    public function download(User $actor, array $filters): BinaryFileResponse
    {
        $rows = [[
            trans('app.email_delivery.export.record'),
            trans('app.email_delivery.export.type'),
            trans('app.email_delivery.export.recipient'),
            trans('app.email_delivery.export.subject'),
            trans('app.email_delivery.export.status'),
            trans('app.email_delivery.export.mailer'),
            trans('app.email_delivery.export.attempts'),
            trans('app.email_delivery.export.portfolio'),
            trans('app.email_delivery.export.account'),
            trans('app.email_delivery.export.started_at'),
            trans('app.email_delivery.export.accepted_at'),
            trans('app.email_delivery.export.failed_at'),
            trans('app.email_delivery.export.error'),
            trans('app.email_delivery.export.notification_id'),
            trans('app.email_delivery.export.transport_message_id'),
        ]];

        $this->deliveries
            ->filtered($actor, $filters)
            ->reorder()
            ->chunkByIdDesc(500, function ($deliveries) use (&$rows): void {
                foreach ($deliveries as $delivery) {
                    /** @var EmailDeliveryLog $delivery */
                    $item = $this->presenter->row($delivery);
                    $rows[] = [
                        $delivery->id,
                        $item['type_label'],
                        $delivery->recipient_email,
                        $item['subject'],
                        $item['status_label'],
                        $delivery->mailer,
                        $delivery->attempts,
                        $item['portfolio'],
                        $item['user'],
                        $delivery->started_at?->toIso8601String(),
                        $delivery->accepted_at?->toIso8601String(),
                        $delivery->failed_at?->toIso8601String(),
                        $delivery->error_message,
                        $delivery->notification_id,
                        $delivery->transport_message_id,
                    ];
                }
            });

        $path = $this->workbook->create($rows, trans('app.email_delivery.export_sheet'));

        return response()->download(
            $path,
            'email-delivery-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }
}
