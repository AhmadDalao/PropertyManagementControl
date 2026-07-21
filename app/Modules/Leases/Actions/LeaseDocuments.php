<?php

namespace App\Modules\Leases\Actions;

use App\Models\Document;
use App\Models\Lease;
use App\Models\User;
use App\Modules\Leases\Support\LeaseAccess;
use App\Modules\Shared\PrivatePdfDocuments;
use App\Support\BilingualPdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LeaseDocuments
{
    public function __construct(
        private readonly LeaseAccess $access,
        private readonly BilingualPdf $pdf,
        private readonly PrivatePdfDocuments $documents,
    ) {}

    public function uploadSigned(User $actor, Lease $lease, UploadedFile $file): Document
    {
        $this->access->ensureCanManage($actor, $lease);
        $path = $file->store("documents/leases/{$lease->id}", 'local');

        if ($path === false) {
            throw new RuntimeException('The signed contract could not be stored.');
        }

        try {
            return Document::query()->create([
                'portfolio_id' => $lease->portfolio_id,
                'uploaded_by_user_id' => $actor->id,
                'documentable_type' => $lease->getMorphClass(),
                'documentable_id' => $lease->id,
                'type' => 'signed_contract',
                'title_en' => "Signed contract {$lease->code}",
                'title_ar' => "العقد الموقع {$lease->code}",
                'disk' => 'local',
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/pdf',
                'file_size' => $file->getSize(),
                'is_public' => false,
            ]);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
    }

    public function contract(User $actor, Lease $lease): StreamedResponse
    {
        $this->access->ensureCanAccess($actor, $lease);
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'portfolio.owner', 'managedBy');

        return $this->generate(
            $actor,
            $lease,
            'pdf.lease-contract',
            'lease_contract',
            "lease-contract-{$lease->code}.pdf",
            "Lease contract {$lease->code}",
            "عقد الإيجار {$lease->code}",
        );
    }

    public function statement(User $actor, Lease $lease): StreamedResponse
    {
        $this->access->ensureCanAccess($actor, $lease);
        $lease->loadMissing('tenantProfile.user', 'leaseable', 'installments', 'payments', 'portfolio.owner');

        return $this->generate(
            $actor,
            $lease,
            'pdf.tenant-statement',
            'tenant_statement',
            "tenant-statement-{$lease->code}.pdf",
            "Tenant statement {$lease->code}",
            "كشف حساب المستأجر {$lease->code}",
        );
    }

    /**
     * @param  view-string  $view
     */
    private function generate(
        User $actor,
        Lease $lease,
        string $view,
        string $type,
        string $fileName,
        string $titleEn,
        string $titleAr,
    ): StreamedResponse {
        $content = $this->pdf->loadView($view, ['lease' => $lease])->setPaper('a4')->output();
        $this->documents->replace(
            $lease,
            $actor,
            $lease->portfolio_id,
            $type,
            $titleEn,
            $titleAr,
            "generated/leases/{$lease->id}",
            $fileName,
            $content,
        );

        return response()->streamDownload(static fn () => print ($content), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
