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
use App\Modules\Wording\UiTranslationCatalog;
use App\Services\XlsxWorkbook;
use App\Support\PortfolioModules;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminExportController extends Controller
{
    public function __construct(
        private readonly XlsxWorkbook $workbook,
        private readonly UiTranslationCatalog $translations,
    ) {}

    public function __invoke(Request $request, string $resource): BinaryFileResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->isAllowedResource($resource), 404);

        if ($resource === 'cms-pages') {
            $this->requireRoles($actor, ['superadmin']);
        } else {
            $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        }

        $module = PortfolioModules::exportResourceModule($resource);
        abort_unless(! $module || PortfolioModules::enabledForUser($actor, $module), 403, trans('app.errors.module_disabled'));

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
            default => abort(404),
        };
    }

    private function exportPortfolios(Request $request): BinaryFileResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all']);
        $query = $this->scopeByPortfolio(Portfolio::query(), $actor, 'id')->withCount(['assets', 'users', 'leases']);
        $this->applyExactFilter($query, $filters, 'status');
        $this->applySearch($query, $filters['search'], ['name_en', 'name_ar', 'code', 'contact_email', 'city', 'country', 'address', 'address_ar']);

        return $this->xlsx('portfolios', ['Code', 'Name', 'Arabic Name', 'Status', 'City', 'Country', 'Users', 'Assets', 'Leases'], $query, fn (Portfolio $row) => [
            $row->code,
            $row->name_en,
            $row->name_ar,
            $this->option($row->status),
            $row->city,
            $row->country,
            $row->users_count,
            $row->assets_count,
            $row->leases_count,
        ]);
    }

    private function exportUsers(Request $request): BinaryFileResponse
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

        return $this->xlsx('users', ['Name', 'Email', 'Phone', 'Roles', 'Status', 'Portfolio'], $query, fn (User $row) => [
            $row->name,
            $row->email,
            $row->phone,
            $row->roles->pluck('name')->map(fn (string $role): string => $this->option($role))->join(', '),
            $this->option($row->status),
            $this->localizedModel($row->portfolio, 'name_en', 'name_ar'),
        ]);
    }

    private function exportAssets(Request $request): BinaryFileResponse
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

        $this->applySearch($query, $filters['search'], [
            'title_en',
            'title_ar',
            'code',
            'address',
            'address_ar',
            'level_label',
            'unit_label',
            fn (Builder $query, string $search, string $like) => $query
                ->orWhere('meta_json->map->zone', 'like', $like)
                ->orWhere('meta_json->map->zone_en', 'like', $like)
                ->orWhere('meta_json->map->zone_ar', 'like', $like)
                ->orWhere('meta_json->map->land_number', 'like', $like),
        ]);

        return $this->xlsx('assets', ['Code', 'Title', 'Arabic Title', 'Type', 'Usage', 'Occupancy', 'Status', 'Rentable', 'Value', 'Currency', 'Parent', 'Portfolio'], $query, fn (Asset $row) => [
            $row->code,
            $row->title_en,
            $row->title_ar,
            $this->option($row->asset_type),
            $this->option($row->usage_type),
            $this->option($row->occupancy_status),
            $this->option($row->status),
            $this->copy($row->rentable ? 'Yes' : 'No'),
            $row->valuation_amount,
            $row->currency,
            $this->localizedModel($row->parent, 'title_en', 'title_ar'),
            $this->localizedModel($row->portfolio, 'name_en', 'name_ar'),
        ]);
    }

    private function exportTenants(Request $request): BinaryFileResponse
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

        return $this->xlsx('tenants', ['Name', 'Email', 'Phone', 'Profile', 'National ID', 'Company', 'Status', 'Portfolio'], $query, fn (TenantProfile $row) => [
            $row->user?->name,
            $row->user?->email,
            $row->user?->phone,
            $this->option($row->profile_type),
            $row->national_id,
            $row->company_name,
            $this->option($row->status),
            $this->localizedModel($row->portfolio, 'name_en', 'name_ar'),
        ]);
    }

    private function exportLeases(Request $request): BinaryFileResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'payment_frequency' => 'all', 'date_from' => '', 'date_to' => '']);
        $query = $this->scopeByPortfolio(Lease::query(), $actor)->with(['tenantProfile.user', 'leaseable', 'installments']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'payment_frequency');
        $this->applyDateRange($query, $filters, 'started_at');
        $this->applySearch($query, $filters['search'], ['code']);

        return $this->xlsx('leases', ['Code', 'Tenant', 'Asset', 'Status', 'Frequency', 'Start', 'End', 'Rent', 'Paid', 'Balance', 'Currency'], $query, fn (Lease $row) => [
            $row->code,
            $row->tenantProfile?->user?->name,
            $this->localizedModel($row->leaseable, 'title_en', 'title_ar'),
            $this->option($row->status),
            $this->option($row->payment_frequency),
            $this->date($row->started_at),
            $this->date($row->ends_at),
            $row->rent_amount,
            $row->total_paid,
            $row->balance_remaining,
            $row->currency,
        ]);
    }

    private function exportPayments(Request $request): BinaryFileResponse
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

        return $this->xlsx('payments', ['Reference', 'Tenant', 'Lease', 'Date', 'Type', 'Method', 'Status', 'Amount', 'Currency'], $query, fn (Payment $row) => [
            $row->reference ?: '#'.$row->id,
            $row->tenantProfile?->user?->name,
            $row->lease?->code,
            $this->date($row->received_on),
            $this->option($row->type),
            $this->option($row->method),
            $this->option($row->status),
            $row->amount,
            $row->currency,
        ]);
    }

    private function exportMaintenance(Request $request): BinaryFileResponse
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

        return $this->xlsx('maintenance-requests', ['ID', 'Title', 'Tenant', 'Asset', 'Category', 'Priority', 'Status', 'Requested', 'Assigned To'], $query, fn (MaintenanceRequest $row) => [
            $row->id,
            $row->title,
            $row->tenantProfile?->user?->name,
            $this->localizedModel($row->asset, 'title_en', 'title_ar'),
            $this->option($row->category),
            $this->option($row->priority),
            $this->option($row->status),
            $this->date($row->requested_at, true),
            $row->assignedTo?->name,
        ]);
    }

    private function exportExpenses(Request $request): BinaryFileResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['status' => 'all', 'category' => 'all', 'date_from' => '', 'date_to' => '']);
        $query = $this->scopeByPortfolio(ExpenseEntry::query(), $actor)->with(['asset']);
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'status');
        $this->applyExactFilter($query, $filters, 'category');
        $this->applyDateRange($query, $filters, 'incurred_on');
        $this->applySearch($query, $filters['search'], ['title', 'description', 'category', 'vendor_name']);

        return $this->xlsx('expenses', ['Title', 'Asset', 'Category', 'Vendor', 'Date', 'Status', 'Amount', 'Currency'], $query, fn (ExpenseEntry $row) => [
            $row->title,
            $this->localizedModel($row->asset, 'title_en', 'title_ar'),
            $this->option($row->category),
            $row->vendor_name,
            $this->date($row->incurred_on),
            $this->option($row->status),
            $row->amount,
            $row->currency,
        ]);
    }

    private function exportDocuments(Request $request): BinaryFileResponse
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
        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'original_name', 'type']);

        return $this->xlsx('documents', ['Title', 'Arabic Title', 'Type', 'Attachment', 'Original File', 'Mime Type', 'Size', trans('app.documents.portal_visible'), 'Portfolio', 'Uploaded By', 'Created'], $query, fn (Document $row) => [
            $row->title_en,
            $row->title_ar,
            $this->option($row->type),
            $this->copy(class_basename($row->documentable_type)).' #'.$row->documentable_id,
            $row->original_name,
            $row->mime_type,
            $row->file_size,
            $this->copy($row->is_public ? 'Yes' : 'No'),
            $this->localizedModel($row->portfolio, 'name_en', 'name_ar'),
            $row->uploadedBy?->name,
            $this->date($row->created_at, true),
        ]);
    }

    private function exportCmsPages(Request $request): BinaryFileResponse
    {
        $filters = $this->tableFilters($request, ['status' => 'all']);
        $query = CmsPage::query();
        $this->applyExactFilter($query, $filters, 'status');
        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'slug', 'excerpt_en', 'excerpt_ar']);

        return $this->xlsx('cms-pages', ['Title', 'Arabic Title', 'Slug', 'Status', 'Homepage', 'Visible', 'Published At'], $query, fn (CmsPage $row) => [
            $row->title_en,
            $row->title_ar,
            $row->slug,
            $this->option($row->status),
            $this->copy($row->is_homepage ? 'Yes' : 'No'),
            $this->copy($row->is_visible ? 'Yes' : 'No'),
            $this->date($row->published_at, true),
        ]);
    }

    private function exportMediaFiles(Request $request): BinaryFileResponse
    {
        $actor = $this->actor($request);
        $filters = $this->tableFilters($request, ['visibility' => 'all', 'collection' => 'all']);
        $query = $this->scopeByPortfolio(MediaFile::query(), $actor)->with('portfolio');
        $this->applyExactFilter($query, $filters, 'portfolio_id');
        $this->applyExactFilter($query, $filters, 'visibility');
        $this->applyExactFilter($query, $filters, 'collection');
        $this->applySearch($query, $filters['search'], ['title_en', 'title_ar', 'collection', 'path', 'mime_type']);

        return $this->xlsx('media-files', ['Title', 'Collection', 'Visibility', 'Path', 'Mime Type', 'Size', 'Portfolio'], $query, fn (MediaFile $row) => [
            $row->title_en ?: $this->copy('Media').' #'.$row->id,
            $this->option($row->collection),
            $this->option($row->visibility),
            $row->path,
            $row->mime_type,
            $row->size,
            $this->localizedModel($row->portfolio, 'name_en', 'name_ar'),
        ]);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  Builder<Model>  $query
     */
    private function xlsx(string $resource, array $headers, Builder $query, callable $rowMapper): BinaryFileResponse
    {
        $filename = $resource.'-export-'.now()->format('Ymd-His').'.xlsx';
        $rows = [array_map(fn (string $header): string => $this->copy($header), $headers)];

        $query->orderByDesc('id')->chunk(500, function ($records) use (&$rows, $rowMapper): void {
            foreach ($records as $record) {
                $rows[] = array_values($rowMapper($record));
            }
        });

        $path = $this->workbook->create(
            $rows,
            $this->copy(str($resource)->headline()->toString()),
        );

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
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

    private function copy(string $value): string
    {
        return $this->translations->text($value);
    }

    private function option(?string $value): string
    {
        if (blank($value)) {
            return '';
        }

        $statusKey = "app.status.{$value}";

        if (trans()->has($statusKey)) {
            return trans($statusKey);
        }

        $roleKey = "app.roles.{$value}";

        if (trans()->has($roleKey)) {
            return trans($roleKey);
        }

        return $this->copy(Str::of($value)->replace('_', ' ')->title()->toString());
    }

    private function date(mixed $value, bool $withTime = false): string
    {
        if (! $value instanceof CarbonInterface) {
            return '';
        }

        if (app()->isLocale('ar')) {
            return $value->format($withTime ? 'Y/m/d H:i' : 'Y/m/d');
        }

        return $value->format($withTime ? 'M j, Y H:i' : 'M j, Y');
    }

    private function localizedModel(
        ?Model $model,
        string $englishAttribute,
        string $arabicAttribute,
    ): ?string {
        if ($model === null) {
            return null;
        }

        $english = $model->getAttribute($englishAttribute);
        $arabic = $model->getAttribute($arabicAttribute);

        return $this->localized(
            is_string($english) ? $english : null,
            is_string($arabic) ? $arabic : null,
        );
    }
}
