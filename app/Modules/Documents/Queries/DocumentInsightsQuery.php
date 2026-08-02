<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Modules\Documents\Support\DocumentExpiryState;
use Illuminate\Database\Eloquent\Builder;

final class DocumentInsightsQuery
{
    public function __construct(private readonly DocumentExpiryState $expiry) {}

    /**
     * @param  Builder<Document>  $baseQuery
     * @return array{
     *     total:int,contracts:int,signed:int,receipts:int,portal_visible:int,
     *     expired:int,expiring_90:int,no_expiry:int
     * }
     */
    public function metrics(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'contracts' => (clone $baseQuery)->whereIn('type', ['lease_contract', 'signed_contract'])->count(),
            'signed' => (clone $baseQuery)->where('type', 'signed_contract')->count(),
            'receipts' => (clone $baseQuery)->where('type', 'receipt')->count(),
            'portal_visible' => (clone $baseQuery)->where('is_public', true)->count(),
            'expired' => $this->expiryCount($baseQuery, 'expired'),
            'expiring_90' => $this->expiryCount($baseQuery, 'due_30')
                + $this->expiryCount($baseQuery, 'due_90'),
            'no_expiry' => $this->expiryCount($baseQuery, 'no_expiry'),
        ];
    }

    /**
     * @param  Builder<Document>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return array<int, array{label:string,value:int,filter:array<string, string>,active:bool}>
     */
    public function counts(Builder $baseQuery, array $filters): array
    {
        $activeType = (string) $filters['type'];
        $activeVisibility = (string) $filters['visibility'];
        $activeExpiry = (string) $filters['expiry'];

        return [
            $this->count($baseQuery, trans('app.documents.all'), ['type' => 'all', 'visibility' => 'all', 'expiry' => 'all'], $activeType === 'all' && $activeVisibility === 'all' && $activeExpiry === 'all'),
            $this->expiryCountChip($baseQuery, 'attention', $activeExpiry),
            $this->expiryCountChip($baseQuery, 'expired', $activeExpiry),
            $this->expiryCountChip($baseQuery, 'due_30', $activeExpiry),
            $this->expiryCountChip($baseQuery, 'due_90', $activeExpiry),
            $this->count($baseQuery, trans('app.documents.portal_visible'), ['visibility' => 'public'], $activeVisibility === 'public', 'is_public', true),
        ];
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array<string, string>  $filter
     * @return array{label:string,value:int,filter:array<string, string>,active:bool}
     */
    private function count(
        Builder $query,
        string $label,
        array $filter,
        bool $active,
        ?string $column = null,
        mixed $value = null,
    ): array {
        if ($column !== null) {
            $query = (clone $query)->where($column, $value);
        }

        return [
            'label' => $label,
            'value' => (clone $query)->count(),
            'filter' => $filter,
            'active' => $active,
        ];
    }

    /**
     * @param  Builder<Document>  $query
     * @return array{label:string,value:int,filter:array<string, string>,active:bool}
     */
    private function expiryCountChip(
        Builder $query,
        string $filter,
        string $activeExpiry,
    ): array {
        return [
            'label' => trans('app.documents.expiry_'.$filter),
            'value' => $this->expiryCount($query, $filter),
            'filter' => ['expiry' => $filter],
            'active' => $activeExpiry === $filter,
        ];
    }

    /** @param Builder<Document> $query */
    private function expiryCount(Builder $query, string $filter): int
    {
        $query = clone $query;
        $this->expiry->apply($query, $filter);

        return $query->count();
    }
}
