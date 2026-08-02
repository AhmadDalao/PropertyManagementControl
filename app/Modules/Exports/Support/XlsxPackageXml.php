<?php

namespace App\Modules\Exports\Support;

final class XlsxPackageXml
{
    public function contentTypes(int $sheetCount): string
    {
        $worksheets = collect(range(1, $sheetCount))
            ->map(fn (int $index): string => sprintf(
                '<Override PartName="/xl/worksheets/sheet%d.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>',
                $index,
            ))
            ->implode('');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    {$worksheets}
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    public function rootRelationships(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    /**
     * @param  array<int, array{name:string,rows:array<int, array<int, mixed>>}>  $sheets
     */
    public function workbook(array $sheets): string
    {
        $sheetNodes = collect(array_values($sheets))
            ->map(fn (array $sheet, int $index): string => sprintf(
                '<sheet name="%s" sheetId="%d" r:id="rId%d"/>',
                $this->sheetName($sheet['name']),
                $index + 1,
                $index + 1,
            ))
            ->implode('');

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>{$sheetNodes}</sheets>
</workbook>
XML;
    }

    public function workbookRelationships(int $sheetCount): string
    {
        $relationships = collect(range(1, $sheetCount))
            ->map(fn (int $index): string => sprintf(
                '<Relationship Id="rId%d" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet%d.xml"/>',
                $index,
                $index,
            ))
            ->implode('');
        $stylesRelationship = $sheetCount + 1;

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    {$relationships}
    <Relationship Id="rId{$stylesRelationship}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    public function styles(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
    <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }

    private function sheetName(string $sheetName): string
    {
        $sheetName = trim(preg_replace('/[:\\\\\\/\\?\\*\\[\\]]/', ' ', $sheetName) ?: 'Report');
        $sheetName = mb_substr($sheetName, 0, 31) ?: 'Report';

        return htmlspecialchars($sheetName, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
