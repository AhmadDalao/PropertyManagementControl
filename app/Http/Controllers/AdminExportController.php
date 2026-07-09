<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CmsPage;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\MediaFile;
use App\Models\Payment;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportController extends Controller
{
    public function __invoke(Request $request, string $resource): StreamedResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->isAllowedResource($resource), 404);

        if ($resource === 'cms-pages') {
            $this->requireRoles($actor, ['superadmin']);
        } else {
            $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        }

        return match ($resource) {
            'portfolios' => $this->exportPortfolios($request),
            'users' => $this->exportUsers($request),
            'assets' => $this->exportAssets($request),
            'tenants' => $this->exportTenants($request),
            'leases' => $this->exportLeases($request),
            'payments' => $this->exportPayments($request),
            'maintenance-requests' => $this->exportMaintenance($request),
            'expenses' => $this->exportExpenses($request),
            'documents' => $this->exportDocuments($request),
            'cms-pages' => $this->exportCmsPages($request),
            'media-files' => $this->exportMediaFiles($request),
        };
    }

    private function exportPortfolios(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all']);
        $query = $this->scopeByPortfolio(Portfolio::query(), $actor, 'id')->withCount(['assets', 'users', 'leases']);
        $this->applyExactFilter($query, $filters, 'status');
        $this->applySearch($query, $filters['search'], ['name_en', 'name_ar', 'code', 'contact_email', 'city', 'country']);

        return $this->csv('portfolios', ['Code', 'Name', 'Arabic Name', 'Status', 'City', 'Country', 'Users', 'Assets', 'Leases'], $query, fn (Portfolio $row) => [
            $row->code,
            $row->name_en,
            $row->name_ar,
            $row->status,
            $row->city,
            $row->country,
            $row->users_count,
            $row->assets_count,
            $row->leases_count,
        ]);
    }

    private function exportUsers(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'role' => 'all']);
        $query = $this->scopeByPortfolio(User::query(), $actor)->with(['portfolio', 'roles']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applySearch($query, $filters['search'], [
            'name',
            'email',
            'phone',
            fn ($query, $search, $like) => $query->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'like', $like)),
        ]);

        if (($filters['role'] ?? 'all') !== 'all') {
            $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $filters['role']));
        }

        return $this->csv('users', ['Name', 'Email', 'Phone', 'Roles', 'Status', 'Portfolio'], $query, fn (User $row) => [
            $row->name,
            $row->email,
            $row->phone,
            $row->roles->pluck('name')->join(', '),
            $row->status,
            $row->portfolio?->name_en,
        ]);
    }

    private function exportAssets(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'asset_type' => 'all', 'usage_type' => 'all', 'occupancy_status' => 'all', 'rentable' => 'all']);
        $query = $this->scopeByPortfolio(Asset::query(), $actor)->with(['portfolio', 'parent']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'asset_type');
        $this->applyExactFilter($query, $filters, 'usage_type');
        $this->applyExactFilter($query, $filters, 'occupancy_status');

        if (($filters['rentable'] ?? 'all') !== 'all') {
            $query->where('rentable', $filters['rentable'] === 'yes');
        }

        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'code', 'address', 'level_label', 'unit_label']);

        return $this->csv('assets', ['Code', 'Title', 'Arabic Title', 'Type', 'Usage', 'Occupancy', 'Status', 'Rentable', 'Value', 'Currency', 'Parent', 'Portfolio'], $query, fn (Asset $row) => [
            $row->code,
            $row->title_en,
            $row->title_ar,
            $row->asset_type,
            $row->usage_type,
            $row->occupancy_status,
            $row->status,
            $row->rentable ? 'Yes' : 'No',
            $row->valuation_amount,
            $row->currency,
            $row->parent?->title_en,
            $row->portfolio?->name_en,
        ]);
    }

    private function exportTenants(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'profile_type' => 'all']);
        $query = $this->scopeByPortfolio(TenantProfile::query(), $actor)->with(['portfolio', 'user']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'profile_type');
        $this->applySearch($query, $filters['search'], [
            'national_id',
            'company_name',
            fn ($query, $search, $like) => $query->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like)),
        ]);

        return $this->csv('tenants', ['Name', 'Email', 'Phone', 'Profile', 'National ID', 'Company', 'Status', 'Portfolio'], $query, fn (TenantProfile $row) => [
            $row->user?->name,
            $row->user?->email,
            $row->user?->phone,
            $row->profile_type,
            $row->national_id,
            $row->company_name,
            $row->status,
            $row->portfolio?->name_en,
        ]);
    }

    private function exportLeases(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'payment_frequency' => 'all', 'date_from' => '', 'date_to' => '']);
        $query = $this->scopeByPortfolio(Lease::query(), $actor)->with(['tenantProfile.user', 'leaseable', 'installments']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'payment_frequency');
        $this->applyDateRange($query, $filters, 'started_at');
        $this->applySearch($query, $filters['search'], ['code']);

        return $this->csv('leases', ['Code', 'Tenant', 'Asset', 'Status', 'Frequency', 'Start', 'End', 'Rent', 'Paid', 'Balance', 'Currency'], $query, fn (Lease $row) => [
            $row->code,
            $row->tenantProfile?->user?->name,
            $row->leaseable?->title_en,
            $row->status,
            $row->payment_frequency,
            $row->started_at?->toDateString(),
            $row->ends_at?->toDateString(),
            $row->rent_amount,
            $row->total_paid,
            $row->balance_remaining,
            $row->currency,
        ]);
    }

    private function exportPayments(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'type' => 'all', 'method' => 'all', 'date_from' => '', 'date_to' => '']);
        $query = $this->scopeByPortfolio(Payment::query(), $actor)->with(['lease', 'tenantProfile.user']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'type');
        $this->applyExactFilter($query, $filters, 'method');
        $this->applyDateRange($query, $filters, 'received_on');
        $this->applySearch($query, $filters['search'], ['reference', 'notes']);

        return $this->csv('payments', ['Reference', 'Tenant', 'Lease', 'Date', 'Type', 'Method', 'Status', 'Amount', 'Currency'], $query, fn (Payment $row) => [
            $row->reference ?: '#'.$row->id,
            $row->tenantProfile?->user?->name,
            $row->lease?->code,
            $row->received_on?->toDateString(),
            $row->type,
            $row->method,
            $row->status,
            $row->amount,
            $row->currency,
        ]);
    }

    private function exportMaintenance(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'category' => 'all', 'priority' => 'all', 'date_from' => '', 'date_to' => '']);
        $query = $this->scopeByPortfolio(MaintenanceRequest::query(), $actor)->with(['asset', 'tenantProfile.user', 'assignedTo']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'category');
        $this->applyExactFilter($query, $filters, 'priority');
        $this->applyDateRange($query, $filters, 'created_at');
        $this->applySearch($query, $filters['search'], ['title', 'description', 'category']);

        return $this->csv('maintenance-requests', ['ID', 'Title', 'Tenant', 'Asset', 'Category', 'Priority', 'Status', 'Requested', 'Assigned To'], $query, fn (MaintenanceRequest $row) => [
            $row->id,
            $row->title,
            $row->tenantProfile?->user?->name,
            $row->asset?->title_en,
            $row->category,
            $row->priority,
            $row->status,
            $row->requested_at?->toDateTimeString(),
            $row->assignedTo?->name,
        ]);
    }

    private function exportExpenses(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'category' => 'all', 'date_from' => '', 'date_to' => '']);
        $query = $this->scopeByPortfolio(ExpenseEntry::query(), $actor)->with(['asset']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'category');
        $this->applyDateRange($query, $filters, 'incurred_on');
        $this->applySearch($query, $filters['search'], ['title', 'description', 'category', 'vendor_name']);

        return $this->csv('expenses', ['Title', 'Asset', 'Category', 'Vendor', 'Date', 'Status', 'Amount', 'Currency'], $query, fn (ExpenseEntry $row) => [
            $row->title,
            $row->asset?->title_en,
            $row->category,
            $row->vendor_name,
            $row->incurred_on?->toDateString(),
            $row->status,
            $row->amount,
            $row->currency,
        ]);
    }

    private function exportDocuments(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, [
            'type' => 'all',
            'attachment' => 'all',
            'visibility' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
        $query = $this->scopeByPortfolio(Document::query(), $actor)->with(['portfolio', 'uploadedBy']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'type');

        if (($filters['attachment'] ?? 'all') !== 'all') {
            $query->whereIn('documentable_type', $this->documentableExportTypes((string) $filters['attachment']));
        }

        if (($filters['visibility'] ?? 'all') === 'public') {
            $query->where('is_public', true);
        }

        if (($filters['visibility'] ?? 'all') === 'private') {
            $query->where('is_public', false);
        }

        $this->applyDateRange($query, $filters, 'created_at');
        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'original_name', 'type', 'file_path']);

        return $this->csv('documents', ['Title', 'Arabic Title', 'Type', 'Attachment', 'Original File', 'Mime Type', 'Size', 'Public', 'Portfolio', 'Uploaded By', 'Created'], $query, fn (Document $row) => [
            $row->title_en,
            $row->title_ar,
            $row->type,
            class_basename($row->documentable_type).' #'.$row->documentable_id,
            $row->original_name,
            $row->mime_type,
            $row->file_size,
            $row->is_public ? 'Yes' : 'No',
            $row->portfolio?->name_en,
            $row->uploadedBy?->name,
            $row->created_at?->toDateTimeString(),
        ]);
    }

    private function exportCmsPages(Request $request): StreamedResponse
    {
        $filters = $this->tableFilters($request, ['status' => 'all']);
        $query = CmsPage::query();
        $this->applyExactFilter($query, $filters, 'status');
        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'slug', 'excerpt_en', 'excerpt_ar']);

        return $this->csv('cms-pages', ['Title', 'Arabic Title', 'Slug', 'Status', 'Homepage', 'Visible', 'Published At'], $query, fn (CmsPage $row) => [
            $row->title_en,
            $row->title_ar,
            $row->slug,
            $row->status,
            $row->is_homepage ? 'Yes' : 'No',
            $row->is_visible ? 'Yes' : 'No',
            $row->published_at,
        ]);
    }

    private function exportMediaFiles(Request $request): StreamedResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['visibility' => 'all', 'collection' => 'all']);
        $query = $this->scopeByPortfolio(MediaFile::query(), $actor)->with('portfolio');
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'visibility');
        $this->applyExactFilter($query, $filters, 'collection');
        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'collection', 'path', 'mime_type']);

        return $this->csv('media-files', ['Title', 'Collection', 'Visibility', 'Path', 'Mime Type', 'Size', 'Portfolio'], $query, fn (MediaFile $row) => [
            $row->title_en ?: 'Media #'.$row->id,
            $row->collection,
            $row->visibility,
            $row->path,
            $row->mime_type,
            $row->size,
            $row->portfolio?->name_en,
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function csv(string $resource, array $headers, Builder $query, callable $rowMapper): StreamedResponse
    {
        $filename = $resource.'-export-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $query, $rowMapper): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            $query->orderByDesc('id')->chunk(500, function ($rows) use ($handle, $rowMapper): void {
                foreach ($rows as $row) {
                    fputcsv($handle, $rowMapper($row));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function isAllowedResource(string $resource): bool
    {
        return in_array($resource, [
            'assets',
            'tenants',
            'leases',
            'payments',
            'maintenance-requests',
            'expenses',
            'documents',
            'users',
            'portfolios',
            'cms-pages',
            'media-files',
        ], true);
    }

    /**
     * @return array<int, string>
     */
    private function documentableExportTypes(string $alias): array
    {
        return match ($alias) {
            'lease' => ['lease', Lease::class],
            'asset' => ['asset', Asset::class],
            'payment' => ['payment', Payment::class],
            default => [$alias],
        };
    }
}
