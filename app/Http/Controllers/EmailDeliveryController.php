<?php

namespace App\Http\Controllers;

use App\Models\EmailDeliveryLog;
use App\Modules\EmailDelivery\Actions\EmailDeliveryWorkbookExport;
use App\Modules\EmailDelivery\Presenters\EmailDeliveryLogPresenter;
use App\Modules\EmailDelivery\Queries\EmailDeliveryIndexQuery;
use App\Modules\EmailDelivery\Requests\EmailDeliveryIndexRequest;
use App\Modules\EmailDelivery\Support\EmailDeliveryAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmailDeliveryController extends Controller
{
    public function __construct(
        private readonly EmailDeliveryIndexQuery $deliveries,
        private readonly EmailDeliveryLogPresenter $presenter,
        private readonly EmailDeliveryAccess $access,
        private readonly EmailDeliveryWorkbookExport $workbook,
    ) {}

    public function index(EmailDeliveryIndexRequest $request): Response
    {
        return Inertia::render(
            'admin/email-delivery/index',
            $this->deliveries->handle($request, $this->actor($request)),
        );
    }

    public function show(Request $request, EmailDeliveryLog $emailDeliveryLog): Response
    {
        $this->access->ensureSuperadmin($this->actor($request));

        return Inertia::render(
            'admin/email-delivery/show',
            $this->presenter->detail(
                $emailDeliveryLog->loadMissing(['portfolio', 'user']),
            ),
        );
    }

    public function export(EmailDeliveryIndexRequest $request): BinaryFileResponse
    {
        return $this->workbook->download(
            $this->actor($request),
            $request->filters(),
        );
    }
}
