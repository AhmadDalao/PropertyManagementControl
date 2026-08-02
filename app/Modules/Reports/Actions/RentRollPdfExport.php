<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Documents\Support\BilingualPdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class RentRollPdfExport
{
    public function __construct(private BilingualPdf $pdf) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $content = $this->pdf
            ->loadView('pdf.rent-roll', ['data' => $data])
            ->setPaper('a4', 'landscape')
            ->output();
        $filename = 'rent-roll-'.now()->format('Ymd-His').'.pdf';

        return response()->streamDownload(
            static fn () => print ($content),
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }
}
