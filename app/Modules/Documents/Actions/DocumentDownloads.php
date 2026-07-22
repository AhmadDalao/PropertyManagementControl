<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Documents\Support\DocumentFileStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentDownloads
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly DocumentFileStorage $files,
    ) {}

    public function download(User $actor, Document $document): StreamedResponse
    {
        $this->access->ensureCanDownload($actor, $document);

        return $this->files->download($document);
    }
}
