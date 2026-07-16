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
                'message' => 'Type at least 2 characters.',
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
            $this->applySearch($portfolioQuery, $search, ['name_en', 'name_ar', 'code', 'contact_email']);
            foreach ($portfolioQuery->limit(5)->get() as $portfolio) {
                $results[] = $this->result('Portfolios', $portfolio->name_en, $portfolio->code, $portfolio->status, route('portfolios.show', $portfolio));
            }
        }

        if ($this->moduleEnabled($actor, 'assets')) {
            $assetQuery = $this->scopeByPortfolio(Asset::query(), $actor);
            $this->applySearch($assetQuery, $search, [
                'title_en',
                'title_ar',
                'code',
                'address',
                fn (Builder $query, string $term, string $like) => $this->orWhereAssetMapMetadata($query, $like),
            ]);
            foreach ($assetQuery->limit(6)->get() as $asset) {
                $results[] = $this->result('Assets', $asset->title_en, $this->assetResultSubtitle($asset), $asset->occupancy_status, route('assets.show', $asset));
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
                $results[] = $this->result('Tenants', $tenant->user?->name ?? 'Tenant #'.$tenant->id, $tenant->user?->email ?? $tenant->company_name ?? '', $tenant->status, route('tenants.show', $tenant));
            }
        }

        if ($this->moduleEnabled($actor, 'leases')) {
            $leaseQuery = $this->scopeByPortfolio(Lease::query()->with(['tenantProfile.user', 'leaseable']), $actor);
            $this->applySearch($leaseQuery, $search, [
                'code',
                fn ($query, $term, $like) => $query->orWhereHas('tenantProfile.user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)),
                fn ($query, $term, $like) => $query->orWhereHasMorph('leaseable', [Asset::class], fn ($assetQuery) => $assetQuery->where('title_en', 'like', $like)->orWhere('code', 'like', $like)),
            ]);
            foreach ($leaseQuery->limit(5)->get() as $lease) {
                $results[] = $this->result('Leases', $lease->code, ($lease->tenantProfile?->user?->name ?? '-').' · '.($lease->leaseable?->title_en ?? '-'), $lease->status, route('leases.show', $lease));
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
                $results[] = $this->result('Payments', $payment->reference ?: '#'.$payment->id, $payment->tenantProfile?->user?->name ?? $payment->lease?->code ?? '', $payment->status, route('payments.show', $payment));
            }
        }

        if ($this->moduleEnabled($actor, 'maintenance')) {
            $maintenanceQuery = $this->scopeByPortfolio(MaintenanceRequest::query()->with(['asset', 'tenantProfile.user']), $actor);
            $this->applySearch($maintenanceQuery, $search, [
                'title',
                'description',
                'category',
                fn ($query, $term, $like) => $query->orWhereHas('asset', fn ($assetQuery) => $assetQuery->where('title_en', 'like', $like)->orWhere('code', 'like', $like)),
                fn ($query, $term, $like) => $query->orWhereHas('tenantProfile.user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)),
            ]);
            foreach ($maintenanceQuery->limit(5)->get() as $maintenance) {
                $results[] = $this->result('Maintenance', '#'.$maintenance->id.' '.$maintenance->title, $maintenance->asset?->title_en ?? $maintenance->tenantProfile?->user?->name ?? '', $maintenance->status, route('maintenance-requests.show', $maintenance));
            }
        }

        if ($this->moduleEnabled($actor, 'users')) {
            $userQuery = $this->scopeByPortfolio(User::query()->with('roles'), $actor);
            $this->applySearch($userQuery, $search, ['name', 'email', 'phone']);
            foreach ($userQuery->limit(5)->get() as $user) {
                $results[] = $this->result('Users', $user->name, $user->email, $user->roles->pluck('name')->join(', '), route('users.show', $user));
            }
        }

        if ($this->moduleEnabled($actor, 'documents')) {
            $documentQuery = $this->scopeByPortfolio(Document::query(), $actor);
            $this->applySearch($documentQuery, $search, ['title_en', 'title_ar', 'original_name', 'type', 'file_path']);
            foreach ($documentQuery->limit(5)->get() as $document) {
                $results[] = $this->result('Documents', $document->title_en, $document->original_name, $document->type, route('documents.show', $document));
            }
        }

        if ($this->moduleEnabled($actor, 'media')) {
            $mediaQuery = $this->scopeByPortfolio(MediaFile::query(), $actor);
            $this->applySearch($mediaQuery, $search, ['title_en', 'title_ar', 'collection', 'path']);
            foreach ($mediaQuery->limit(4)->get() as $media) {
                $results[] = $this->result('Media', $media->title_en ?: 'Media #'.$media->id, $media->collection, $media->visibility, route('media-files.show', $media));
            }
        }

        if ($actor->hasRole('superadmin')) {
            $pageQuery = CmsPage::query();
            $this->applySearch($pageQuery, $search, ['title_en', 'title_ar', 'slug']);
            foreach ($pageQuery->limit(4)->get() as $page) {
                $results[] = $this->result('CMS Pages', $page->title_en, $page->slug, $page->status, route('cms.pages.show', $page));
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
                $results[] = $this->result('My Leases', $lease->code, $lease->leaseable?->title_en ?? '', $lease->status, route('leases.show', $lease));
            }
        }

        if ($this->moduleEnabled($actor, 'payments')) {
            $paymentQuery = Payment::query()->with('lease')->where('tenant_profile_id', $tenantProfile->id);
            $this->applySearch($paymentQuery, $search, ['reference', 'notes']);
            foreach ($paymentQuery->limit(5)->get() as $payment) {
                $results[] = $this->result('My Payments', $payment->reference ?: '#'.$payment->id, $payment->lease?->code ?? '', $payment->status, route('payments.show', $payment));
            }
        }

        if ($this->moduleEnabled($actor, 'maintenance')) {
            $maintenanceQuery = MaintenanceRequest::query()->with('asset')->where('tenant_profile_id', $tenantProfile->id);
            $this->applySearch($maintenanceQuery, $search, ['title', 'description', 'category']);
            foreach ($maintenanceQuery->limit(5)->get() as $maintenance) {
                $results[] = $this->result('My Maintenance', '#'.$maintenance->id.' '.$maintenance->title, $maintenance->asset?->title_en ?? '', $maintenance->status, route('maintenance-requests.show', $maintenance));
            }
        }

        if ($this->moduleEnabled($actor, 'documents')) {
            $documentQuery = Document::query()
                ->whereHasMorph('documentable', [Lease::class], fn ($leaseQuery) => $leaseQuery->where('tenant_profile_id', $tenantProfile->id));
            $this->applySearch($documentQuery, $search, ['title_en', 'title_ar', 'original_name', 'type']);
            foreach ($documentQuery->limit(5)->get() as $document) {
                $results[] = $this->result('My Documents', $document->title_en, $document->original_name, $document->type, route('documents.download', $document));
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
            'title' => $title ?: 'Untitled',
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
            ->orWhere('meta_json->map->land_number', 'like', $like);
    }

    private function assetResultSubtitle(Asset $asset): string
    {
        $map = is_array($asset->meta_json['map'] ?? null) ? $asset->meta_json['map'] : [];
        $parts = array_filter([
            $asset->code,
            $map['land_number'] ?? null,
            $map['zone'] ?? null,
            $asset->asset_type,
        ]);

        return implode(' · ', $parts);
    }
}
