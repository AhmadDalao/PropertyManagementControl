<?php

namespace App\Modules\Media\Actions;

use App\Models\MediaFile;
use App\Models\User;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use App\Modules\Media\Queries\MediaFileIndexQuery;
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
            'Title',
            'Collection',
            'Visibility',
            'Path',
            'Mime Type',
            'Size',
            'Portfolio',
        ], $this->media->forExport($request, $actor), fn (MediaFile $media): array => [
            $this->workbook->localized($media, 'title_en', 'title_ar')
                ?? $this->workbook->copy('Media').' #'.$media->id,
            $this->workbook->option($media->collection),
            $this->workbook->option($media->visibility),
            $media->path,
            $media->mime_type,
            $media->size,
            $this->workbook->localized($media->portfolio, 'name_en', 'name_ar'),
        ]);
    }
}
