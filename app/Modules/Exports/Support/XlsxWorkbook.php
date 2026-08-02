<?php

namespace App\Modules\Exports\Support;

use RuntimeException;
use ZipArchive;

class XlsxWorkbook
{
    public function __construct(private readonly XlsxPackageXml $package) {}

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function create(array $rows, string $sheetName = 'Portfolio Report'): string
    {
        return $this->createSheets([
            ['name' => $sheetName, 'rows' => $rows],
        ]);
    }

    /**
     * @param  array<int, array{name:string,rows:array<int, array<int, mixed>>}>  $sheets
     */
    public function createSheets(array $sheets): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to create XLSX files.');
        }

        if ($sheets === []) {
            throw new RuntimeException('At least one XLSX worksheet is required.');
        }

        $path = tempnam(sys_get_temp_dir(), 'pmc-report-');

        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary XLSX file.');
        }

        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open the XLSX archive for writing.');
        }

        $zip->addFromString('[Content_Types].xml', $this->package->contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', $this->package->rootRelationships());
        $zip->addFromString('xl/workbook.xml', $this->package->workbook($sheets));
        $zip->addFromString(
            'xl/_rels/workbook.xml.rels',
            $this->package->workbookRelationships(count($sheets)),
        );
        $zip->addFromString('xl/styles.xml', $this->package->styles());

        foreach (array_values($sheets) as $index => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet'.($index + 1).'.xml',
                $this->sheet($sheet['rows']),
            );
        }

        $zip->close();

        return $path;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function sheet(array $rows): string
    {
        $sheetRows = collect($rows)
            ->values()
            ->map(fn (array $row, int $index): string => $this->row($row, $index + 1))
            ->implode('');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>{$sheetRows}</sheetData>
</worksheet>
XML;
    }

    /**
     * @param  array<int, mixed>  $values
     */
    private function row(array $values, int $rowNumber): string
    {
        $cells = collect($values)
            ->values()
            ->map(fn (mixed $value, int $index): string => $this->cell($value, $this->cellReference($index + 1, $rowNumber)))
            ->implode('');

        return '<row r="'.$rowNumber.'">'.$cells.'</row>';
    }

    private function cell(mixed $value, string $reference): string
    {
        if ($value === null || $value === '') {
            return '<c r="'.$reference.'"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'"><v>'.$value.'</v></c>';
        }

        $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        return '<c r="'.$reference.'" t="inlineStr"><is><t>'.$escaped.'</t></is></c>';
    }

    private function cellReference(int $column, int $row): string
    {
        $letters = '';

        while ($column > 0) {
            $column--;
            $letters = chr(65 + ($column % 26)).$letters;
            $column = intdiv($column, 26);
        }

        return $letters.$row;
    }
}
