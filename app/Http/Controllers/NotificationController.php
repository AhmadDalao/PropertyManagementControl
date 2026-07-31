<?php

namespace App\Http\Controllers;

use App\Modules\Notifications\Actions\MarkNotificationsRead;
use App\Modules\Notifications\Queries\NotificationIndexQuery;
use App\Modules\Notifications\Requests\NotificationIndexRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class NotificationController extends Controller
{
    public function index(
        NotificationIndexRequest $request,
        NotificationIndexQuery $notifications,
    ): Response {
        return Inertia::render(
            'admin/notifications/index',
            $notifications->handle($this->actor($request), $request->filters()),
        );
    }

    public function read(
        Request $request,
        string $notification,
        MarkNotificationsRead $notifications,
    ): RedirectResponse {
        return redirect()->to(
            $notifications->one($this->actor($request), $notification),
        );
    }

    public function readAll(
        Request $request,
        MarkNotificationsRead $notifications,
    ): RedirectResponse {
        $notifications->all($this->actor($request));

        return back()->with('success', trans('app.messages.notifications_marked_read'));
    }
}
