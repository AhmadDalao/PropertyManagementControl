<?php

namespace App\Modules\Documents\Support;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

final class DocumentAttachmentResolver
{
    public function __construct(private readonly PortfolioScope $portfolios) {}

    public function resolve(
        User $actor,
        string $alias,
        int $id,
        bool $lock = false,
    ): Asset|Lease|Payment {
        $class = $this->classFor($alias);
        $query = $class::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        $record = $query->find($id);

        if (! $record) {
            throw (new ModelNotFoundException)->setModel($this->classFor($alias), [$id]);
        }

        $portfolioId = (int) $record->getAttribute('portfolio_id');
        $this->portfolios->ensureAccess($actor, $portfolioId);
        $portfolioQuery = Portfolio::query();

        if ($lock) {
            $portfolioQuery->lockForUpdate();
        }

        $portfolio = $portfolioQuery->findOrFail($portfolioId);

        if ($portfolio->status !== 'active') {
            throw ValidationException::withMessages([
                'documentable_id' => trans('app.errors.document_portfolio_inactive'),
            ]);
        }

        return $record;
    }

    /** @return class-string<Asset|Lease|Payment> */
    private function classFor(string $alias): string
    {
        return match ($alias) {
            'lease' => Lease::class,
            'asset' => Asset::class,
            'payment' => Payment::class,
            default => throw ValidationException::withMessages([
                'documentable_type' => trans('app.errors.unsupported_document_attachment'),
            ]),
        };
    }
}
