<?php

namespace App\Http\Controllers;

use App\Modules\InfrastructureSettings\Actions\ApplyInfrastructureSettings;
use App\Modules\InfrastructureSettings\Actions\UpdateInfrastructureSettings;
use App\Modules\InfrastructureSettings\Presenters\InfrastructureSettingsPagePresenter;
use App\Modules\InfrastructureSettings\Requests\UpdateInfrastructureSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class InfrastructureSettingsController extends Controller
{
    public function __construct(
        private readonly InfrastructureSettingsPagePresenter $page,
        private readonly UpdateInfrastructureSettings $update,
        private readonly ApplyInfrastructureSettings $apply,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render(
            'admin/infrastructure-settings/index',
            $this->page->present($this->actor($request)),
        );
    }

    public function update(UpdateInfrastructureSettingsRequest $request): RedirectResponse
    {
        $this->update->handle($this->actor($request), $request->payload());
        $this->apply->handle();

        return back()->with('success', trans('app.infrastructure_settings.saved'));
    }
}
