<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseEntryController extends Controller
{
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
            ], 'incurred_on'),
            'filters' => $filters,
            'counts' => $this->statusCounts($baseQuery, ['posted', 'pending', 'void'], $filters),
            'portfolioOptions' => $this->portfolioOptions($actor),
            'assetOptions' => $this->scopeByPortfolio(Asset::query(), $actor)->get(),
            'maintenanceOptions' => $this->scopeByPortfolio(MaintenanceRequest::query(), $actor)->get(),
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
            'category' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'incurred_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string'],
        ]);

        $portfolioId = $data['portfolio_id'] ?? $actor->portfolio_id;
        $this->ensurePortfolioAccess($actor, $portfolioId);
        $this->ensureExpenseReferencesBelongToPortfolio($data, $portfolioId);

        ExpenseEntry::query()->create([
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

        return to_route('expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function update(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $expense->portfolio_id);

        $data = $request->validate([
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'maintenance_request_id' => ['nullable', 'integer', 'exists:maintenance_requests,id'],
            'category' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'incurred_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string'],
        ]);

        $this->ensureExpenseReferencesBelongToPortfolio($data, $expense->portfolio_id);
        $expense->update($data);

        return to_route('expenses.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        $actor = $this->actor($request);
        $this->requireRoles($actor, ['superadmin', 'owner', 'property_manager']);
        $this->ensurePortfolioAccess($actor, $expense->portfolio_id);

        $expense->update(['status' => 'void']);

        return to_route('expenses.index')->with('success', 'Expense voided successfully.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function ensureExpenseReferencesBelongToPortfolio(array $data, int $portfolioId): void
    {
        if (! empty($data['asset_id'])) {
            abort_unless(
                Asset::query()->whereKey($data['asset_id'])->where('portfolio_id', $portfolioId)->exists(),
                422,
                'Selected asset does not belong to this portfolio.'
            );
        }

        if (! empty($data['maintenance_request_id'])) {
            abort_unless(
                MaintenanceRequest::query()
                    ->whereKey($data['maintenance_request_id'])
                    ->where('portfolio_id', $portfolioId)
                    ->exists(),
                422,
                'Selected maintenance request does not belong to this portfolio.'
            );
        }
    }
}
