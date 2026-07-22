<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Media\Queries\MediaFileIndexQuery;
use App\Modules\Media\Support\MediaOptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaFileWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly MediaFileIndexQuery $media,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('media-files', [
            trans('app.media.export_headers.title'),
            trans('app.media.export_headers.arabic_title'),
            trans('app.media.export_headers.collection'),
            trans('app.media.export_headers.visibility'),
            trans('app.media.export_headers.file'),
            trans('app.media.export_headers.mime_type'),
            trans('app.media.export_headers.size'),
            trans('app.media.export_headers.dimensions'),
            trans('app.media.export_headers.portfolio'),
            trans('app.media.export_headers.uploaded_by'),
            trans('app.media.export_headers.created'),
        ], $this->media->forExport($request, $actor), fn (MediaFile $media): array => [
            $media->title_en,
            $media->title_ar,
            $media->collection,
            MediaOptions::label($media->visibility),
            basename((string) $media->path),
            $media->mime_type,
            $media->size,
            $media->width && $media->height ? "{$media->width} x {$media->height} px" : '',
            $this->workbook->localized($media->portfolio, 'name_en', 'name_ar'),
            $media->uploadedBy?->name,
            $this->workbook->date($media->created_at, true),
        ]);
    }
}
