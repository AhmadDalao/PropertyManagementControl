<?php

namespace App\Modules\ActionCenter\Actions;

use App\Modules\Documents\Support\BilingualPdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ActionCenterPdfExport
{
    public const int MAX_DETAIL_ROWS = 100;

    public function __construct(private BilingualPdf $pdf) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $records = is_array($data['records'] ?? null) ? $data['records'] : [];
        $data['recordTotal'] = count($records);
        $data['records'] = array_slice($records, 0, self::MAX_DETAIL_ROWS);
        $data['recordLimit'] = self::MAX_DETAIL_ROWS;
        $content = $this->pdf
            ->loadView('pdf.daily-operations-brief', ['data' => $data])
            ->setPaper('a4', 'landscape')
            ->output();

        return response()->streamDownload(
            static fn () => print ($content),
            'daily-operations-brief-'.now()->format('Ymd-His').'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
