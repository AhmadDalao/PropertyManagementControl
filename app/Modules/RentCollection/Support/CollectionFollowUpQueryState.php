<?php

namespace App\Modules\RentCollection\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CollectionFollowUpQueryState
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function broken(Builder $query): Builder
    {
        return $this->unfulfilled(
            $query
                ->where('outcome', 'promise_to_pay')
                ->whereDate('promised_on', '<', today()),
        );
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function unfulfilled(Builder $query): Builder
    {
        return $query->where(function (Builder $promise): void {
            $promise
                ->whereNull('outstanding_amount_at_contact')
                ->orWhereNull('promised_amount')
                ->orWhereRaw(
                    '(collection_follow_ups.outstanding_amount_at_contact - '
                    .'(lease_installments.amount_due - lease_installments.amount_paid)) + 0.005 '
                    .'< collection_follow_ups.promised_amount',
                );
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function fulfilled(Builder $query): Builder
    {
        return $query
            ->whereNotNull('outstanding_amount_at_contact')
            ->whereNotNull('promised_amount')
            ->whereRaw(
                '(collection_follow_ups.outstanding_amount_at_contact - '
                .'(lease_installments.amount_due - lease_installments.amount_paid)) + 0.005 '
                .'>= collection_follow_ups.promised_amount',
            );
    }
}
