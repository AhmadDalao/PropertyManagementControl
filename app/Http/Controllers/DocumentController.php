<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Lease;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function download(Document $document): StreamedResponse
    {
        /** @var User $actor */
        $actor = request()->user();

        abort_unless($this->canDownload($actor, $document), 403);

        return Storage::disk($document->disk)->download($document->file_path, $document->original_name);
    }

    private function canDownload(User $actor, Document $document): bool
    {
        if ($actor->hasRole('superadmin')) {
            return true;
        }

        if ($actor->hasAnyRole(['owner', 'property_manager']) && $actor->portfolio_id === $document->portfolio_id) {
            return true;
        }

        if ($actor->hasRole('tenant') && $document->documentable_type === Lease::class) {
            $lease = $document->documentable;

            return $lease?->tenantProfile?->user_id === $actor->id;
        }

        return false;
    }
}
