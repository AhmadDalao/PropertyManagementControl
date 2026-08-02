<?php

namespace App\Modules\Exports\Support\Docx;

final readonly class DocxDocumentBuilder
{
    private const int A4_SHORT = 11906;

    private const int A4_LONG = 16838;

    private const int MARGIN = 900;

    public function __construct(private DocxText $text) {}

    /**
     * @param  array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>  $blocks
     * @param  array{orientation?:'auto'|'portrait'|'landscape',title?:string}  $options
     */
    public function build(array $blocks, array $options = []): DocxDocument
    {
        $title = trim((string) ($options['title'] ?? $this->title($blocks)));
        $orientation = $this->orientation($blocks, $options['orientation'] ?? 'auto');
        $landscape = $orientation === 'landscape';
        $pageWidth = $landscape ? self::A4_LONG : self::A4_SHORT;
        $pageHeight = $landscape ? self::A4_SHORT : self::A4_LONG;
        $usableWidth = $pageWidth - (self::MARGIN * 2);
        $rightToLeft = $this->text->isRightToLeft($title);
        $body = '';

        foreach ($blocks as $index => $block) {
            $nextRows = $blocks[$index + 1]['rows'] ?? [];
            $startsLongTable = ($block['style'] ?? null) === 'Heading1'
                && ($blocks[$index + 1]['type'] ?? null) === 'table'
                && count($nextRows) > 12;
            $body .= $block['type'] === 'table'
                ? $this->table($block['rows'] ?? [], $usableWidth, $rightToLeft)
                : $this->paragraph(
                    (string) ($block['text'] ?? ''),
                    $block['style'] ?? null,
                    $startsLongTable,
                );
        }

        $section = $this->section($pageWidth, $pageHeight, $landscape);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<w:body>'.$body.$section.'</w:body></w:document>';

        return new DocxDocument(
            xml: $xml,
            title: $title !== '' ? $title : 'Property Management Control',
            rightToLeft: $rightToLeft,
            usableWidth: $usableWidth,
        );
    }

    /** @param array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}> $blocks */
    private function title(array $blocks): string
    {
        foreach ($blocks as $block) {
            if (($block['style'] ?? null) === 'Title') {
                return (string) ($block['text'] ?? '');
            }
        }

        return 'Property Management Control';
    }

    /**
     * @param  array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>  $blocks
     * @param  'auto'|'portrait'|'landscape'  $requested
     * @return 'portrait'|'landscape'
     */
    private function orientation(array $blocks, string $requested): string
    {
        if ($requested !== 'auto') {
            return $requested;
        }

        $columns = 0;
        foreach ($blocks as $block) {
            foreach ($block['rows'] ?? [] as $row) {
                $columns = max($columns, count($row));
            }
        }

        return $columns >= 5 ? 'landscape' : 'portrait';
    }

    private function paragraph(
        string $value,
        ?string $style = null,
        bool $pageBreakBefore = false,
    ): string {
        $rightToLeft = $this->text->isRightToLeft($value);
        $keep = in_array($style, ['Title', 'Heading1', 'Heading2'], true)
            ? '<w:keepNext/><w:keepLines/>'
            : '';
        $properties = $style ? '<w:pStyle w:val="'.$this->text->xml($style).'"/>' : '';
        $properties .= $keep;
        $properties .= $rightToLeft ? '<w:bidi/><w:jc w:val="right"/>' : '';

        return '<w:p><w:pPr>'.$properties.'</w:pPr>'
            .($pageBreakBefore ? '<w:r><w:br w:type="page"/></w:r>' : '')
            .$this->run($value, rightToLeft: $rightToLeft)
            .'</w:p>';
    }

    /** @param array<int, array<int, string>> $rows */
    private function table(array $rows, int $width, bool $documentRightToLeft): string
    {
        if ($rows === []) {
            return '';
        }

        $columns = max(array_map('count', $rows));
        $widths = $this->columnWidths($columns, $width);
        $fontSize = $columns >= 7 ? 15 : ($columns >= 5 ? 16 : 18);
        $xml = '<w:tbl>'.$this->tableProperties($width, $documentRightToLeft)
            .'<w:tblGrid>'.collect($widths)
                ->map(fn (int $column): string => '<w:gridCol w:w="'.$column.'"/>')
                ->join('').'</w:tblGrid>';

        foreach ($rows as $index => $row) {
            $xml .= $this->tableRow(
                array_pad(array_values($row), $columns, ''),
                $widths,
                $fontSize,
                $index === 0,
                $index === 0,
            );
        }

        if (count($rows) === 1) {
            $xml .= $this->emptyTableRow($columns, $width, $fontSize);
        }

        return $xml.'</w:tbl><w:p><w:pPr><w:spacing w:after="80"/></w:pPr></w:p>';
    }

    private function tableProperties(int $width, bool $rightToLeft): string
    {
        return '<w:tblPr><w:tblW w:w="'.$width.'" w:type="dxa"/>'
            .'<w:tblLayout w:type="fixed"/><w:jc w:val="'.($rightToLeft ? 'right' : 'left').'"/>'
            .($rightToLeft ? '<w:bidiVisual/>' : '')
            .'<w:tblCellMar><w:top w:w="80" w:type="dxa"/><w:left w:w="90" w:type="dxa"/>'
            .'<w:bottom w:w="80" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar>'
            .'<w:tblBorders><w:top w:val="single" w:sz="6" w:color="D7D0C4"/>'
            .'<w:left w:val="single" w:sz="6" w:color="D7D0C4"/>'
            .'<w:bottom w:val="single" w:sz="6" w:color="D7D0C4"/>'
            .'<w:right w:val="single" w:sz="6" w:color="D7D0C4"/>'
            .'<w:insideH w:val="single" w:sz="4" w:color="E7E1D7"/>'
            .'<w:insideV w:val="single" w:sz="4" w:color="E7E1D7"/>'
            .'</w:tblBorders></w:tblPr>';
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<int, int>  $widths
     */
    private function tableRow(
        array $row,
        array $widths,
        int $fontSize,
        bool $header,
        bool $keepWithNext,
    ): string {
        $xml = '<w:tr><w:trPr><w:cantSplit/>'.($header ? '<w:tblHeader/>' : '').'</w:trPr>';

        foreach ($row as $index => $value) {
            $width = $widths[$index];
            $shade = $header ? '<w:shd w:val="clear" w:color="auto" w:fill="F4EFE5"/>' : '';
            $xml .= '<w:tc><w:tcPr><w:tcW w:w="'.$width.'" w:type="dxa"/>'
                .'<w:vAlign w:val="center"/>'.$shade.'</w:tcPr>'
                .$this->cellParagraph((string) $value, $fontSize, $header, $keepWithNext)
                .'</w:tc>';
        }

        return $xml.'</w:tr>';
    }

    private function emptyTableRow(int $columns, int $width, int $fontSize): string
    {
        return '<w:tr><w:trPr><w:cantSplit/></w:trPr><w:tc><w:tcPr>'
            .'<w:tcW w:w="'.$width.'" w:type="dxa"/><w:gridSpan w:val="'.$columns.'"/>'
            .'<w:shd w:val="clear" w:color="auto" w:fill="FBFAF7"/></w:tcPr>'
            .$this->cellParagraph('No records / لا توجد سجلات', $fontSize, false)
            .'</w:tc></w:tr>';
    }

    private function cellParagraph(
        string $value,
        int $fontSize,
        bool $bold,
        bool $keepWithNext = false,
    ): string {
        $rightToLeft = $this->text->isRightToLeft($value);
        $properties = '<w:spacing w:before="0" w:after="0" w:line="240" w:lineRule="auto"/>'
            .($keepWithNext ? '<w:keepNext/>' : '')
            .($rightToLeft ? '<w:bidi/><w:jc w:val="right"/>' : '');

        return '<w:p><w:pPr>'.$properties.'</w:pPr>'
            .$this->run(
                $value,
                bold: $bold,
                size: $fontSize,
                rightToLeft: $rightToLeft,
            )
            .'</w:p>';
    }

    private function run(
        string $value,
        bool $bold = false,
        ?int $size = null,
        bool $rightToLeft = false,
    ): string {
        $properties = '<w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/>'
            .'<w:lang w:val="en-US" w:bidi="ar-SA"/>'
            .($bold ? '<w:b/>' : '')
            .($size ? '<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/>' : '')
            .($rightToLeft ? '<w:rtl/>' : '');

        return '<w:r><w:rPr>'.$properties.'</w:rPr><w:t xml:space="preserve">'
            .$this->text->xml($value)
            .'</w:t></w:r>';
    }

    /** @return array<int, int> */
    private function columnWidths(int $columns, int $width): array
    {
        if ($columns === 2) {
            return [(int) floor($width * 0.36), $width - (int) floor($width * 0.36)];
        }

        $base = intdiv($width, $columns);
        $widths = array_fill(0, $columns, $base);
        $widths[$columns - 1] += $width - array_sum($widths);

        return $widths;
    }

    private function section(int $width, int $height, bool $landscape): string
    {
        return '<w:sectPr><w:headerReference w:type="default" r:id="rId1"/>'
            .'<w:footerReference w:type="default" r:id="rId2"/>'
            .'<w:pgSz w:w="'.$width.'" w:h="'.$height.'"'
            .($landscape ? ' w:orient="landscape"' : '').'/>'
            .'<w:pgMar w:top="'.self::MARGIN.'" w:right="'.self::MARGIN.'" w:bottom="1050" '
            .'w:left="'.self::MARGIN.'" w:header="420" w:footer="420" w:gutter="0"/>'
            .'<w:pgNumType w:start="1"/></w:sectPr>';
    }
}
