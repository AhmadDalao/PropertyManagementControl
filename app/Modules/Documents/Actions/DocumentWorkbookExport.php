<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Queries\DocumentIndexQuery;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly DocumentIndexQuery $documents,
        private readonly DocumentAttachments $attachments,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('documents', [
            'Title',
            'Arabic Title',
            'Type',
            'Attachment',
            'Original File',
            'Mime Type',
            'Size',
            trans('app.documents.portal_visible'),
            'Portfolio',
            'Uploaded By',
            'Created',
        ], $this->documents->forExport($request, $actor), fn (Document $document): array => [
            $document->title_en,
            $document->title_ar,
            $this->workbook->option($document->type),
            $this->attachmentLabel($document),
            $document->original_name,
            $document->mime_type,
            $document->file_size,
            $this->workbook->yesNo($document->is_public),
            $this->workbook->localized($document->portfolio, 'name_en', 'name_ar'),
            $document->uploadedBy?->name,
            $this->workbook->date($document->created_at, true),
        ]);
    }

    private function attachmentLabel(Document $document): string
    {
        $type = $this->attachments->aliasForDocument($document) ?? 'record';

        return $this->workbook->option($type).' #'.$document->documentable_id;
    }
}
