<?php

namespace App\Modules\SystemReadiness\Support;

final class MailReadiness
{
    public function configured(): bool
    {
        $mailer = $this->mailer();

        if (in_array($mailer, ['log', 'array', 'null'], true)) {
            return false;
        }

        $from = trim((string) config('mail.from.address'));

        if ($from === '' || str_ends_with($from, '@example.com')) {
            return false;
        }

        if ($mailer !== 'smtp') {
            return true;
        }

        return trim((string) config('mail.mailers.smtp.host')) !== ''
            && trim((string) config('mail.mailers.smtp.username')) !== '';
    }

    public function mailer(): string
    {
        return (string) config('mail.default', 'log');
    }
}
