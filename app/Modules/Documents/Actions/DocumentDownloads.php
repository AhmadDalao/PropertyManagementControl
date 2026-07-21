<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloads
{
    public function __construct(private readonly DocumentAccess $access) {}

    public function download(User $actor, Document $document): StreamedResponse
    {
        $this->access->ensureCanDownload($actor, $document);
        abort_unless(
            Storage::disk($document->disk)->exists($document->file_path),
            404,
            trans('app.errors.document_file_missing'),
        );

        $fileName = trim(str_replace(
            ["\r", "\n"],
            '',
            basename(str_replace('\\', '/', $document->original_name)),
        ));

        return Storage::disk($document->disk)->download(
            $document->file_path,
            $fileName !== '' ? $fileName : "document-{$document->id}.pdf",
            ['Content-Type' => 'application/pdf'],
        );
    }
}
