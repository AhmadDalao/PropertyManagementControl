<?php

namespace App\Modules\Reports\Presenters;

use App\Models\ReportPreset;
use App\Models\User;
use App\Modules\Reports\Queries\ReportPresetDetailQuery;
use App\Modules\Shared\ResourcePresenter;

final readonly class ReportPresetDetailPresenter
{
    public function __construct(
        private ReportPresetDetailQuery $query,
        private ReportPresetDetailHeaderPresenter $header,
        private ReportPresetDetailOverviewPresenter $overview,
        private ReportPresetDetailWorkflowPresenter $workflow,
        private ReportPresetDetailNoticePresenter $notices,
        private ReportPresetOutputPresenter $outputs,
        private ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(User $actor, ReportPreset $preset): array
    {
        $data = $this->query->get($actor, $preset);

        return [
            'header' => $this->header->present($data),
            'availableTabs' => ['overview', 'scope', 'outputs', 'access', 'history'],
            'workflow' => $this->workflow->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => $this->overview->sections($data),
            'outputs' => $this->outputs->present($preset, $data->view, $data->dateRange),
            'notices' => $this->notices->present($data),
            'timeline' => $this->resources->activityTimeline($preset),
        ];
    }
}
