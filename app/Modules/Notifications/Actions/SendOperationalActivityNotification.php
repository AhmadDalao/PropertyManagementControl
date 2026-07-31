<?php

namespace App\Modules\Notifications\Actions;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Notifications\Data\OperationalNotificationData;
use App\Modules\Notifications\Notifications\OperationalActivityNotification;
use App\Modules\Notifications\Presenters\OperationalNotificationFactory;
use App\Modules\Notifications\Queries\OperationalNotificationRecipientsQuery;
use Illuminate\Support\Facades\Notification;
use Throwable;

final readonly class SendOperationalActivityNotification
{
    public function __construct(
        private OperationalNotificationRecipientsQuery $recipients,
        private OperationalNotificationFactory $factory,
    ) {}

    public function payment(User $actor, Payment $payment, string $event): void
    {
        $this->send(
            $actor,
            $payment,
            $this->factory->payment($payment, $actor, $event),
        );
    }

    public function lease(User $actor, Lease $lease, string $event): void
    {
        $this->send(
            $actor,
            $lease,
            $this->factory->lease($lease, $actor, $event),
        );
    }

    public function document(User $actor, Document $document): void
    {
        if ($document->is_public) {
            $this->send(
                $actor,
                $document,
                $this->factory->document($document, $actor),
            );
        }
    }

    private function send(
        User $actor,
        Lease|Payment|Document $record,
        OperationalNotificationData $data,
    ): void {
        try {
            $recipients = $this->recipients->affected($record, $actor);

            if ($recipients->isNotEmpty()) {
                Notification::send(
                    $recipients,
                    new OperationalActivityNotification($data),
                );
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
