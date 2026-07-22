<?php

namespace App\Modules\Documents\Support;

use App\Models\Document;
use App\Modules\Documents\Data\StoredDocumentFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentFileStorage
{
    public function store(UploadedFile $file, int $portfolioId): StoredDocumentFile
    {
        $path = $file->store("documents/library/{$portfolioId}", 'local');

        if ($path === false) {
            throw new RuntimeException(trans('app.errors.document_store_failed'));
        }

        return new StoredDocumentFile(
            disk: 'local',
            path: $path,
            originalName: $this->safeFileName($file->getClientOriginalName()),
            mimeType: 'application/pdf',
            size: max(0, (int) $file->getSize()),
        );
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    public function download(Document $document): StreamedResponse
    {
        abort_unless(
            Storage::disk($document->disk)->exists($document->file_path),
            404,
            trans('app.errors.document_file_missing'),
        );

        return Storage::disk($document->disk)->download(
            $document->file_path,
            $this->safeFileName($document->original_name, "document-{$document->id}.pdf"),
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function safeFileName(string $name, string $fallback = 'document.pdf'): string
    {
        $name = trim(str_replace(["\r", "\n"], '', basename(str_replace('\\', '/', $name))));

        return $name !== '' ? $name : $fallback;
    }
}
