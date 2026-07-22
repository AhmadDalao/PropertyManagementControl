<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;

final class ManageDocuments
{
    public function __construct(
        private readonly CreateDocument $create,
        private readonly UpdateDocument $update,
        private readonly DeleteDocument $delete,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Document
    {
        return $this->create->handle($actor, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, Document $document, array $data): Document
    {
        return $this->update->handle($actor, $document, $data);
    }

    public function delete(User $actor, Document $document): void
    {
        $this->delete->handle($actor, $document);
    }
}
