<?php

namespace App\Modules\Search\Queries;

use App\Models\User;
use App\Modules\Assets\Queries\AssetSearch;
use App\Modules\Cms\Queries\CmsPageSearch;
use App\Modules\Documents\Queries\DocumentSearch;
use App\Modules\Expenses\Queries\ExpenseSearch;
use App\Modules\Leases\Queries\LeaseSearch;
use App\Modules\Maintenance\Queries\MaintenanceSearch;
use App\Modules\Media\Queries\MediaFileSearch;
use App\Modules\Payments\Queries\PaymentSearch;
use App\Modules\Portfolios\Queries\PortfolioSearch;
use App\Modules\Search\Contracts\SearchSource;
use App\Modules\Search\Support\SearchAccess;
use App\Modules\Tenants\Queries\TenantSearch;
use App\Modules\Users\Queries\UserSearch;

class GlobalSearchQuery
{
    public function __construct(
        private readonly SearchAccess $access,
        private readonly PortfolioSearch $portfolios,
        private readonly AssetSearch $assets,
        private readonly TenantSearch $tenants,
        private readonly LeaseSearch $leases,
        private readonly PaymentSearch $payments,
        private readonly MaintenanceSearch $maintenance,
        private readonly ExpenseSearch $expenses,
        private readonly UserSearch $users,
        private readonly DocumentSearch $documents,
        private readonly MediaFileSearch $media,
        private readonly CmsPageSearch $cmsPages,
    ) {}

    /** @return array{ok:bool,query:string,results:array<int, array{group:string,title:string,subtitle:string,badge:string,url:string}>,message:string,direct_url:string} */
    public function handle(User $actor, string $query): array
    {
        $this->access->ensureAllowed($actor);

        if (mb_strlen($query) < 2) {
            return $this->payload($query, [], trans('app.search.minimum'), null);
        }

        $results = [];
        $directUrl = null;

        foreach ($this->sources() as $source) {
            if (count($results) < 30) {
                $results = [
                    ...$results,
                    ...$source->results($actor, $query),
                ];
            }

            $directUrl ??= $source->directUrl($actor, $query);
        }

        return $this->payload(
            $query,
            array_slice($results, 0, 30),
            '',
            $directUrl,
        );
    }

    /** @return array<int, SearchSource> */
    private function sources(): array
    {
        return [
            $this->portfolios,
            $this->assets,
            $this->tenants,
            $this->leases,
            $this->payments,
            $this->maintenance,
            $this->expenses,
            $this->users,
            $this->documents,
            $this->media,
            $this->cmsPages,
        ];
    }

    /**
     * @param  array<int, array{group:string,title:string,subtitle:string,badge:string,url:string}>  $results
     * @return array{ok:bool,query:string,results:array<int, array{group:string,title:string,subtitle:string,badge:string,url:string}>,message:string,direct_url:string}
     */
    private function payload(string $query, array $results, string $message, ?string $directUrl): array
    {
        return [
            'ok' => true,
            'query' => $query,
            'results' => $results,
            'message' => $message,
            'direct_url' => $directUrl ?? '',
        ];
    }
}
