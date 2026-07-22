<?php

namespace App\Modules\Cms\Presenters;

use App\Models\NavigationItem;
use App\Models\User;
use App\Modules\Cms\Queries\NavigationFormOptionsQuery;
use App\Modules\Cms\Support\CmsAccess;

final class NavigationFormPresenter
{
    public function __construct(
        private readonly CmsAccess $access,
        private readonly NavigationFormOptionsQuery $options,
        private readonly NavigationFormFieldsPresenter $fields,
        private readonly NavigationFormValuesPresenter $values,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ?NavigationItem $item = null): array
    {
        $this->access->ensureAdmin($actor);

        return [
            'title' => $item ? trans('app.cms.edit_navigation') : trans('app.cms.create_navigation'),
            'description' => trans('app.cms.navigation_form_description'),
            'backHref' => route('cms.index', ['view' => 'navigation']),
            'backLabel' => trans('app.cms.website_control'),
            'action' => $item
                ? route('navigation-items.update', $item)
                : route('navigation-items.store'),
            'method' => $item ? 'put' : 'post',
            'submitLabel' => $item ? trans('app.cms.update_navigation') : trans('app.cms.create_navigation'),
            'fields' => $this->fields->present($this->options->handle($item)),
            'initialValues' => $this->values->present($item),
        ];
    }
}
