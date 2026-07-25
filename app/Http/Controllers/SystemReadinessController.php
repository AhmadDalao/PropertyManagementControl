<?php

namespace App\Http\Controllers;

use App\Modules\SystemReadiness\Actions\SendReadinessTestEmail;
use App\Modules\SystemReadiness\Actions\UpdateReadinessConfirmation;
use App\Modules\SystemReadiness\Presenters\ReadinessPagePresenter;
use App\Modules\SystemReadiness\Requests\ReadinessIndexRequest;
use App\Modules\SystemReadiness\Requests\SendReadinessTestEmailRequest;
use App\Modules\SystemReadiness\Requests\UpdateReadinessCheckRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SystemReadinessController extends Controller
{
    public function __construct(
        private readonly ReadinessPagePresenter $page,
        private readonly UpdateReadinessConfirmation $confirmations,
        private readonly SendReadinessTestEmail $testEmail,
    ) {}

    public function index(ReadinessIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/system-readiness/index',
            $this->page->present($this->actor($request), $request->portfolioId()),
        );
    }

    public function update(UpdateReadinessCheckRequest $request): RedirectResponse
    {
        $payload = $request->payload();
        $this->confirmations->handle(
            $this->actor($request),
            $payload['key'],
            $payload['confirmed'],
            $payload['evidence'],
            $payload['portfolio_id'],
        );

        return back()->with('success', trans('app.readiness.check_updated'));
    }

    public function testEmail(SendReadinessTestEmailRequest $request): RedirectResponse
    {
        try {
            $this->testEmail->handle($this->actor($request));

            return back()->with('success', trans('app.readiness.test_email_sent'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', trans('app.readiness.test_email_failed'));
        }
    }
}
