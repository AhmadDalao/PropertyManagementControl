<?php

namespace App\Modules\Cms\Actions;

use App\Models\NavigationItem;
use App\Models\User;
use App\Modules\Cms\Support\CmsAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageNavigationItems
{
    public function __construct(private readonly CmsAccess $access) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): NavigationItem
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($data): NavigationItem {
            $this->ensureParentIsValid($data);

            return NavigationItem::query()->create($this->payload($data));
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, NavigationItem $target, array $data): NavigationItem
    {
        $this->access->ensureAdmin($actor);

        return DB::transaction(function () use ($target, $data): NavigationItem {
            $item = NavigationItem::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $this->ensureParentIsValid($data, $item);
            $item->update($this->payload($data, $item));

            return $item->refresh();
        });
    }

    public function delete(User $actor, NavigationItem $target): void
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
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data, ?NavigationItem $item = null): array
    {
        return [
            ...$data,
            'is_visible' => (bool) ($data['is_visible'] ?? ($item ? $item->is_visible : true)),
        ];
    }

    /** @param array<string, mixed> $data */
    private function ensureParentIsValid(array $data, ?NavigationItem $item = null): void
    {
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

        if (! $parentId) {
            return;
        }

        if ($item && $parentId === $item->id) {
            throw ValidationException::withMessages([
                'parent_id' => trans('app.errors.navigation_self_parent'),
            ]);
        }

        $parent = NavigationItem::query()->lockForUpdate()->findOrFail($parentId);

        if ($parent->location !== $data['location']) {
            throw ValidationException::withMessages([
                'parent_id' => trans('app.errors.navigation_parent_location'),
            ]);
        }

        $visited = [];

        while ($parent !== null) {
            if (isset($visited[$parent->id]) || ($item && $parent->id === $item->id)) {
                throw ValidationException::withMessages([
                    'parent_id' => trans('app.errors.navigation_child_parent'),
                ]);
            }

            $visited[$parent->id] = true;
            $parent = $parent->parent_id
                ? NavigationItem::query()->lockForUpdate()->find($parent->parent_id)
                : null;
        }
    }
}
