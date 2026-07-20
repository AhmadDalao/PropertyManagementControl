<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseEntryController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $categories = ['maintenance', 'utilities', 'supplies', 'repairs', 'insurance', 'taxes', 'administration'];

    public function index(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $filters = $this->tableFilters($request, [
            'status' => 'all',
            'category' => 'all',
            'date_from' => '',
            'date_to' => '',
        ]);
        $baseQuery = $this->scopeByPortfolio(ExpenseEntry::query(), $actor);
        $expenses = (clone $baseQuery)->with(['asset', 'maintenanceRequest']);

        $this->applyExactFilter($expenses, $filters, 'portfolio_id');
        $this->applyExactFilter($expenses, $filters, 'status');
        $this->applyExactFilter($expenses, $filters, 'category');
        $this->applyDateRange($expenses, $filters, 'incurred_on');
        $this->applySearch($expenses, $filters['search'], [
            'title',
            'description',
            'category',
            'vendor_name',
            fn ($query, $search, $like) => $query->orWhereHas(
                'asset',
                fn ($assetQuery) => $assetQuery->where('title_en', 'like', $like)->orWhere('code', 'like', $like)
            ),
            fn ($query, $search, $like) => $query->orWhereHas(
                'maintenanceRequest',
                fn ($maintenanceQuery) => $maintenanceQuery->where('title', 'like', $like)
            ),
        ]);

        return Inertia::render('admin/expenses/index', [
            'expenses' => $this->paginateTable($expenses, $request, $filters, [
                'created_at',
                'incurred_on',
                'title',
                'category',
                'status',
                'amount',
            ], 'incurred_on')->through(fn (ExpenseEntry $expense) => $this->expenseTableRow($expense)),
            'expenseInsights' => $this->expenseInsights($baseQuery),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['posted', 'pending', 'void'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'categoryOptions' => $this->categories,
            'assetOptions' => $this->scopeByPortfolio(Asset::query(), $actor)
                ->orderBy('title_en')
                ->get(['id', 'title_en', 'title_ar', 'code', 'portfolio_id']),
            'maintenanceOptions' => $this->scopeByPortfolio(MaintenanceRequest::query(), $actor)
                ->with('asset')
                ->latest()
                ->get()
                ->map(fn (MaintenanceRequest $maintenanceRequest) => [
                    'id' => $maintenanceRequest->id,
                    'asset_id' => $maintenanceRequest->asset_id,
                    'title' => $maintenanceRequest->title,
                    'status' => $maintenanceRequest->status,
                    'priority' => $maintenanceRequest->priority,
                    'asset' => [
                        'title_en' => $maintenanceRequest->asset?->title_en,
                        'title_ar' => $maintenanceRequest->asset?->title_ar,
                        'code' => $maintenanceRequest->asset?->code,
                    ],
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->expenseFormPage($actor),
        ]);
    }

    public function show(Request $request, ExpenseEntry $expense): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $expense->portfolio_id);
        $expense->loadMissing(['portfolio', 'asset', 'lease', 'maintenanceRequest', 'createdBy']);

        return Inertia::render('admin/resource-show', [
            'detailPage' => [
                'header' => [
                    'eyebrow' => 'Expense detail',
                    'title' => $expense->title,
                    'description' => trim($expense->category.' · '.$expense->status.' · '.$expense->vendor_name),
                    'backHref' => route('expenses.index'),
                    'backLabel' => 'All expenses',
                    'actions' => [
                        ['label' => 'Edit expense', 'href' => route('expenses.edit', $expense), 'variant' => 'primary'],
                    ],
                ],
                'stats' => $this->detailItems([
                    ['label' => 'Amount', 'value' => number_format((float) $expense->amount, 2).' '.$expense->currency, 'tone' => 'primary'],
                    ['label' => 'Status', 'value' => $expense->status, 'tone' => $expense->status === 'void' ? 'danger' : 'teal'],
                    ['label' => 'Category', 'value' => $expense->category],
                    ['label' => 'Date', 'value' => $expense->incurred_on?->toDateString()],
                ]),
                'sections' => [
                    [
                        'title' => 'Expense record',
                        'description' => 'Cost context for revenue and net reporting.',
                        'items' => $this->detailItems([
                            ['label' => 'Asset', 'value' => $this->localized($expense->asset?->title_en, $expense->asset?->title_ar), 'href' => $expense->asset ? route('assets.show', $expense->asset) : null],
                            ['label' => 'Maintenance', 'value' => $expense->maintenanceRequest?->title, 'href' => $expense->maintenanceRequest ? route('maintenance-requests.show', $expense->maintenanceRequest) : null],
                            ['label' => 'Lease', 'value' => $expense->lease?->code, 'href' => $expense->lease ? route('leases.show', $expense->lease) : null],
                            ['label' => 'Portfolio', 'value' => $this->localized($expense->portfolio?->name_en, $expense->portfolio?->name_ar), 'href' => $expense->portfolio ? route('portfolios.show', $expense->portfolio) : null],
                            ['label' => 'Created by', 'value' => $expense->createdBy?->name, 'href' => $expense->createdBy ? route('users.show', $expense->createdBy) : null],
                            ['label' => 'Vendor', 'value' => $expense->vendor_name],
                            ['label' => 'Description', 'value' => $expense->description],
                        ]),
                    ],
                ],
                'related' => [],
                'documents' => [],
                'timeline' => $this->activityTimeline($expense),
            ],
        ]);
    }

    public function edit(Request $request, ExpenseEntry $expense): Response
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $expense->portfolio_id);

        return Inertia::render('admin/resource-form', [
            'formPage' => $this->expenseFormPage($actor, $expense),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);

        $data = $request->validate([
            'portfolio_id' => ['nullable', 'integer', 'exists:portfolios,id'],
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'maintenance_request_id' => ['nullable', 'integer', 'exists:maintenance_requests,id'],
            'category' => ['required', Rule::in($this->categories)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'incurred_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['posted', 'pending', 'void'])],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        $data = $this->normalizeExpenseReferences($data, $portfolioId);

        $expense = ExpenseEntry::query()->create([
            'portfolio_id' => $portfolioId,
            'asset_id' => $data['asset_id'] ?? null,
            'maintenance_request_id' => $data['maintenance_request_id'] ?? null,
            'created_by_user_id' => $actor->id,
            'category' => $data['category'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'incurred_on' => $data['incurred_on'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'SAR',
            'vendor_name' => $data['vendor_name'] ?? null,
            'status' => $data['status'],
        ]);

        return to_route('expenses.show', $expense)->with('success', trans('app.messages.expense_created'));
    }

    public function update(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $expense->portfolio_id);

        $data = $request->validate([
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'maintenance_request_id' => ['nullable', 'integer', 'exists:maintenance_requests,id'],
            'category' => ['required', Rule::in($this->categories)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'incurred_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['posted', 'pending', 'void'])],
        ]);

        $data = $this->normalizeExpenseReferences($data, $expense->portfolio_id);
        $expense->update($data);

        return to_route('expenses.show', $expense)->with('success', trans('app.messages.expense_updated'));
    }

    public function destroy(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $expense->portfolio_id);

        $expense->update(['status' => 'void']);

        return to_route('expenses.index')->with('success', trans('app.messages.expense_voided'));
    }

    private function expenseFormPage(User $actor, ?ExpenseEntry $expense = null): array
    {
        $assetOptions = $this->scopeByPortfolio(Asset::query()->orderBy('title_en'), $actor)
            ->get()
            ->map(fn (Asset $asset) => [
                'value' => $asset->id,
                'label' => $this->localized($asset->title_en, $asset->title_ar).' · '.$asset->code,
            ])
            ->prepend(['value' => '', 'label' => 'No asset'])
            ->values()
            ->all();
        $maintenanceOptions = $this->scopeByPortfolio(MaintenanceRequest::query()->with('asset')->latest(), $actor)
            ->get()
            ->map(fn (MaintenanceRequest $maintenanceRequest) => [
                'value' => $maintenanceRequest->id,
                'label' => '#'.$maintenanceRequest->id.' · '.$maintenanceRequest->title.' · '.($this->localized($maintenanceRequest->asset?->title_en, $maintenanceRequest->asset?->title_ar) ?? 'asset'),
            ])
            ->prepend(['value' => '', 'label' => 'No maintenance request'])
            ->values()
            ->all();
        $fields = [];

        if ($actor->hasRole('superadmin') && $expense === null) {
            $fields[] = [
                'name' => 'portfolio_id',
                'label' => 'Portfolio',
                'type' => 'select',
                'options' => collect($this->portfolioOptions($actor))->map(fn ($portfolio) => ['value' => $portfolio['id'], 'label' => $portfolio['name']])->all(),
            ];
        }

        $fields = [
            ...$fields,
            ['name' => 'asset_id', 'label' => 'Asset', 'type' => 'select', 'options' => $assetOptions],
            ['name' => 'maintenance_request_id', 'label' => 'Maintenance request', 'type' => 'select', 'options' => $maintenanceOptions],
            ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $this->fieldOptions($this->categories)],
            ['name' => 'title', 'label' => 'Title', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'incurred_on', 'label' => 'Incurred on', 'type' => 'date', 'required' => true],
            ['name' => 'amount', 'label' => 'Amount', 'type' => 'number', 'min' => 0, 'required' => true],
            ['name' => 'currency', 'label' => 'Currency'],
            ['name' => 'vendor_name', 'label' => 'Vendor'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => $this->fieldOptions(['posted', 'pending', 'void'])],
        ];

        return [
            'title' => $expense ? 'Edit expense' : 'Record expense',
            'description' => 'Attach costs to assets or maintenance so reports show net performance.',
            'backHref' => $expense ? route('expenses.show', $expense) : route('expenses.index'),
            'backLabel' => $expense ? 'Expense detail' : 'All expenses',
            'action' => $expense ? route('expenses.update', $expense) : route('expenses.store'),
            'method' => $expense ? 'put' : 'post',
            'submitLabel' => $expense ? 'Update expense' : 'Record expense',
            'fields' => $fields,
            'initialValues' => [
                'portfolio_id' => (string) ($expense?->portfolio_id ?? request('portfolio_id', $actor->portfolio_id ?? $this->portfolioOptions($actor)[0]['id'] ?? '')),
                'asset_id' => (string) request('asset_id', $expense?->asset_id ?? ''),
                'maintenance_request_id' => (string) request('maintenance_request_id', $expense?->maintenance_request_id ?? ''),
                'category' => $expense?->category ?? 'maintenance',
                'title' => $expense?->title ?? '',
                'description' => $expense?->description ?? '',
                'incurred_on' => $expense?->incurred_on?->toDateString() ?? now()->toDateString(),
                'amount' => (float) ($expense?->amount ?? 0),
                'currency' => $expense?->currency ?? 'SAR',
                'vendor_name' => $expense?->vendor_name ?? '',
                'status' => $expense?->status ?? 'posted',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeExpenseReferences(array $data, int $portfolioId): array
    {
        $assetId = $data['asset_id'] ?? null;
        $maintenanceRequestId = $data['maintenance_request_id'] ?? null;

        if (! empty($data['asset_id'])) {
            abort_unless(
                Asset::query()->whereKey($data['asset_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                trans('app.errors.asset_portfolio_mismatch')
            );
        }

        if (! empty($maintenanceRequestId)) {
            $maintenanceRequest = MaintenanceRequest::query()
                ->whereKey($maintenanceRequestId)
                ->where('portfolio_id', $portfolioId)
                ->first();

            abort_unless(
                $maintenanceRequest,
                422,
                trans('app.errors.maintenance_portfolio_mismatch')
            );

            if (empty($assetId) && $maintenanceRequest->asset_id) {
                $data['asset_id'] = $maintenanceRequest->asset_id;
            }

            if (! empty($assetId) && $maintenanceRequest->asset_id && (int) $assetId !== (int) $maintenanceRequest->asset_id) {
                abort(422, trans('app.errors.expense_asset_mismatch'));
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function expenseTableRow(ExpenseEntry $expense): array
    {
        $expense->loadMissing(['asset', 'maintenanceRequest']);

        return [
            'id' => $expense->id,
            'portfolio_id' => $expense->portfolio_id,
            'asset_id' => $expense->asset_id,
            'maintenance_request_id' => $expense->maintenance_request_id,
            'title' => $expense->title,
            'description' => $expense->description,
            'category' => $expense->category,
            'status' => $expense->status,
            'vendor_name' => $expense->vendor_name,
            'amount' => (float) $expense->amount,
            'currency' => $expense->currency,
            'incurred_on' => $expense->incurred_on?->toDateString(),
            'asset' => [
                'id' => $expense->asset?->id,
                'title_en' => $expense->asset?->title_en,
                'title_ar' => $expense->asset?->title_ar,
                'code' => $expense->asset?->code,
            ],
            'maintenance_request' => [
                'id' => $expense->maintenanceRequest?->id,
                'title' => $expense->maintenanceRequest?->title,
                'status' => $expense->maintenanceRequest?->status,
                'priority' => $expense->maintenanceRequest?->priority,
            ],
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function expenseInsights(Builder $baseQuery): array
    {
        $expenses = (clone $baseQuery)->get();
        $posted = $expenses->where('status', 'posted');
        $pending = $expenses->where('status', 'pending');
        $void = $expenses->where('status', 'void');

        return [
            'total' => $expenses->count(),
            'posted_count' => $posted->count(),
            'pending_count' => $pending->count(),
            'void_count' => $void->count(),
            'posted_amount' => (float) $posted->sum('amount'),
            'pending_amount' => (float) $pending->sum('amount'),
            'void_amount' => (float) $void->sum('amount'),
            'maintenance_amount' => (float) $posted->where('category', 'maintenance')->sum('amount'),
            'linked_to_assets' => $expenses->whereNotNull('asset_id')->count(),
            'linked_to_maintenance' => $expenses->whereNotNull('maintenance_request_id')->count(),
            'unlinked_count' => $expenses
                ->filter(fn (ExpenseEntry $expense) => $expense->asset_id === null && $expense->maintenance_request_id === null)
                ->count(),
            'vendors' => $expenses->pluck('vendor_name')->filter()->unique()->count(),
            'posted_this_month' => (float) $posted
                ->filter(fn (ExpenseEntry $expense) => $expense->incurred_on?->isSameMonth(now()) ?? false)
                ->sum('amount'),
        ];
    }
}
