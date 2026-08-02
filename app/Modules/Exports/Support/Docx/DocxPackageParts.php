<?php

namespace App\Modules\Exports\Support\Docx;

final readonly class DocxPackageParts
{
    public function __construct(private DocxText $text) {}

    /** @return array<string, string> */
    public function all(DocxDocument $document): array
    {
        return [
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRelationships(),
            'docProps/core.xml' => $this->coreProperties($document->title),
            'docProps/app.xml' => $this->appProperties(),
            'word/document.xml' => $document->xml,
            'word/styles.xml' => $this->styles(),
            'word/settings.xml' => $this->settings(),
            'word/header1.xml' => $this->header($document),
            'word/footer1.xml' => $this->footer(),
            'word/_rels/document.xml.rels' => $this->documentRelationships(),
        ];
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
    <Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/>
    <Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>
    <Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>
    <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
    <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
</Types>
XML;
    }

    private function rootRelationships(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
    <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    private function documentRelationships(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>
</Relationships>
XML;
    }

    private function styles(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:docDefaults>
        <w:rPrDefault><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/><w:lang w:val="en-US" w:bidi="ar-SA"/><w:sz w:val="19"/><w:szCs w:val="19"/></w:rPr></w:rPrDefault>
        <w:pPrDefault><w:pPr><w:spacing w:after="90" w:line="260" w:lineRule="auto"/></w:pPr></w:pPrDefault>
    </w:docDefaults>
    <w:style w:type="paragraph" w:default="1" w:styleId="Normal">
        <w:name w:val="Normal"/>
        <w:qFormat/>
        <w:pPr><w:widowControl/></w:pPr>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Title">
        <w:name w:val="Title"/>
        <w:basedOn w:val="Normal"/>
        <w:next w:val="Normal"/>
        <w:qFormat/>
        <w:pPr><w:keepNext/><w:spacing w:before="0" w:after="220"/><w:pBdr><w:bottom w:val="single" w:sz="16" w:space="8" w:color="F2B705"/></w:pBdr></w:pPr>
        <w:rPr><w:b/><w:color w:val="111111"/><w:sz w:val="38"/><w:szCs w:val="38"/></w:rPr>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Heading1">
        <w:name w:val="Heading 1"/>
        <w:basedOn w:val="Normal"/>
        <w:next w:val="Normal"/>
        <w:qFormat/>
        <w:pPr><w:keepNext/><w:keepLines/><w:spacing w:before="220" w:after="90"/><w:pBdr><w:bottom w:val="single" w:sz="5" w:space="5" w:color="D7D0C4"/></w:pBdr></w:pPr>
        <w:rPr><w:b/><w:color w:val="0B7C78"/><w:sz w:val="26"/><w:szCs w:val="26"/></w:rPr>
    </w:style>
    <w:style w:type="paragraph" w:styleId="Heading2">
        <w:name w:val="Heading 2"/>
        <w:basedOn w:val="Normal"/>
        <w:next w:val="Normal"/>
        <w:qFormat/>
        <w:pPr><w:keepNext/><w:keepLines/><w:spacing w:before="180" w:after="70"/></w:pPr>
        <w:rPr><w:b/><w:color w:val="8A6500"/><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr>
    </w:style>
</w:styles>
XML;
    }

    private function settings(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:zoom w:percent="90"/>
    <w:defaultTabStop w:val="720"/>
    <w:doNotTrackMoves/>
    <w:doNotTrackFormatting/>
    <w:compat><w:compatSetting w:name="compatibilityMode" w:uri="http://schemas.microsoft.com/office/word" w:val="15"/></w:compat>
</w:settings>
XML;
    }

    private function header(DocxDocument $document): string
    {
        $title = $this->text->xml($document->title);
        $alignment = $document->rightToLeft ? '<w:bidi/><w:jc w:val="right"/>' : '';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:p><w:pPr>'.$alignment
            .'<w:tabs><w:tab w:val="right" w:pos="'.$document->usableWidth.'"/></w:tabs>'
            .'<w:pBdr><w:bottom w:val="single" w:sz="6" w:space="5" w:color="D7D0C4"/></w:pBdr></w:pPr>'
            .'<w:r><w:rPr><w:b/><w:color w:val="F2B705"/><w:sz w:val="18"/></w:rPr><w:t>PMC</w:t></w:r>'
            .'<w:r><w:tab/></w:r>'
            .'<w:r><w:rPr><w:color w:val="666666"/><w:sz w:val="16"/></w:rPr><w:t xml:space="preserve">'
            .$title.'</w:t></w:r></w:p></w:hdr>';
    }

    private function footer(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:p>
        <w:pPr><w:jc w:val="center"/><w:pBdr><w:top w:val="single" w:sz="4" w:space="5" w:color="D7D0C4"/></w:pBdr></w:pPr>
        <w:r><w:rPr><w:color w:val="777777"/><w:sz w:val="15"/></w:rPr><w:t xml:space="preserve">Property Management Control  |  </w:t></w:r>
        <w:fldSimple w:instr="PAGE"><w:r><w:rPr><w:color w:val="777777"/><w:sz w:val="15"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple>
        <w:r><w:rPr><w:color w:val="777777"/><w:sz w:val="15"/></w:rPr><w:t xml:space="preserve"> / </w:t></w:r>
        <w:fldSimple w:instr="NUMPAGES"><w:r><w:rPr><w:color w:val="777777"/><w:sz w:val="15"/></w:rPr><w:t>1</w:t></w:r></w:fldSimple>
    </w:p>
</w:ftr>
XML;
    }

    private function coreProperties(string $title): string
    {
        $created = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            .'xmlns:dcterms="http://purl.org/dc/terms/" '
            .'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$this->text->xml($title).'</dc:title>'
            .'<dc:creator>Property Management Control</dc:creator>'
            .'<cp:lastModifiedBy>Property Management Control</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$created.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function appProperties(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
    <Application>Property Management Control</Application>
    <AppVersion>1.0</AppVersion>
</Properties>
XML;
    }
}
