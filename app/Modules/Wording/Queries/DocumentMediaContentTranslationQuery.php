<?php

namespace App\Modules\Wording\Queries;

use App\Models\Document;
use App\Models\MediaFile;
use App\Modules\Wording\Support\ContentTranslationItem;
use Illuminate\Database\Eloquent\Builder;

class DocumentMediaContentTranslationQuery
{
    /**
     * @return array<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    public function items(): array
    {
        $documents = Document::query()
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', ''))
            ->limit(300)
            ->get()
            ->map(fn (Document $document): array => ContentTranslationItem::make(
                'documents',
                $document->title_en,
                $document->original_name,
                'title_ar',
                route('documents.edit', $document),
            ))
            ->all();
        $media = MediaFile::query()
            ->where('visibility', 'public')
            ->where(fn (Builder $query) => $query
                ->whereNull('title_ar')
                ->orWhere('title_ar', '')
                ->orWhereNull('alt_text_ar')
                ->orWhere('alt_text_ar', ''))
            ->limit(300)
            ->get()
            ->map(fn (MediaFile $media): array => ContentTranslationItem::make(
                'media',
                $media->title_en ?: basename($media->path),
                $media->collection,
                blank($media->title_ar) ? 'title_ar' : 'alt_text_ar',
                route('media-files.edit', $media),
            ))
            ->all();

        return [...$documents, ...$media];
    }
}
