<?php

namespace App\Modules\EmailDelivery\Presenters;

use App\Models\EmailDeliveryLog;
use App\Modules\EmailDelivery\Support\EmailDeliveryType;
use App\Modules\Shared\ResourcePresenter;

final class EmailDeliveryLogPresenter
{
    public function __construct(
        private readonly EmailDeliveryType $types,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function row(EmailDeliveryLog $log): array
    {
        return [
            'id' => $log->id,
            'notification_id' => $log->notification_id,
            'recipient_email' => $log->recipient_email,
            'subject' => $log->subject ?: trans('app.email_delivery.subject_unavailable'),
            'email_type' => $log->email_type,
            'type_label' => $this->types->label($log->email_type),
            'status' => $log->status,
            'status_label' => trans("app.email_delivery.statuses.{$log->status}"),
            'mailer' => $log->mailer,
            'attempts' => $log->attempts,
            'portfolio' => $log->portfolio
                ? $this->resources->localized(
                    $log->portfolio->name_en,
                    $log->portfolio->name_ar,
                )
                : null,
            'user' => $log->user?->name,
            'created_at' => $log->created_at?->toIso8601String(),
            'started_at' => $log->started_at?->toIso8601String(),
            'accepted_at' => $log->accepted_at?->toIso8601String(),
            'failed_at' => $log->failed_at?->toIso8601String(),
            'error_message' => $log->error_message,
            'url' => route('email-delivery.show', $log),
        ];
    }

    /** @return array<string, mixed> */
    public function detail(EmailDeliveryLog $log): array
    {
        return [
            'delivery' => [
                ...$this->row($log),
                'notification_class' => $log->notification_class,
                'transport_message_id' => $log->transport_message_id,
            ],
        ];
    }
}
