<?php

namespace App\Modules\Wording\Presenters;

use App\Modules\Wording\Queries\WordingIndexQuery;
use App\Modules\Wording\Requests\WordingIndexRequest;
use App\Modules\Wording\TranslationCompletenessService;

class WordingPagePresenter
{
    public function __construct(
        private readonly WordingIndexQuery $index,
        private readonly TranslationCompletenessService $completeness,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(WordingIndexRequest $request): array
    {
        $filters = $request->filters();

        return [
            ...$this->index->present($filters),
            'contentTranslations' => $this->completeness->summary(
                $filters['content_module'],
            ),
        ];
    }
}
