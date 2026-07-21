<?php

namespace App\Modules\Shared;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PrivatePdfDocuments
{
    public function __construct(private readonly MorphTypes $morphTypes) {}

    public function replace(
        Model $subject,
        User $actor,
        int $portfolioId,
        string $type,
        string $titleEn,
        string $titleAr,
        string $directory,
        string $fileName,
        string $content,
        bool $portalVisible = false,
    ): Document {
        $disk = 'local';
        $path = trim($directory, '/').'/'.Str::uuid().'-'.$fileName;

        if (! Storage::disk($disk)->put($path, $content)) {
            throw new RuntimeException('The generated PDF could not be stored.');
        }

        $document = Document::query()
            ->whereIn('documentable_type', $this->morphTypes->for($subject))
            ->where('documentable_id', $subject->getKey())
            ->where('type', $type)
            ->latest('id')
            ->first() ?? new Document;
        $previousDisk = $document->exists ? (string) $document->getAttribute('disk') : null;
        $previousPath = $document->exists ? (string) $document->getAttribute('file_path') : null;

        if (! $document->exists) {
            $document->uploadedBy()->associate($actor);
        }

        try {
            $document->fill([
                'portfolio_id' => $portfolioId,
                'documentable_type' => $subject->getMorphClass(),
                'documentable_id' => $subject->getKey(),
                'type' => $type,
                'title_en' => $titleEn,
                'title_ar' => $titleAr,
                'disk' => $disk,
                'file_path' => $path,
                'original_name' => $fileName,
                'mime_type' => 'application/pdf',
                'file_size' => strlen($content),
                'is_public' => $portalVisible,
            ])->save();
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }

        if ($previousDisk && $previousPath && ($previousDisk !== $disk || $previousPath !== $path)) {
            Storage::disk($previousDisk)->delete($previousPath);
        }

        return $document;
    }
}
