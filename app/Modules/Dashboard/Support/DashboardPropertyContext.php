<?php

namespace App\Modules\Dashboard\Support;

use App\Models\Lease;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final readonly class DashboardPropertyContext
{
    /**
     * @param  array{id:int,code:string,title_en:string,title_ar:?string}|null  $selected
     * @param  list<array{id:int,code:string,title_en:string,title_ar:?string}>  $options
     * @param  list<int>  $assetIds
     * @param  list<string>  $leaseableTypes
     */
    public function __construct(
        public ?array $selected,
        public array $options,
        private array $assetIds,
        private array $leaseableTypes,
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function assets(Builder $query): Builder
    {
        return $this->selected === null
            ? $query
            : $query->whereIn('assets.id', $this->assetIds);
    }

    /**
     * @param  Builder<Lease>  $query
     * @return Builder<Lease>
     */
    public function leases(Builder $query): Builder
    {
        return $this->selected === null
            ? $query
            : $query
                ->whereIn('leaseable_type', $this->leaseableTypes)
                ->whereIn('leaseable_id', $this->assetIds);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function assetRecords(Builder $query, string $column = 'asset_id'): Builder
    {
        return $this->selected === null
            ? $query
            : $query->whereIn($column, $this->assetIds);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function leaseRecords(Builder $query, string $column = 'lease_id'): Builder
    {
        if ($this->selected === null) {
            return $query;
        }

        return $query->whereIn(
            $column,
            $this->leases(Lease::query())->select('id'),
        );
    }

    /** @return array<string, mixed> */
    public function payload(): array
    {
        return [
            'selected' => $this->selected,
            'options' => $this->options,
        ];
    }
}
