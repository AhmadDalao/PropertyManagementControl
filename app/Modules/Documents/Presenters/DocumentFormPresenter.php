<?php

namespace App\Modules\Documents\Presenters;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Queries\DocumentFormDataQuery;

final class DocumentFormPresenter
{
    public function __construct(
        private readonly DocumentFormDataQuery $forms,
        private readonly DocumentCreateFormPresenter $create,
        private readonly DocumentEditFormPresenter $edit,
    ) {}

    /**
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public function present(User $actor, ?Document $document = null, array $defaults = []): array
    {
        $data = $this->forms->get($actor, $document, $defaults);

        return $document ? $this->edit->present($data) : $this->create->present($data);
    }
}
