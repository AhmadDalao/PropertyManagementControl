<?php

namespace App\Modules\Exports\Support;

use RuntimeException;
use ZipArchive;

final class SimpleDocx
{
    /**
     * @param  array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>  $blocks
     */
    public function create(array $blocks): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to create Word files.');
        }

        $path = tempnam(sys_get_temp_dir(), 'pmc-word-');
        throw_if($path === false, RuntimeException::class, 'Unable to create a temporary Word file.');

        $zip = new ZipArchive;
        throw_if(
            $zip->open($path, ZipArchive::OVERWRITE) !== true,
            RuntimeException::class,
            'Unable to create the Word archive.',
        );

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('word/document.xml', $this->document($blocks));
        $zip->addFromString('word/styles.xml', $this->styles());
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationships());
        $zip->close();

        return $path;
    }

    /** @param array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}> $blocks */
    private function document(array $blocks): string
    {
        $body = '';

        foreach ($blocks as $block) {
            $body .= $block['type'] === 'table'
                ? $this->table($block['rows'] ?? [])
                : $this->paragraph($block['text'] ?? '', $block['style'] ?? null);
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="900" w:right="900" w:bottom="900" w:left="900"/></w:sectPr>'
            .'</w:body></w:document>';
    }

    private function paragraph(string $text, ?string $style = null): string
    {
        $rtl = preg_match('/\p{Arabic}/u', $text) === 1;
        $properties = ($style ? '<w:pStyle w:val="'.$this->xml($style).'"/>' : '')
            .($rtl ? '<w:bidi/><w:jc w:val="right"/>' : '');
        $runProperties = $rtl ? '<w:rtl/>' : '';

        return '<w:p><w:pPr>'.$properties.'</w:pPr><w:r><w:rPr>'.$runProperties
            .'</w:rPr><w:t xml:space="preserve">'.$this->xml($text).'</w:t></w:r></w:p>';
    }

    /** @param array<int, array<int, string>> $rows */
    private function table(array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $xml = '<w:tbl><w:tblPr><w:tblStyle w:val="PMCGrid"/><w:tblW w:w="0" w:type="auto"/>'
            .'<w:tblLook w:val="04A0"/></w:tblPr>';

        foreach ($rows as $row) {
            $xml .= '<w:tr>';
            foreach ($row as $cell) {
                $xml .= '<w:tc><w:tcPr><w:tcW w:w="0" w:type="auto"/></w:tcPr>'
                    .$this->paragraph((string) $cell).'</w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>
</Types>
XML;
    }

    private function rootRelationships(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>
XML;
    }

    private function documentRelationships(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>
XML;
    }

    private function styles(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:sz w:val="20"/></w:rPr></w:style>
    <w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="180"/></w:pPr><w:rPr><w:b/><w:sz w:val="34"/></w:rPr></w:style>
    <w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="Heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:before="220" w:after="100"/></w:pPr><w:rPr><w:b/><w:color w:val="8A6800"/><w:sz w:val="25"/></w:rPr></w:style>
    <w:style w:type="table" w:styleId="PMCGrid"><w:name w:val="PMC Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4" w:color="D9D3C7"/><w:left w:val="single" w:sz="4" w:color="D9D3C7"/><w:bottom w:val="single" w:sz="4" w:color="D9D3C7"/><w:right w:val="single" w:sz="4" w:color="D9D3C7"/><w:insideH w:val="single" w:sz="4" w:color="E8E3DA"/><w:insideV w:val="single" w:sz="4" w:color="E8E3DA"/></w:tblBorders></w:tblPr></w:style>
</w:styles>
XML;
    }
}
