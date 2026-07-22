<?php

namespace App\Modules\Cms\Actions;

use App\Models\NavigationItem;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DeleteNavigationItem
{
    public function __construct(private readonly CmsAccess $access) {}

    public function handle(User $actor, NavigationItem $target): void
    {
        $this->access->ensureAdmin($actor);

        DB::transaction(function () use ($target): void {
            $item = NavigationItem::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();

            if ($item->children()->exists()) {
                throw ValidationException::withMessages([
                    'navigation' => trans('app.errors.navigation_has_children'),
                ]);
            }

            $item->delete();
        }, 3);
    }
}
