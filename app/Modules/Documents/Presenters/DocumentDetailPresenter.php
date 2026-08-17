<?php

namespace App\Modules\Documents\Presenters;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Queries\DocumentDetailQuery;
use App\Modules\Shared\ResourcePresenter;

final class DocumentDetailPresenter
{
    public function __construct(
        private readonly DocumentDetailQuery $details,
        private readonly DocumentDetailHeaderPresenter $header,
        private readonly DocumentWorkflowPresenter $workflow,
        private readonly DocumentDetailOverviewPresenter $overview,
        private readonly DocumentAccessPresenter $access,
        private readonly DocumentValidityPresenter $validity,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Document $document, User $actor): array
    {
        $data = $this->details->get($document, $actor);
        $validity = $this->validity->present($data);

        return [
            'header' => $this->header->present($data),
            'availableTabs' => ['overview', 'access', 'validity', 'history'],
            'workflow' => $this->workflow->present($data),
            'stats' => $this->overview->stats($data),
            'sections' => [
                ...$this->overview->sections($data),
                $this->access->section($data),
                $validity['section'],
            ],
            'replacement' => $validity['replacement'],
            'timeline' => $this->resources->activityTimeline($data->document),
        ];
    }
}
