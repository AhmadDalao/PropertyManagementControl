<?php

namespace App\Modules\Documents\Support;

use App\Models\Document;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final class DocumentExpiryState
{
    /** @var list<string> */
    public const FILTERS = [
        'attention',
        'expired',
        'due_30',
        'due_90',
        'current',
        'no_expiry',
    ];

    public function code(?CarbonInterface $expiresOn): string
    {
        if ($expiresOn === null) {
            return 'no_expiry';
        }

        if ($expiresOn->isBefore(today())) {
            return 'expired';
        }

        if ($expiresOn->lessThanOrEqualTo(today()->addDays(30))) {
            return 'due_30';
        }

        return $expiresOn->lessThanOrEqualTo(today()->addDays(90))
            ? 'due_90'
            : 'current';
    }

    public function label(?CarbonInterface $expiresOn): string
    {
        return trans('app.documents.expiry_'.$this->code($expiresOn));
    }

    public function daysRemaining(?CarbonInterface $expiresOn): ?int
    {
        return $expiresOn === null
            ? null
            : (int) ($expiresOn->diffInDays(today(), false) * -1);
    }

    /** @param Builder<Document> $query */
    public function apply(Builder $query, string $filter): void
    {
        match ($filter) {
            'attention' => $query
                ->whereNotNull('expires_on')
                ->whereDate('expires_on', '<=', today()->addDays(90)),
            'expired' => $query->whereDate('expires_on', '<', today()),
            'due_30' => $query
                ->whereDate('expires_on', '>=', today())
                ->whereDate('expires_on', '<=', today()->addDays(30)),
            'due_90' => $query
                ->whereDate('expires_on', '>', today()->addDays(30))
                ->whereDate('expires_on', '<=', today()->addDays(90)),
            'current' => $query->whereDate('expires_on', '>', today()->addDays(90)),
            'no_expiry' => $query->whereNull('expires_on'),
            default => null,
        };
    }
}
