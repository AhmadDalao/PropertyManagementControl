<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\Document;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\MediaFile;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use App\Support\PortfolioModules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $actor = $this->actor($request);
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'ok' => true,
                'query' => $query,
                'results' => [],
                'message' => trans('app.search.minimum'),
                'direct_url' => '',
            ]);
        }

        if ($actor->hasRole('tenant')) {
            return response()->json([
                'ok' => true,
                'query' => $query,
                'results' => $this->tenantResults($actor, $query),
                'message' => '',
                'direct_url' => $this->tenantDirectUrl($actor, $query),
            ]);
        }

        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return response()->json([
            'ok' => true,
            'query' => $query,
            'results' => $this->adminResults($request, $query),
            'message' => '',
            'direct_url' => $this->adminDirectUrl($request, $query),
        ]);
    }

    /**
     * @return array<int, array{group:string,title:string,subtitle:string,badge:string,url:string}>
     */
    private function adminResults(Request $request, string $search): array
    {
        $actor = $this->actor($request);
        $results = [];

        if ($actor->hasRole('superadmin')) {
            $portfolioQuery = Portfolio::query();
            $this->applySearch($portfolioQuery, $search, ['name_en', 'name_ar', 'code', 'contact_email', 'address', 'address_ar']);
            foreach ($portfolioQuery->limit(5)->get() as $portfolio) {
                $results[] = $this->result(trans('app.nav.portfolios'), $this->localized($portfolio->name_en, $portfolio->name_ar), $portfolio->code, $this->status($portfolio->status), route('portfolios.show', $portfolio));
            }
        }

        if ($this->moduleEnabled($actor, 'assets')) {
            $assetQuery = $this->scopeByPortfolio(Asset::query(), $actor);
            $this->applySearch($assetQuery, $search, [
                'title_en',
                'title_ar',
                'code',
                'address',
                'address_ar',
                fn (Builder $query, string $term, string $like) => $this->orWhereAssetMapMetadata($query, $like),
            ]);
            foreach ($assetQuery->limit(6)->get() as $asset) {
                $results[] = $this->result(trans('app.nav.assets'), $this->localized($asset->title_en, $asset->title_ar), $this->assetResultSubtitle($asset), $this->status($asset->occupancy_status), route('assets.show', $asset));
            }
        }

        if ($this->moduleEnabled($actor, 'tenants')) {
            $tenantQuery = $this->scopeByPortfolio(TenantProfile::query()->with('user'), $actor);
            $this->applySearch($tenantQuery, $search, [
                'national_id',
                'company_name',
                fn ($query, $term, $like) => $query->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)),
            ]);
            foreach ($tenantQuery->limit(5)->get() as $tenant) {
                $results[] = $this->result(trans('app.nav.tenants'), $tenant->user?->name ?? trans('app.nav.tenants').' #'.$tenant->id, $tenant->user?->email ?? $tenant->company_name ?? '', $this->status($tenant->status), route('tenants.show', $tenant));
            }
        }

        if ($this->moduleEnabled($actor, 'leases')) {
            $leaseQuery = $this->scopeByPortfolio(Lease::query()->with(['tenantProfile.user', 'leaseable']), $actor);
            $this->applySearch($leaseQuery, $search, [
                'code',
                fn ($query, $term, $like) => $query->orWhereHas('tenantProfile.user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)),
                fn ($query, $term, $like) => $query->orWhereHasMorph('leaseable', [Asset::class], fn ($assetQuery) => $assetQuery
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like)),
            ]);
            foreach ($leaseQuery->limit(5)->get() as $lease) {
                $results[] = $this->result(trans('app.nav.leases'), $lease->code, ($lease->tenantProfile?->user?->name ?? '-').' · '.$this->localized($lease->leaseable?->title_en, $lease->leaseable?->title_ar), $this->status($lease->status), route('leases.show', $lease));
            }
        }

        if ($this->moduleEnabled($actor, 'payments')) {
            $paymentQuery = $this->scopeByPortfolio(Payment::query()->with(['tenantProfile.user', 'lease']), $actor);
            $this->applySearch($paymentQuery, $search, [
                'reference',
                'notes',
                fn ($query, $term, $like) => $query->orWhereHas('tenantProfile.user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)),
                fn ($query, $term, $like) => $query->orWhereHas('lease', fn ($leaseQuery) => $leaseQuery->where('code', 'like', $like)),
            ]);
            foreach ($paymentQuery->limit(5)->get() as $payment) {
                $results[] = $this->result(trans('app.nav.payments'), $payment->reference ?: '#'.$payment->id, $payment->tenantProfile?->user?->name ?? $payment->lease?->code ?? '', $this->status($payment->status), route('payments.show', $payment));
            }
        }

        if ($this->moduleEnabled($actor, 'maintenance')) {
            $maintenanceQuery = $this->scopeByPortfolio(MaintenanceRequest::query()->with(['asset', 'tenantProfile.user']), $actor);
            $this->applySearch($maintenanceQuery, $search, [
                'title',
                'description',
                'category',
                fn ($query, $term, $like) => $query->orWhereHas('asset', fn ($assetQuery) => $assetQuery
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like)),
                fn ($query, $term, $like) => $query->orWhereHas('tenantProfile.user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)),
            ]);
            foreach ($maintenanceQuery->limit(5)->get() as $maintenance) {
                $results[] = $this->result(trans('app.nav.maintenance'), '#'.$maintenance->id.' '.$maintenance->title, $this->localized($maintenance->asset?->title_en, $maintenance->asset?->title_ar) ?: ($maintenance->tenantProfile?->user?->name ?? ''), $this->status($maintenance->status), route('maintenance-requests.show', $maintenance));
            }
        }

        if ($this->moduleEnabled($actor, 'users')) {
            $userQuery = $this->scopeByPortfolio(User::query()->with('roles'), $actor);
            $this->applySearch($userQuery, $search, ['name', 'email', 'phone']);
            foreach ($userQuery->limit(5)->get() as $user) {
                $results[] = $this->result(trans('app.nav.users'), $user->name, $user->email, $user->roles->pluck('name')->map(fn (string $role): string => trans("app.roles.{$role}"))->join(', '), route('users.show', $user));
            }
        }

        if ($this->moduleEnabled($actor, 'documents')) {
            $documentQuery = $this->scopeByPortfolio(Document::query(), $actor);
            $this->applySearch($documentQuery, $search, ['title_en', 'title_ar', 'original_name', 'type', 'file_path']);
            foreach ($documentQuery->limit(5)->get() as $document) {
                $results[] = $this->result(trans('app.nav.documents'), $this->localized($document->title_en, $document->title_ar), $document->original_name, $document->type, route('documents.show', $document));
            }
        }

        if ($this->moduleEnabled($actor, 'media')) {
            $mediaQuery = $this->scopeByPortfolio(MediaFile::query(), $actor);
            $this->applySearch($mediaQuery, $search, ['title_en', 'title_ar', 'collection', 'path']);
            foreach ($mediaQuery->limit(4)->get() as $media) {
                $results[] = $this->result(trans('app.nav.media'), $this->localized($media->title_en, $media->title_ar) ?: trans('app.nav.media').' #'.$media->id, $media->collection, $this->status($media->visibility), route('media-files.show', $media));
            }
        }

        if ($actor->hasRole('superadmin')) {
            $pageQuery = CmsPage::query();
            $this->applySearch($pageQuery, $search, ['title_en', 'title_ar', 'slug']);
            foreach ($pageQuery->limit(4)->get() as $page) {
                $results[] = $this->result(trans('app.search.cms_pages'), $this->localized($page->title_en, $page->title_ar), $page->slug, $this->status($page->status), route('cms.pages.show', $page));
            }
        }

        return array_slice($results, 0, 30);
    }

    /**
     * @return array<int, array{group:string,title:string,subtitle:string,badge:string,url:string}>
     */
    private function tenantResults(User $actor, string $search): array
    {
        $tenantProfile = TenantProfile::query()->where('user_id', $actor->id)->first();

        if (! $tenantProfile) {
            return [];
        }

        $results = [];
        if ($this->moduleEnabled($actor, 'leases')) {
            $leaseQuery = Lease::query()->with('leaseable')->where('tenant_profile_id', $tenantProfile->id);
            $this->applySearch($leaseQuery, $search, ['code']);
            foreach ($leaseQuery->limit(5)->get() as $lease) {
                $results[] = $this->result(trans('app.search.my_leases'), $lease->code, $this->localized($lease->leaseable?->title_en, $lease->leaseable?->title_ar), $this->status($lease->status), route('leases.show', $lease));
            }
        }

        if ($this->moduleEnabled($actor, 'payments')) {
            $paymentQuery = Payment::query()->with('lease')->where('tenant_profile_id', $tenantProfile->id);
            $this->applySearch($paymentQuery, $search, ['reference', 'notes']);
            foreach ($paymentQuery->limit(5)->get() as $payment) {
                $results[] = $this->result(trans('app.search.my_payments'), $payment->reference ?: '#'.$payment->id, $payment->lease?->code ?? '', $this->status($payment->status), route('payments.show', $payment));
            }
        }

        if ($this->moduleEnabled($actor, 'maintenance')) {
            $maintenanceQuery = MaintenanceRequest::query()->with('asset')->where('tenant_profile_id', $tenantProfile->id);
            $this->applySearch($maintenanceQuery, $search, ['title', 'description', 'category']);
            foreach ($maintenanceQuery->limit(5)->get() as $maintenance) {
                $results[] = $this->result(trans('app.search.my_maintenance'), '#'.$maintenance->id.' '.$maintenance->title, $this->localized($maintenance->asset?->title_en, $maintenance->asset?->title_ar), $this->status($maintenance->status), route('maintenance-requests.show', $maintenance));
            }
        }

        if ($this->moduleEnabled($actor, 'documents')) {
            $documentQuery = Document::query()
                ->whereHasMorph('documentable', [Lease::class], fn ($leaseQuery) => $leaseQuery->where('tenant_profile_id', $tenantProfile->id));
            $this->applySearch($documentQuery, $search, ['title_en', 'title_ar', 'original_name', 'type']);
            foreach ($documentQuery->limit(5)->get() as $document) {
                $results[] = $this->result(trans('app.search.my_documents'), $this->localized($document->title_en, $document->title_ar), $document->original_name, $document->type, route('documents.download', $document));
            }
        }

        return $results;
    }

    private function adminDirectUrl(Request $request, string $search): string
    {
        $actor = $this->actor($request);

        if ($this->moduleEnabled($actor, 'assets')) {
            $asset = $this->scopeByPortfolio(Asset::query(), $actor)
                ->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('code', $search)
                        ->orWhere('meta_json->map->land_number', $search);
                })
                ->first();
            if ($asset) {
                return route('assets.show', $asset);
            }
        }

        if ($this->moduleEnabled($actor, 'leases')) {
            $lease = $this->scopeByPortfolio(Lease::query(), $actor)->where('code', $search)->first();
            if ($lease) {
                return route('leases.show', $lease);
            }
        }

        if ($this->moduleEnabled($actor, 'payments')) {
            $payment = $this->scopeByPortfolio(Payment::query(), $actor)->where('reference', $search)->first();
            if ($payment) {
                return route('payments.show', $payment);
            }
        }

        if ($this->moduleEnabled($actor, 'maintenance') && ctype_digit($search)) {
            $maintenance = $this->scopeByPortfolio(MaintenanceRequest::query(), $actor)->whereKey((int) $search)->first();
            if ($maintenance) {
                return route('maintenance-requests.show', $maintenance);
            }
        }

        return '';
    }

    private function tenantDirectUrl(User $actor, string $search): string
    {
        $tenantProfile = TenantProfile::query()->where('user_id', $actor->id)->first();
        if (! $tenantProfile) {
            return '';
        }

        if ($this->moduleEnabled($actor, 'leases')) {
            $lease = Lease::query()->where('tenant_profile_id', $tenantProfile->id)->where('code', $search)->first();
            if ($lease) {
                return route('leases.show', $lease);
            }
        }

        if ($this->moduleEnabled($actor, 'payments')) {
            $payment = Payment::query()->where('tenant_profile_id', $tenantProfile->id)->where('reference', $search)->first();
            if ($payment) {
                return route('payments.show', $payment);
            }
        }

        if ($this->moduleEnabled($actor, 'maintenance') && ctype_digit($search)) {
            $maintenance = MaintenanceRequest::query()->where('tenant_profile_id', $tenantProfile->id)->whereKey((int) $search)->first();
            if ($maintenance) {
                return route('maintenance-requests.show', $maintenance);
            }
        }

        return '';
    }

    /**
     * @return array{group:string,title:string,subtitle:string,badge:string,url:string}
     */
    private function result(string $group, ?string $title, ?string $subtitle, ?string $badge, string $url): array
    {
        return [
            'group' => $group,
            'title' => $title ?: trans('app.search.untitled'),
            'subtitle' => $subtitle ?: '',
            'badge' => $badge ?: '',
            'url' => $url,
        ];
    }

    private function moduleEnabled(User $actor, string $module): bool
    {
        return PortfolioModules::enabledForUser($actor, $module);
    }

    private function orWhereAssetMapMetadata(Builder $query, string $like): void
    {
        $query
            ->orWhere('meta_json->map->zone', 'like', $like)
            ->orWhere('meta_json->map->zone_en', 'like', $like)
            ->orWhere('meta_json->map->zone_ar', 'like', $like)
            ->orWhere('meta_json->map->land_number', 'like', $like);
    }

    private function assetResultSubtitle(Asset $asset): string
    {
        $map = is_array($asset->meta_json['map'] ?? null) ? $asset->meta_json['map'] : [];
        $parts = array_filter([
            $asset->code,
            $map['land_number'] ?? null,
            $map['zone_'.app()->getLocale()] ?? $map['zone'] ?? null,
            $asset->asset_type,
        ]);

        return implode(' · ', $parts);
    }

    protected function localized(?string $english, ?string $arabic): string
    {
        return (string) (app()->isLocale('ar')
            ? ($arabic ?: $english)
            : ($english ?: $arabic));
    }

    private function status(?string $status): string
    {
        return $status ? trans("app.status.{$status}") : '';
    }
}
