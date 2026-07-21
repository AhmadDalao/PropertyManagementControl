<?php

namespace App\Modules\Users\Actions;

use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Users\Queries\UserIndexQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly UserIndexQuery $users,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('users', [
            trans('app.users.name'),
            trans('app.users.email'),
            trans('app.users.phone'),
            trans('app.users.role'),
            trans('app.users.status'),
            trans('app.users.portfolio'),
        ], $this->users->forExport($request, $actor), fn (User $user): array => [
            $user->name,
            $user->email,
            $user->phone,
            $user->roles
                ->pluck('name')
                ->map(fn (string $role): string => $this->workbook->option($role))
                ->join(', '),
            $this->workbook->option($user->status),
            $this->workbook->localized($user->portfolio, 'name_en', 'name_ar'),
        ]);
    }
}
