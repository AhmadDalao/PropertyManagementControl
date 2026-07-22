<?php

namespace App\Modules\Cms\Support;

use App\Models\NavigationItem;
use Illuminate\Validation\ValidationException;

final class NavigationParentPolicy
{
    /** @param array<string, mixed> $data */
    public function ensure(array $data, ?NavigationItem $item = null): void
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
