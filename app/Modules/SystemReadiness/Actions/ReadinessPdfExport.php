<?php

namespace App\Modules\SystemReadiness\Actions;

use App\Modules\Documents\Support\BilingualPdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ReadinessPdfExport
{
    public function __construct(private BilingualPdf $pdf) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $content = $this->pdf
            ->loadView('pdf.system-readiness', ['data' => $data])
            ->setPaper('a4')
            ->output();

        return response()->streamDownload(
            static fn () => print ($content),
            'launch-readiness-'.now()->format('Ymd-His').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
