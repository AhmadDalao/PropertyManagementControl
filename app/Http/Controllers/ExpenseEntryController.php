<?php

namespace App\Http\Controllers;

use App\Models\ExpenseEntry;
use App\Modules\Expenses\Actions\ManageExpenses;
use App\Modules\Expenses\Presenters\ExpenseDetailPresenter;
use App\Modules\Expenses\Presenters\ExpenseFormPresenter;
use App\Modules\Expenses\Queries\ExpenseIndexQuery;
use App\Modules\Expenses\Requests\StoreExpenseRequest;
use App\Modules\Expenses\Requests\UpdateExpenseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseEntryController extends Controller
{
    public function __construct(
        private readonly ExpenseIndexQuery $indexQuery,
        private readonly ExpenseFormPresenter $formPresenter,
        private readonly ExpenseDetailPresenter $detailPresenter,
        private readonly ManageExpenses $expenses,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/expenses/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present(
                $this->actor($request),
                defaults: $request->only('portfolio_id', 'asset_id', 'maintenance_request_id'),
            ),
        ]);
    }

    public function show(Request $request, ExpenseEntry $expense): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($expense, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, ExpenseEntry $expense): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $expense),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenses->create($this->actor($request), $request->validated());

        return to_route('expenses.show', $expense)
            ->with('success', trans('app.messages.expense_created'));
    }

    public function update(UpdateExpenseRequest $request, ExpenseEntry $expense): RedirectResponse
    {
        $this->expenses->update($this->actor($request), $expense, $request->validated());

        return to_route('expenses.show', $expense)
            ->with('success', trans('app.messages.expense_updated'));
    }

    public function destroy(Request $request, ExpenseEntry $expense): RedirectResponse
    {
        $this->expenses->void($this->actor($request), $expense);

        return to_route('expenses.show', $expense)
            ->with('success', trans('app.messages.expense_voided'));
    }
}
