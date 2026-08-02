<?php

namespace App\Modules\Reports\Actions;

use App\Modules\Documents\Support\BilingualPdf;
use App\Modules\Reports\Support\PropertyOperatingReportFilename;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class PropertyOperatingReportPdfExport
{
    private const int MAX_DETAIL_ROWS = 50;

    public function __construct(
        private BilingualPdf $pdf,
        private PropertyOperatingReportFilename $filenames,
    ) {}

    /** @param array<string, mixed> $data */
    public function download(array $data): StreamedResponse
    {
        $data['export'] = [
            'generated_at' => now()->format('Y-m-d H:i'),
            'limits' => [],
        ];

        foreach ([
            'arrearsLeases',
            'recentPayments',
            'recentExpenses',
            'maintenanceBacklog',
            'operationalJournal',
        ] as $key) {
            $records = is_array($data[$key] ?? null) ? $data[$key] : [];
            $data['export']['limits'][$key] = [
                'total' => count($records),
                'shown' => min(count($records), self::MAX_DETAIL_ROWS),
            ];
            $data[$key] = array_slice($records, 0, self::MAX_DETAIL_ROWS);
        }

        $content = $this->pdf
            ->loadView('pdf.property-operating-report', ['data' => $data])
            ->setPaper('a4', 'landscape')
            ->output();

        return response()->streamDownload(
            static fn () => print ($content),
            $this->filenames->make($data, 'pdf'),
            ['Content-Type' => 'application/pdf'],
        );
    }
}
