<?php

namespace App\Modules\Dashboard\Presenters;

final class ManagerAssignmentActionPresenter
{
    /** @return array<int, array{label:string, description:string, href:string, icon:string}> */
    public function present(): array
    {
        return [
            $this->action(
                trans('app.dashboard.property_assignment_action'),
                trans('app.dashboard.property_assignment_action_help'),
                '/portfolios',
                'bi-building',
            ),
            $this->action(
                trans('app.dashboard.open_operating_manual'),
                trans('app.dashboard.open_operating_manual_help'),
                '/documentation',
                'bi-journal-richtext',
            ),
        ];
    }

    /** @return array{label:string, description:string, href:string, icon:string} */
    private function action(string $label, string $description, string $href, string $icon): array
    {
        return compact('label', 'description', 'href', 'icon');
    }
}
