<?php

namespace Tests\Unit;

use App\Modules\Exports\Support\XlsxWorkbook;
use App\Modules\OpeningData\Support\XlsxReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;
use ZipArchive;

class OpeningDataXlsxReaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reader_handles_excel_shared_strings_rich_text_and_date_styles(): void
    {
        $path = app(XlsxWorkbook::class)->create([
            ['code', 'started_at'],
        ], 'Assets');
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));
        $zip->addFromString('xl/sharedStrings.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="3" uniqueCount="3">
    <si><t>code</t></si>
    <si><t>started_at</t></si>
    <si><r><t>ASSET-</t></r><r><t>001</t></r></si>
</sst>
XML);
        $zip->addFromString('xl/styles.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
    <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
    <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
    <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
    <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="14" fontId="0" fillId="0" borderId="0" xfId="0"/>
    </cellXfs>
    <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML);
        $zip->addFromString('xl/worksheets/sheet1.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <sheetData>
        <row r="1">
            <c r="A1" t="s"><v>0</v></c>
            <c r="B1" t="s"><v>1</v></c>
        </row>
        <row r="2">
            <c r="A2" t="s"><v>2</v></c>
            <c r="B2" s="1"><v>46023</v></c>
        </row>
    </sheetData>
</worksheet>
XML);
        $zip->close();

        $table = app(XlsxReader::class)->tables($path, ['Assets'])['Assets'];

        $this->assertSame(['code', 'started_at'], $table['headers']);
        $this->assertSame('ASSET-001', $table['rows'][0]['code']);
        $this->assertSame('2026-01-01', $table['rows'][0]['started_at']);
        @unlink($path);
    }

    public function test_reader_rejects_workbooks_with_an_excessive_entry_count(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'opening-data-entries-');
        $this->assertIsString($path);
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        for ($index = 0; $index <= 2_000; $index++) {
            $zip->addFromString("xl/custom/entry-{$index}.xml", '<x/>');
        }

        $zip->close();

        try {
            app(XlsxReader::class)->tables($path, ['Assets']);
            $this->fail('The oversized workbook should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                trans('app.opening_data.errors.workbook_too_large'),
                $exception->getMessage(),
            );
        } finally {
            @unlink($path);
        }
    }
}
