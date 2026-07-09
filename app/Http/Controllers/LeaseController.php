<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Services\LeaseFinancialService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseController extends Controller
{
    public function __construct(private readonly LeaseFinancialService $leaseFinancials) {}

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'payment_frequency' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
        $baseQuery = $this->scopeByPortfolio(Lease::query(), $actor);
        $leases = (clone $baseQuery)->with(['tenantProfile.user', 'leaseable', 'installments', 'documents']);

        $this->applyExactFilter($leases, $filters, 'portfolio_id');
        $this->applyExactFilter($leases, $filters, 'status');
        $this->applyExactFilter($leases, $filters, 'payment_frequency');
        $this->applyDateRange($leases, $filters, 'started_at');
        $this->applySearch($leases, $filters['search'], [
            'code',
            fn ($query, $search, $like) => $query->orWhereHas(
                'tenantProfile.user',
                fn ($userQuery) => $userQuery->where('name', 'like', $like)->orWhere('email', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHasMorph(
                'leaseable',
                [Asset::class],
                fn ($assetQuery) => $assetQuery
                    ->where('title_en', 'like', $like)
                    ->orWhere('title_ar', 'like', $like)
                    ->orWhere('code', 'like', $like)
            ),
        ]);

        return Inertia::render('admin/leases/index', [
            'leases' => $this->paginateTable($leases, $request, $filters, [
                'created_at',
                'code',
                'status',
                'payment_frequency',
                'started_at',
                'ends_at',
                'rent_amount',
            ], 'started_at'),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['draft', 'active', 'expired', 'terminated'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'tenantOptions' => $this->scopeByPortfolio(
                TenantProfile::query()->with('user')->whereNotNull('user_id'),
                $actor
            )->get(),
            'assetOptions' => $this->scopeByPortfolio(
                Asset::query()->where('rentable', true),
                $actor
            )->get(),
            'leaseOptions' => $this->scopeByPortfolio(
                Lease::query()->orderByDesc('created_at'),
                $actor
            )->get(['id', 'code', 'portfolio_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'tenant_profile_id' => ['required', 'integer', 'exists:tenant_profiles,id'],
            'asset_id' => ['required', 'integer', 'exists:assets,id'],
            'status' => ['required', 'string'],
            'payment_frequency' => ['required', 'string'],
            'started_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:started_at'],
            'signed_at' => ['nullable', 'date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'notes' => ['nullable', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);

        $asset = Asset::query()->findOrFail($data['asset_id']);
        abort_if($asset->portfolio_id !== $portfolioId, 422, 'Asset does not belong to the selected portfolio.');
        abort_unless(
            TenantProfile::query()
                ->whereKey($data['tenant_profile_id'])
                ->where('portfolio_id', $portfolioId)
                ->exists(),
            422,
            'Tenant does not belong to the selected portfolio.'
        );
        abort_if(
            Lease::query()
                ->where('leaseable_type', Asset::class)
                ->where('leaseable_id', $asset->id)
                ->where('status', 'active')
                ->exists(),
            422,
            'This asset already has an active lease.'
        );

        $lease = Lease::query()->create([
            'portfolio_id' => $portfolioId,
            'tenant_profile_id' => $data['tenant_profile_id'],
            'managed_by_user_id' => $actor->id,
            'leaseable_type' => Asset::class,
            'leaseable_id' => $asset->id,
            'code' => 'LEASE-'.Str::upper(Str::random(8)),
            'status' => $data['status'],
            'payment_frequency' => $data['payment_frequency'],
            'started_at' => $data['started_at'],
            'ends_at' => $data['ends_at'],
            'signed_at' => $data['signed_at'] ?? null,
            'rent_amount' => $data['rent_amount'],
            'deposit_amount' => $data['deposit_amount'] ?? 0,
            'tax_amount' => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'currency' => $data['currency'] ?? 'SAR',
            'billing_day' => $data['billing_day'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->leaseFinancials->syncInstallments($lease);
        $asset->update(['occupancy_status' => 'occupied']);

        return to_route('leases.index')->with('success', "Lease {$lease->code} created.");
    }

    public function update(Request $request, Lease $lease): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        $data = $request->validate([
            'status' => ['required', 'string'],
            'signed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'resync_installments' => ['nullable', 'boolean'],
        ]);

        $lease->update([
            'status' => $data['status'],
            'signed_at' => $data['signed_at'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (($data['resync_installments'] ?? false) && $lease->payments()->count() === 0) {
            $this->leaseFinancials->syncInstallments($lease);
        }

        return to_route('leases.index')->with('success', "Lease {$lease->code} updated.");
    }

    public function destroy(Request $request, Lease $lease): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        DB::transaction(function () use ($lease) {
            $lease->update(['status' => 'terminated']);

            if ($lease->leaseable_type !== Asset::class) {
                return;
            }

            $hasOtherActiveLease = Lease::query()
                ->whereKeyNot($lease->id)
                ->where('leaseable_type', Asset::class)
                ->where('leaseable_id', $lease->leaseable_id)
                ->where('status', 'active')
                ->exists();

            if (! $hasOtherActiveLease) {
                Asset::query()
                    ->whereKey($lease->leaseable_id)
                    ->update(['occupancy_status' => 'vacant']);
            }
        });

        return to_route('leases.index')->with('success', "Lease {$lease->code} terminated.");
    }

    public function uploadSignedContract(Request $request, Lease $lease): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        $data = $request->validate([
            'signed_contract' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $data['signed_contract'];
        $path = $file->store("documents/leases/{$lease->id}", 'local');

        Document::query()->create([
            'portfolio_id' => $lease->portfolio_id,
            'uploaded_by_user_id' => $actor->id,
            'documentable_type' => Lease::class,
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => "Signed contract {$lease->code}",
            'title_ar' => "العقد الموقع {$lease->code}",
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'is_public' => false,
        ]);

        return to_route('leases.index')->with('success', 'Signed contract uploaded.');
    }

    public function contract(Request $request, Lease $lease): StreamedResponse
    {
        $actor = $this->actor($request);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'portfolio');

        $pdf = Pdf::loadView('pdf.lease-contract', ['lease' => $lease]);
        $content = $pdf->output();
        $fileName = "lease-contract-{$lease->code}.pdf";
        $path = "generated/leases/{$lease->id}/{$fileName}";

        Storage::disk('local')->put($path, $content);

        Document::query()->updateOrCreate(
            [
                'documentable_type' => Lease::class,
                'documentable_id' => $lease->id,
                'type' => 'lease_contract',
            ],
            [
                'portfolio_id' => $lease->portfolio_id,
                'uploaded_by_user_id' => $actor->id,
                'title_en' => "Lease contract {$lease->code}",
                'title_ar' => "عقد الإيجار {$lease->code}",
                'disk' => 'local',
                'file_path' => $path,
                'original_name' => $fileName,
                'mime_type' => 'application/pdf',
                'file_size' => strlen($content),
                'is_public' => false,
            ],
        );

        return response()->streamDownload(fn () => print ($content), $fileName);
    }

    public function statement(Request $request, Lease $lease): StreamedResponse
    {
        $actor = $this->actor($request);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'payments');

        $pdf = Pdf::loadView('pdf.tenant-statement', ['lease' => $lease]);

        return response()->streamDownload(fn () => print ($pdf->output()), "tenant-statement-{$lease->code}.pdf");
    }
}
