<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Queries\DocumentIndexQuery;
use App\Modules\Documents\Support\DocumentAttachments;
use App\Modules\Documents\Support\DocumentExpiryState;
use App\Modules\Documents\Support\DocumentOptions;
use App\Modules\Exports\Contracts\ResourceExporter;
use App\Modules\Exports\Support\ResourceWorkbook;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentWorkbookExport implements ResourceExporter
{
    public function __construct(
        private readonly DocumentIndexQuery $documents,
        private readonly DocumentAttachments $attachments,
        private readonly DocumentExpiryState $expiry,
        private readonly ResourceWorkbook $workbook,
    ) {}

    public function download(Request $request, User $actor): BinaryFileResponse
    {
        return $this->workbook->download('documents', [
            trans('app.documents.export_headers.title'),
            trans('app.documents.export_headers.arabic_title'),
            trans('app.documents.export_headers.type'),
            trans('app.documents.export_headers.issued_on'),
            trans('app.documents.export_headers.expires_on'),
            trans('app.documents.export_headers.expiry_status'),
            trans('app.documents.export_headers.days_remaining'),
            trans('app.documents.export_headers.attachment'),
            trans('app.documents.export_headers.original_file'),
            trans('app.documents.export_headers.mime_type'),
            trans('app.documents.export_headers.size'),
            trans('app.documents.export_headers.portal_visible'),
            trans('app.documents.export_headers.portfolio'),
            trans('app.documents.export_headers.uploaded_by'),
            trans('app.documents.export_headers.created'),
        ], $this->documents->forExport($request, $actor), fn (Document $document): array => [
            $document->title_en,
            $document->title_ar,
            DocumentOptions::label($document->type),
            $this->workbook->date($document->issued_on),
            $this->workbook->date($document->expires_on),
            $this->expiry->label($document->expires_on),
            $this->expiry->daysRemaining($document->expires_on),
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

        return DocumentOptions::label($type).' #'.$document->documentable_id;
    }
}
