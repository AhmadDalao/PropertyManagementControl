<?php

namespace App\Modules\ActionCenter\Actions;

use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ActionCenterWordExport
{
    public function __construct(private ActionCenterReportFiles $files) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $binary = $this->files->docx($data);

        return response()->streamDownload(
            static fn () => print ($binary),
            'daily-operations-brief-'.now()->format('Ymd-His').'.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        );
    }
}
