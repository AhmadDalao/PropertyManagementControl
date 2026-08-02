<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Documents\Support\BilingualPdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class RentRollPdfExport
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
