<?php

namespace App\Modules\OpeningData\Support;

use RuntimeException;
use ZipArchive;

final class XlsxArchiveGuard
{
    private const MAX_EXPANDED_XML_BYTES = 60_000_000;

    private const MAX_ARCHIVE_ENTRIES = 2_000;

    public function ensureSafe(ZipArchive $zip): void
    {
        if ($zip->numFiles > self::MAX_ARCHIVE_ENTRIES) {
            throw new RuntimeException($this->message());
        }

        $expandedBytes = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entry = $zip->statIndex($index);

            if (! is_array($entry)) {
                throw new RuntimeException(
                    (string) trans('app.opening_data.errors.invalid_workbook'),
                );
            }

            $name = mb_strtolower($entry['name']);

            if (str_ends_with($name, '.xml') || str_ends_with($name, '.rels')) {
                $expandedBytes += $entry['size'];
            }

            if ($expandedBytes > self::MAX_EXPANDED_XML_BYTES) {
                throw new RuntimeException($this->message());
            }
        }
    }

    private function message(): string
    {
        return (string) trans('app.opening_data.errors.workbook_too_large');
    }
}
