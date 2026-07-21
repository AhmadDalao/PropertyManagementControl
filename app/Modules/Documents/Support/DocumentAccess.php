<?php

namespace App\Modules\Documents\Support;

use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\User;
use App\Modules\Shared\PortfolioScope;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DocumentAccess
{
    public function __construct(
        private readonly PortfolioScope $portfolios,
        private readonly DocumentAttachments $attachments,
    ) {}

    public function ensureManager(User $actor): void
    {
        abort_unless(
            $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']),
            403,
            trans('app.errors.section_access_denied'),
        );
    }

    public function ensureCanManage(User $actor, Document $document): void
    {
        $this->ensureManager($actor);
        $this->portfolios->ensureAccess($actor, $document->portfolio_id);
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function directoryScope(Builder $query, User $actor): Builder
    {
        $this->ensureManager($actor);

        return $this->portfolios->apply($query, $actor);
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function tenantPortalScope(Builder $query, User $actor): Builder
    {
        if (! $actor->hasRole('tenant')) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('is_public', true)
            ->where(function (Builder $documents) use ($actor): void {
                $documents
                    ->where(function (Builder $leaseDocuments) use ($actor): void {
                        $leaseDocuments
                            ->whereIn('documentable_type', $this->attachments->typesFor('lease'))
                            ->whereIn('type', DocumentOptions::portalTypes('lease'))
                            ->whereIn('documentable_id', Lease::query()
                                ->select('id')
                                ->whereHas(
                                    'tenantProfile',
                                    fn (Builder $tenants) => $tenants->where('user_id', $actor->id),
                                ));
                    })
                    ->orWhere(function (Builder $paymentDocuments) use ($actor): void {
                        $paymentDocuments
                            ->whereIn('documentable_type', $this->attachments->typesFor('payment'))
                            ->whereIn('type', DocumentOptions::portalTypes('payment'))
                            ->whereIn('documentable_id', Payment::query()
                                ->select('id')
                                ->whereHas(
                                    'tenantProfile',
                                    fn (Builder $tenants) => $tenants->where('user_id', $actor->id),
                                ));
                    });
            });
    }

    public function ensureCanDownload(User $actor, Document $document): void
    {
        abort_unless(
            $this->canDownload($actor, $document),
            403,
            trans('app.errors.document_access_denied'),
        );
    }

    public function canDownload(User $actor, Document $document): bool
    {
        if ($actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            try {
                $this->ensureCanManage($actor, $document);

                return true;
            } catch (HttpException) {
                return false;
            }
        }

        if (! $actor->hasRole('tenant') || ! $document->is_public) {
            return false;
        }

        $attachment = $this->attachments->aliasForDocument($document);

        if ($attachment === null || ! DocumentOptions::canShowInPortal($attachment, $document->type)) {
            return false;
        }

        $document->loadMissing('documentable');
        $record = $document->documentable;

        return match (true) {
            $record instanceof Lease => $record->tenantProfile()->where('user_id', $actor->id)->exists(),
            $record instanceof Payment => $record->tenantProfile()->where('user_id', $actor->id)->exists(),
            default => false,
        };
    }
}
