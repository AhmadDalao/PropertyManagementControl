<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Leases\LeaseLifecycle;
use App\Support\BilingualPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaseController extends Controller
{
    public function __construct(
        private readonly LeaseLifecycle $leaseLifecycle,
        private readonly BilingualPdf $pdf,
    ) {}

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

        $paginatedLeases = $this->paginateTable($leases, $request, $filters, [
            'created_at',
            'code',
            'status',
            'payment_frequency',
            'started_at',
            'ends_at',
            'rent_amount',
        ], 'started_at')->through(fn (Lease $lease) => $this->leaseTableRow($lease));

        return Inertia::render('admin/leases/index', [
            'leases' => $paginatedLeases,
            'leaseInsights' => $this->leaseInsights($baseQuery),
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

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->leaseFormPage($actor),
        ]);
    }

    public function show(Request $request, Lease $lease): Response
    {
        $actor = $this->actor($request);
        $this->ensureLeaseAccess($actor, $lease);
        $lease->loadMissing([
            'portfolio',
            'tenantProfile.user',
            'leaseable',
            'managedBy',
            'installments',
            'payments.allocations.leaseInstallment',
            'documents',
        ]);
        $adminMode = $actor->hasAnyRole(['superadmin', 'owner', 'property_manager']);
        $localizedAssetTitle = $this->localized($lease->leaseable?->title_en, $lease->leaseable?->title_ar);
        $localizedPortfolioName = $this->localized($lease->portfolio?->name_en, $lease->portfolio?->name_ar);

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Lease detail',
                    'title' => $lease->code,
                    'description' => trim(($lease->tenantProfile?->user?->name ?? 'Tenant').' · '.($localizedAssetTitle ?? 'Asset').' · '.$lease->status),
                    'backHref' => $adminMode ? route('leases.index') : route('dashboard'),
                    'backLabel' => $adminMode ? 'All leases' : 'Dashboard',
                    'actions' => array_values(array_filter([
                        $adminMode ? ['label' => 'Edit lease', 'href' => route('leases.edit', $lease), 'variant' => 'primary'] : null,
                        ['label' => 'Contract PDF', 'href' => route('leases.contract', $lease), 'variant' => 'secondary'],
                        $adminMode ? [
                            'label' => 'Upload signed PDF',
                            'href' => route('documents.create', [
                                'documentable_type' => 'lease',
                                'documentable_id' => $lease->id,
                                'type' => 'signed_contract',
                                'title_en' => "Signed contract {$lease->code}",
                                'title_ar' => "العقد الموقع {$lease->code}",
                            ]),
                            'variant' => 'secondary',
                        ] : null,
                        ['label' => 'Tenant statement', 'href' => route('leases.statement', $lease), 'variant' => 'secondary'],
                        $adminMode ? ['label' => 'Record payment', 'href' => route('payments.create', ['lease_id' => $lease->id]), 'variant' => 'secondary'] : null,
                    ])),
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Total due', 'value' => number_format((float) $lease->total_due, 2).' '.$lease->currency, 'tone' => 'primary'],
                    ['label' => 'Paid', 'value' => number_format((float) $lease->total_paid, 2).' '.$lease->currency, 'tone' => 'teal'],
                    ['label' => 'Remaining', 'value' => number_format((float) $lease->balance_remaining, 2).' '.$lease->currency, 'tone' => $lease->balance_remaining > 0 ? 'danger' : 'teal'],
                    ['label' => 'Days left', 'value' => $lease->days_remaining ?? 'Ended'],
                ]),
                'sections' => [
                    [
                        'title' => 'Contract',
                        'description' => 'Tenant, rented asset, dates, billing, and manager.',
                        'items' => $this->detailItems([
                            ['label' => 'Tenant', 'value' => $lease->tenantProfile?->user?->name, 'href' => $lease->tenantProfile ? route('tenants.show', $lease->tenantProfile) : null],
                            ['label' => 'Asset', 'value' => $localizedAssetTitle, 'href' => $lease->leaseable ? route('assets.show', $lease->leaseable) : null],
                            ['label' => 'Portfolio', 'value' => $localizedPortfolioName, 'href' => $lease->portfolio ? route('portfolios.show', $lease->portfolio) : null],
                            ['label' => 'Managed by', 'value' => $lease->managedBy?->name, 'href' => $lease->managedBy ? route('users.show', $lease->managedBy) : null],
                            ['label' => 'Started', 'value' => $lease->started_at?->toDateString()],
                            ['label' => 'Ends', 'value' => $lease->ends_at?->toDateString()],
                            ['label' => 'Signed', 'value' => $lease->signed_at?->toDateString() ?? 'Not signed'],
                            ['label' => 'Frequency', 'value' => $lease->payment_frequency],
                            ['label' => 'Notes', 'value' => $lease->notes],
                        ]),
                    ],
                    [
                        'title' => 'Amounts',
                        'description' => 'Operational rent values used to generate installments.',
                        'items' => $this->detailItems([
                            ['label' => 'Rent amount', 'value' => number_format((float) $lease->rent_amount, 2).' '.$lease->currency],
                            ['label' => 'Deposit', 'value' => number_format((float) $lease->deposit_amount, 2).' '.$lease->currency],
                            ['label' => 'Tax', 'value' => number_format((float) $lease->tax_amount, 2).' '.$lease->currency],
                            ['label' => 'Discount', 'value' => number_format((float) $lease->discount_amount, 2).' '.$lease->currency],
                            ['label' => 'Billing day', 'value' => $lease->billing_day],
                        ]),
                    ],
                ],
                'related' => [
                    [
                        'title' => 'Installments',
                        'description' => 'Due schedule and allocation state.',
                        'columns' => ['#', 'Due date', 'Status', 'Due', 'Paid'],
                        'rows' => $lease->installments->map(fn ($installment) => [
                            '#' => $installment->sequence,
                            'Due date' => $installment->due_date?->toDateString(),
                            'Status' => $installment->status,
                            'Due' => number_format((float) $installment->amount_due, 2),
                            'Paid' => number_format((float) $installment->amount_paid, 2),
                        ])->all(),
                        'emptyText' => 'No installments generated yet.',
                    ],
                    [
                        'title' => 'Payments',
                        'description' => 'Money posted against this lease.',
                        'columns' => ['Payment', 'Date', 'Status', 'Amount'],
                        'rows' => $lease->payments->map(fn ($payment) => [
                            'Payment' => $payment->reference ?: '#'.$payment->id,
                            'Date' => $payment->received_on?->toDateString(),
                            'Status' => $payment->status,
                            'Amount' => number_format((float) $payment->amount, 2).' '.$payment->currency,
                        ])->all(),
                        'emptyText' => 'No payments recorded yet.',
                        'actionHref' => route('payments.create', ['lease_id' => $lease->id]),
                        'actionLabel' => 'Record payment',
                    ],
                ],
                'documents' => $this->documentStrip($lease->documents),
                'timeline' => $this->activityTimeline($lease),
            ],
        ]);
    }

    public function edit(Request $request, Lease $lease): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->leaseFormPage($actor, $lease),
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
            'status' => ['required', Rule::in(['draft', 'active', 'expired', 'terminated'])],
            'payment_frequency' => ['required', Rule::in(['monthly', 'quarterly', 'yearly'])],
            'started_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:started_at'],
            'signed_at' => ['nullable', 'date'],
            'rent_amount' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'terms_en' => ['nullable', 'string', 'max:50000'],
            'terms_ar' => ['nullable', 'string', 'max:50000'],
            'notes' => ['nullable', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);

        $asset = Asset::query()->findOrFail($data['asset_id']);
        abort_if($asset->portfolio_id !== $portfolioId, 422, trans('app.errors.lease_asset_portfolio_mismatch'));
        abort_unless(
            TenantProfile::query()
                ->whereKey($data['tenant_profile_id'])
                ->where('portfolio_id', $portfolioId)
                ->exists(),
            422,
            trans('app.errors.tenant_portfolio_mismatch')
        );
        $lease = $this->leaseLifecycle->create($asset, [
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
            'terms_json' => [
                'en' => $data['terms_en'] ?? null,
                'ar' => $data['terms_ar'] ?? null,
            ],
            'notes' => $data['notes'] ?? null,
        ]);

        return to_route('leases.show', $lease)->with('success', trans('app.messages.lease_created', ['code' => $lease->code]));
    }

    public function update(Request $request, Lease $lease): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'expired', 'terminated'])],
            'signed_at' => ['nullable', 'date'],
            'terms_en' => ['nullable', 'string', 'max:50000'],
            'terms_ar' => ['nullable', 'string', 'max:50000'],
            'notes' => ['nullable', 'string'],
            'resync_installments' => ['nullable', 'boolean'],
        ]);

        $this->leaseLifecycle->update($lease, [
            'status' => $data['status'],
            'signed_at' => $data['signed_at'] ?? null,
            'terms_json' => [
                'en' => $data['terms_en'] ?? null,
                'ar' => $data['terms_ar'] ?? null,
            ],
            'notes' => $data['notes'] ?? null,
        ], (bool) ($data['resync_installments'] ?? false));

        return to_route('leases.show', $lease)->with('success', trans('app.messages.lease_updated', ['code' => $lease->code]));
    }

    public function destroy(Request $request, Lease $lease): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        $this->leaseLifecycle->update($lease, ['status' => 'terminated']);

        return to_route('leases.index')->with('success', trans('app.messages.lease_terminated', ['code' => $lease->code]));
    }

    public function uploadSignedContract(Request $request, Lease $lease): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

        $data = $request->validate([
            'signed_contract' => ['required', 'file', 'extensions:pdf', 'mimes:pdf', 'mimetypes:application/pdf', 'max:10240'],
        ]);

        $file = $data['signed_contract'];
        $path = $file->store("documents/leases/{$lease->id}", 'local');

        Document::query()->create([
            'portfolio_id' => $lease->portfolio_id,
            'uploaded_by_user_id' => $actor->id,
            'documentable_type' => $lease->getMorphClass(),
            'documentable_id' => $lease->id,
            'type' => 'signed_contract',
            'title_en' => "Signed contract {$lease->code}",
            'title_ar' => "العقد الموقع {$lease->code}",
            'disk' => 'local',
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
            'file_size' => $file->getSize(),
            'is_public' => false,
        ]);

        return to_route('leases.show', $lease)->with('success', trans('app.messages.signed_contract_uploaded'));
    }

    public function contract(Request $request, Lease $lease): StreamedResponse
    {
        $actor = $this->actor($request);
        $this->ensureLeaseAccess($actor, $lease);
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'portfolio.owner', 'managedBy');

        $pdf = $this->pdf->loadView('pdf.lease-contract', ['lease' => $lease])->setPaper('a4');
        $content = $pdf->output();
        $fileName = "lease-contract-{$lease->code}.pdf";
        $path = "generated/leases/{$lease->id}/{$fileName}";

        Storage::disk('local')->put($path, $content);

        Document::query()->updateOrCreate(
            [
                'documentable_type' => $lease->getMorphClass(),
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

        return response()->streamDownload(fn () => print ($content), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function statement(Request $request, Lease $lease): StreamedResponse
    {
        $actor = $this->actor($request);
        $this->ensureLeaseAccess($actor, $lease);
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'payments', 'portfolio.owner');

        $pdf = $this->pdf->loadView('pdf.tenant-statement', ['lease' => $lease])->setPaper('a4');
        $content = $pdf->output();
        $fileName = "tenant-statement-{$lease->code}.pdf";
        $path = "generated/leases/{$lease->id}/{$fileName}";

        Storage::disk('local')->put($path, $content);

        Document::query()->updateOrCreate(
            [
                'documentable_type' => $lease->getMorphClass(),
                'documentable_id' => $lease->id,
                'type' => 'tenant_statement',
            ],
            [
                'portfolio_id' => $lease->portfolio_id,
                'uploaded_by_user_id' => $actor->id,
                'title_en' => "Tenant statement {$lease->code}",
                'title_ar' => "كشف حساب المستأجر {$lease->code}",
                'disk' => 'local',
                'file_path' => $path,
                'original_name' => $fileName,
                'mime_type' => 'application/pdf',
                'file_size' => strlen($content),
                'is_public' => false,
            ],
        );

        return response()->streamDownload(fn () => print ($content), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function leaseFormPage(User $actor, ?Lease $lease = null): array
    {
        if ($lease) {
            return [
                'title' => trans('app.actions.edit').' '.$lease->code,
                'description' => 'Update the lease state, signature date, and notes. Financial edits stay locked once payments exist.',
                'backHref' => route('leases.show', $lease),
                'backLabel' => 'Lease detail',
                'action' => route('leases.update', $lease),
                'method' => 'put',
                'submitLabel' => 'Update lease',
                'fields' => [
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['draft', 'active', 'expired', 'terminated'])],
                    ['name' => 'signed_at', 'label' => 'Signed at', 'type' => 'date'],
                    ['name' => 'terms_en', 'label' => 'Approved terms in English', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
                    ['name' => 'terms_ar', 'label' => 'Approved terms in Arabic', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
                    ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
                    ['name' => 'resync_installments', 'label' => 'Regenerate installments', 'type' => 'checkbox', 'help' => 'Only works when no payments exist.'],
                ],
                'initialValues' => [
                    'status' => $lease->status,
                    'signed_at' => $lease->signed_at?->toDateString() ?? '',
                    'terms_en' => data_get($lease->terms_json, 'en', ''),
                    'terms_ar' => data_get($lease->terms_json, 'ar', ''),
                    'notes' => $lease->notes ?? '',
                    'resync_installments' => false,
                ],
            ];
        }

        $tenantOptions = $this->scopeByPortfolio(TenantProfile::query()->with('user')->whereNotNull('user_id')->orderBy('id'), $actor)
            ->get()
            ->map(fn (TenantProfile $tenant) => ['value' => $tenant->id, 'label' => ($tenant->user?->name ?? 'Tenant #'.$tenant->id)])
            ->all();
        $assetOptions = $this->scopeByPortfolio(Asset::query()->where('rentable', true)->orderBy('title_en'), $actor)
            ->get()
            ->map(fn (Asset $asset) => [
                'value' => $asset->id,
                'label' => $this->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
            ])
            ->all();
        $fields = [];

        if ($actor->hasRole('superadmin')) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'tenant_profile_id', 'label' => 'Tenant', 'type' => 'select', 'required' => true, 'options' => $tenantOptions],
            ['name' => 'asset_id', 'label' => 'Rentable asset', 'type' => 'select', 'required' => true, 'options' => $assetOptions],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['draft', 'active', 'expired', 'terminated'])],
            ['name' => 'payment_frequency', 'label' => 'Payment frequency', 'type' => 'select', 'options' => $this->fieldOptions(['monthly', 'quarterly', 'yearly'])],
            ['name' => 'started_at', 'label' => 'Start date', 'type' => 'date', 'required' => true],
            ['name' => 'ends_at', 'label' => 'End date', 'type' => 'date', 'required' => true],
            ['name' => 'signed_at', 'label' => 'Signed date', 'type' => 'date'],
            ['name' => 'rent_amount', 'label' => 'Rent amount', 'type' => 'number', 'min' => 0, 'required' => true],
            ['name' => 'deposit_amount', 'label' => 'Deposit', 'type' => 'number', 'min' => 0],
            ['name' => 'tax_amount', 'label' => 'Tax', 'type' => 'number', 'min' => 0],
            ['name' => 'discount_amount', 'label' => 'Discount', 'type' => 'number', 'min' => 0],
            ['name' => 'currency', 'label' => 'Currency'],
            ['name' => 'billing_day', 'label' => 'Billing day', 'type' => 'number', 'min' => 1, 'max' => 31],
            ['name' => 'terms_en', 'label' => 'Approved terms in English', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
            ['name' => 'terms_ar', 'label' => 'Approved terms in Arabic', 'type' => 'textarea', 'help' => 'Use only portfolio-approved legal wording.'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];

        $fields = $this->sectionFields($fields, [
            'Contract scope' => [
                'description' => 'Select the tenant and rentable asset before setting dates or money.',
                'fields' => ['portfolio_id', 'tenant_profile_id', 'asset_id', 'status'],
            ],
            'Contract period' => [
                'description' => 'Set the signed period and the installment frequency.',
                'fields' => ['payment_frequency', 'started_at', 'ends_at', 'signed_at'],
            ],
            'Rent schedule' => [
                'description' => 'These values generate the installment schedule after the lease is saved.',
                'fields' => ['rent_amount', 'deposit_amount', 'tax_amount', 'discount_amount', 'currency', 'billing_day'],
            ],
            'Internal notes' => [
                'description' => 'Keep operational context here; signed legal terms belong in the contract PDF.',
                'fields' => ['notes'],
            ],
            'Approved legal wording' => [
                'description' => 'Paste the bilingual clauses approved for this portfolio. The system does not invent legal terms.',
                'fields' => ['terms_en', 'terms_ar'],
            ],
        ]);

        return [
            'title' => 'Create lease',
            'description' => 'Attach one tenant to one rentable asset and generate the installment schedule.',
            'backHref' => route('leases.index'),
            'backLabel' => 'All leases',
            'action' => route('leases.store'),
            'method' => 'post',
            'submitLabel' => 'Create lease',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) request('portfolio_id', $actor->portfolio_id ?? $this->portfolioOptions($actor)[0]['id'] ?? ''),
                'tenant_profile_id' => (string) request('tenant_profile_id', $tenantOptions[0]['value'] ?? ''),
                'asset_id' => (string) request('asset_id', $assetOptions[0]['value'] ?? ''),
                'status' => 'active',
                'payment_frequency' => 'monthly',
                'started_at' => now()->toDateString(),
                'ends_at' => now()->addYear()->toDateString(),
                'signed_at' => '',
                'rent_amount' => 0,
                'deposit_amount' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'currency' => 'SAR',
                'billing_day' => 1,
                'terms_en' => '',
                'terms_ar' => '',
                'notes' => '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function leaseTableRow(Lease $lease): array
    {
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'documents');

        $installments = $lease->installments;
        $nextInstallment = $installments
            ->filter(fn ($installment) => $installment->remaining_amount > 0)
            ->sortBy('due_date')
            ->first();
        $overdueCount = $installments
            ->filter(fn ($installment) => $installment->remaining_amount > 0 && ($installment->due_date?->lessThan(today()) ?? false))
            ->count();

        return [
            'id' => $lease->id,
            'portfolio_id' => $lease->portfolio_id,
            'tenant_profile_id' => $lease->tenant_profile_id,
            'leaseable_id' => $lease->leaseable_id,
            'code' => $lease->code,
            'status' => $lease->status,
            'payment_frequency' => $lease->payment_frequency,
            'started_at' => $lease->started_at?->toDateString(),
            'ends_at' => $lease->ends_at?->toDateString(),
            'signed_at' => $lease->signed_at?->toDateString(),
            'rent_amount' => (float) $lease->rent_amount,
            'deposit_amount' => (float) $lease->deposit_amount,
            'tax_amount' => (float) $lease->tax_amount,
            'discount_amount' => (float) $lease->discount_amount,
            'currency' => $lease->currency,
            'billing_day' => $lease->billing_day,
            'notes' => $lease->notes,
            'tenant_profile' => [
                'id' => $lease->tenantProfile?->id,
                'user' => [
                    'name' => $lease->tenantProfile?->user?->name,
                    'email' => $lease->tenantProfile?->user?->email,
                ],
            ],
            'leaseable' => [
                'id' => $lease->leaseable?->getKey(),
                'title_en' => $lease->leaseable?->title_en,
                'title_ar' => $lease->leaseable?->title_ar,
                'code' => $lease->leaseable?->code,
            ],
            'total_due' => (float) $lease->total_due,
            'total_paid' => (float) $lease->total_paid,
            'balance_remaining' => (float) $lease->balance_remaining,
            'days_remaining' => $lease->days_remaining,
            'installment_count' => $installments->count(),
            'overdue_count' => $overdueCount,
            'next_due_date' => $nextInstallment?->due_date?->toDateString(),
            'next_due_amount' => $nextInstallment ? (float) $nextInstallment->remaining_amount : null,
            'open_installment_count' => $installments
                ->filter(fn ($installment) => $installment->remaining_amount > 0)
                ->count(),
            'paid_percent' => $lease->total_due > 0 ? round(min(100, ($lease->total_paid / $lease->total_due) * 100), 1) : 0,
            'installments' => $installments->map(fn ($installment) => [
                'id' => $installment->id,
                'sequence' => $installment->sequence,
                'line_type' => $installment->line_type,
                'label' => $installment->label,
                'period_start' => $installment->period_start?->toDateString(),
                'period_end' => $installment->period_end?->toDateString(),
                'due_date' => $installment->due_date?->toDateString(),
                'amount_due' => (float) $installment->amount_due,
                'amount_paid' => (float) $installment->amount_paid,
                'remaining_amount' => (float) $installment->remaining_amount,
                'status' => $installment->status,
            ])->values()->all(),
            'documents' => $lease->documents->map(fn (Document $document) => [
                'id' => $document->id,
                'type' => $document->type,
                'title_en' => $document->title_en,
                'title_ar' => $document->title_ar,
                'original_name' => $document->original_name,
                'download_url' => route('documents.download', $document),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function leaseInsights(Builder $baseQuery): array
    {
        $leases = (clone $baseQuery)
            ->with('installments')
            ->get();

        return [
            'total' => $leases->count(),
            'active' => $leases->where('status', 'active')->count(),
            'draft' => $leases->where('status', 'draft')->count(),
            'unsigned' => $leases->whereNull('signed_at')->count(),
            'expiring_soon' => $leases
                ->filter(fn (Lease $lease) => $lease->status === 'active'
                    && $lease->ends_at
                    && $lease->ends_at->betweenIncluded(now()->startOfDay(), now()->addDays(60)->endOfDay()))
                ->count(),
            'overdue' => $leases
                ->filter(fn (Lease $lease) => $lease->installments->contains(
                    fn ($installment) => $installment->remaining_amount > 0 && ($installment->due_date?->lessThan(today()) ?? false)
                ))
                ->count(),
            'total_due' => (float) $leases->sum(fn (Lease $lease) => $lease->total_due),
            'total_paid' => (float) $leases->sum(fn (Lease $lease) => $lease->total_paid),
            'balance_remaining' => (float) $leases->sum(fn (Lease $lease) => $lease->balance_remaining),
        ];
    }

    private function ensureLeaseAccess(User $actor, Lease $lease): void
    {
        if ($actor->hasAnyRole(['superadmin', 'owner', 'property_manager'])) {
            $this->ensurePortfolioAccess($actor, $lease->portfolio_id);

            return;
        }

        abort_unless(
            $actor->hasRole('tenant') && $lease->tenantProfile?->user_id === $actor->id,
            403,
            trans('app.errors.lease_access_denied')
        );
    }
}
