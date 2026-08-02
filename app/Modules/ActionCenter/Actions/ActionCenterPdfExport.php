<?php

namespace App\Modules\ActionCenter\Actions;

use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ActionCenterPdfExport
{
    public function __construct(private ActionCenterReportFiles $files) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $content = $this->files->pdf($data);

        return response()->streamDownload(
            static fn () => print ($content),
            'daily-operations-brief-'.now()->format('Ymd-His').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
