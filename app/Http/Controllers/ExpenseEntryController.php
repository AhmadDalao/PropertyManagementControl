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

        return Inertia::render('admin/expenses/index', [
            'expenses' => $this->scopeByPortfolio(
                ExpenseEntry::query()->with(['asset', 'maintenanceRequest'])->latest('incurred_on'),
                $actor
            )->get(),
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
            'category' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'incurred_on' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string'],
        ]);

        $expense->update($data);

        return to_route('expenses.index')->with('success', 'Expense updated successfully.');
    }
}
