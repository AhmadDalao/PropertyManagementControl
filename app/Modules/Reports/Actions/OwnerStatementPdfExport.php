<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Documents\Support\BilingualPdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class OwnerStatementPdfExport
{
    public function __construct(private BilingualPdf $pdf) {}

    /** @param array<string, mixed> $statement */
    public function download(array $statement): StreamedResponse
    {
        $content = $this->pdf
            ->loadView('pdf.owner-statement', ['data' => $statement])
            ->setPaper('a4')
            ->output();
        $filename = 'owner-statement-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(
            static fn () => print ($content),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
