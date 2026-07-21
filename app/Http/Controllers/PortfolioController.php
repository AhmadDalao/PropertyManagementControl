<?php

namespace App\Http\Controllers;

use App\Models\Portfolio;
use App\Modules\Portfolios\Actions\ManagePortfolios;
use App\Modules\Portfolios\Presenters\PortfolioDetailPresenter;
use App\Modules\Portfolios\Presenters\PortfolioFormPresenter;
use App\Modules\Portfolios\Queries\PortfolioIndexQuery;
use App\Modules\Portfolios\Requests\StorePortfolioRequest;
use App\Modules\Portfolios\Requests\UpdatePortfolioRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioIndexQuery $indexQuery,
        private readonly PortfolioFormPresenter $formPresenter,
        private readonly PortfolioDetailPresenter $detailPresenter,
        private readonly ManagePortfolios $portfolios,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/portfolios/index',
            $this->indexQuery->handle($request, $this->actor($request)),
        );
    }

    public function create(Request $request): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request)),
        ]);
    }

    public function show(Request $request, Portfolio $portfolio): Response
    {
        return Inertia::render('admin/resource-show', [
            'detailPage' => $this->detailPresenter->present($portfolio, $this->actor($request)),
        ]);
    }

    public function edit(Request $request, Portfolio $portfolio): Response
    {
        return Inertia::render('admin/resource-form', [
            'formPage' => $this->formPresenter->present($this->actor($request), $portfolio),
        ]);
    }

    public function store(StorePortfolioRequest $request): RedirectResponse
    {
        $portfolio = $this->portfolios->create($this->actor($request), $request->validated());

        return to_route('portfolios.show', $portfolio)->with('success', trans('app.messages.record_created', [
            'resource' => trans('app.nav.portfolios'),
            'name' => app()->isLocale('ar') ? $portfolio->name_ar : $portfolio->name_en,
        ]));
    }

    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): RedirectResponse
    {
        $portfolio = $this->portfolios->update(
            $this->actor($request),
            $portfolio,
            $request->validated(),
        );

        return to_route('portfolios.show', $portfolio)->with('success', trans('app.messages.record_updated', [
            'resource' => trans('app.nav.portfolios'),
            'name' => app()->isLocale('ar') ? $portfolio->name_ar : $portfolio->name_en,
        ]));
    }

    public function destroy(Request $request, Portfolio $portfolio): RedirectResponse
    {
        $blockingReason = $this->portfolios->archive($this->actor($request), $portfolio);

        if ($blockingReason !== null) {
            return back()->with('error', $blockingReason);
        }

        return to_route('portfolios.index')->with('success', trans('app.messages.portfolio_archived', [
            'name' => app()->isLocale('ar') ? $portfolio->name_ar : $portfolio->name_en,
        ]));
    }
}
