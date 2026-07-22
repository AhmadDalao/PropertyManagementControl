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
        private readonly DocumentDetailOverviewPresenter $overview,
        private readonly ResourcePresenter $resources,
    ) {}

    /** @return array<string, mixed> */
    public function present(Document $document, User $actor): array
    {
        $data = $this->details->get($document, $actor);
        $overview = $this->overview->present($data);

        return [
            'header' => $this->header->present($data),
            'stats' => $overview['stats'],
            'sections' => $overview['sections'],
            'related' => [],
            'documents' => $this->resources->documentStrip([$data->document]),
            'timeline' => $this->resources->activityTimeline($data->document),
        ];
    }
}
