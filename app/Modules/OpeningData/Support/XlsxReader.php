<?php

namespace App\Modules\OpeningData\Support;

use DateTimeImmutable;
use DOMElement;
use RuntimeException;
use ZipArchive;

final class XlsxReader
{
    private const MAX_ROWS_PER_SHEET = 5_001;

    private const MAX_COLUMNS = 100;

    public function __construct(
        private readonly XlsxArchiveReader $archive,
        private readonly XlsxArchiveGuard $guard,
    ) {}

    /**
     * @param  array<int, string>  $requiredSheets
     * @return array<string, array{headers:array<int, string>,rows:array<int, array<string, mixed>>}>
     */
    public function tables(string $path, array $requiredSheets): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException($this->message('app.opening_data.errors.invalid_workbook'));
        }

        try {
            $this->guard->ensureSafe($zip);
            $sharedStrings = $this->archive->sharedStrings($zip);
            $dateStyles = $this->archive->dateStyles($zip);
            $sheetPaths = $this->archive->sheetPaths($zip);
            $tables = [];

            foreach ($requiredSheets as $requiredSheet) {
                $pathForSheet = $this->archive->matchingSheetPath($sheetPaths, $requiredSheet);

                if ($pathForSheet === null) {
                    throw new RuntimeException($this->message(
                        'app.opening_data.errors.missing_sheet',
                        ['sheet' => $requiredSheet],
                    ));
                }

                $tables[$requiredSheet] = $this->table(
                    $zip,
                    $pathForSheet,
                    $sharedStrings,
                    $dateStyles,
                );
            }

            return $tables;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @param  array<int, bool>  $dateStyles
     * @return array{headers:array<int, string>,rows:array<int, array<string, mixed>>}
     */
    private function table(
        ZipArchive $zip,
        string $path,
        array $sharedStrings,
        array $dateStyles,
    ): array {
        $document = $this->archive->document($zip, $path);
        $rawRows = [];

        foreach ($this->archive->nodes($document, '//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
            if (! $row instanceof DOMElement) {
                continue;
            }

            if (count($rawRows) >= self::MAX_ROWS_PER_SHEET) {
                throw new RuntimeException($this->message('app.opening_data.errors.sheet_too_large'));
            }

            $values = [];
            $nextColumn = 0;

            foreach ($this->children($row, 'c') as $cell) {
                $column = $this->columnIndex($cell->getAttribute('r')) ?? $nextColumn;

                if ($column >= self::MAX_COLUMNS) {
                    throw new RuntimeException($this->message('app.opening_data.errors.too_many_columns'));
                }

                $values[$column] = $this->cellValue($cell, $sharedStrings, $dateStyles);
                $nextColumn = $column + 1;
            }

            if ($this->hasValue($values)) {
                $rawRows[] = $values;
            }
        }

        if ($rawRows === []) {
            return ['headers' => [], 'rows' => []];
        }

        $headerRow = array_shift($rawRows);
        $headers = [];

        foreach ($headerRow as $column => $value) {
            $header = $this->header($value);

            if ($header !== '') {
                $headers[(int) $column] = $header;
            }
        }

        if ($headers === []) {
            throw new RuntimeException($this->message('app.opening_data.errors.missing_headers'));
        }

        $rows = [];

        foreach ($rawRows as $index => $rawRow) {
            $record = ['_row' => $index + 2];

            foreach ($headers as $column => $header) {
                $record[$header] = $rawRow[$column] ?? null;
            }

            if ($this->hasValue(array_diff_key($record, ['_row' => true]))) {
                $rows[] = $record;
            }
        }

        return [
            'headers' => array_values($headers),
            'rows' => $rows,
        ];
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @param  array<int, bool>  $dateStyles
     */
    private function cellValue(
        DOMElement $cell,
        array $sharedStrings,
        array $dateStyles,
    ): mixed {
        $type = $cell->getAttribute('t');

        if ($type === 'inlineStr') {
            return $this->archive->textContent($cell);
        }

        $value = $this->firstChildText($cell, 'v');

        if ($value === null) {
            return null;
        }

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'b') {
            return $value === '1';
        }

        if (in_array($type, ['str', 'e'], true)) {
            return $value;
        }

        $style = (int) ($cell->getAttribute('s') ?: 0);

        if (isset($dateStyles[$style]) && is_numeric($value)) {
            return $this->excelDate((float) $value);
        }

        if (preg_match('/^-?\d+$/', $value) === 1) {
            return (int) $value;
        }

        return is_numeric($value) ? (float) $value : $value;
    }

    private function excelDate(float $serial): string
    {
        $seconds = (int) round(($serial - 25569) * 86400);

        return (new DateTimeImmutable('@'.$seconds))->setTimezone(
            new \DateTimeZone('UTC'),
        )->format('Y-m-d');
    }

    /**
     * @return array<int, DOMElement>
     */
    private function children(DOMElement $element, string $name): array
    {
        $children = [];

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                $children[] = $child;
            }
        }

        return $children;
    }

    private function firstChildText(DOMElement $element, string $name): ?string
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $child->localName === $name) {
                return $child->textContent;
            }
        }

        return null;
    }

    private function columnIndex(string $reference): ?int
    {
        if (preg_match('/^([A-Z]+)\d+$/i', $reference, $matches) !== 1) {
            return null;
        }

        $column = 0;

        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $column = ($column * 26) + (ord($letter) - 64);
        }

        return $column - 1;
    }

    private function header(mixed $value): string
    {
        $header = mb_strtolower(trim((string) $value));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? '';

        return trim($header, '_');
    }

    /**
     * @param  array<array-key, mixed>  $values
     */
    private function hasValue(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, scalar>  $replace
     */
    private function message(string $key, array $replace = []): string
    {
        $message = trans($key, $replace);

        return is_string($message) ? $message : $key;
    }
}
