<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Modules\Documents\Support\DocumentOptions;
use Illuminate\Database\Eloquent\Builder;

final class DocumentInsightsQuery
{
    /**
     * @param  Builder<Document>  $baseQuery
     * @return array{total:int,contracts:int,signed:int,receipts:int,portal_visible:int}
     */
    public function metrics(Builder $baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'contracts' => (clone $baseQuery)->whereIn('type', ['lease_contract', 'signed_contract'])->count(),
            'signed' => (clone $baseQuery)->where('type', 'signed_contract')->count(),
            'receipts' => (clone $baseQuery)->where('type', 'receipt')->count(),
            'portal_visible' => (clone $baseQuery)->where('is_public', true)->count(),
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

        return [
            $this->count($baseQuery, trans('app.documents.all'), ['type' => 'all', 'visibility' => 'all'], $activeType === 'all' && $activeVisibility === 'all'),
            $this->count($baseQuery, DocumentOptions::label('lease_contract'), ['type' => 'lease_contract'], $activeType === 'lease_contract', 'type', 'lease_contract'),
            $this->count($baseQuery, DocumentOptions::label('signed_contract'), ['type' => 'signed_contract'], $activeType === 'signed_contract', 'type', 'signed_contract'),
            $this->count($baseQuery, DocumentOptions::label('receipt'), ['type' => 'receipt'], $activeType === 'receipt', 'type', 'receipt'),
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
}
