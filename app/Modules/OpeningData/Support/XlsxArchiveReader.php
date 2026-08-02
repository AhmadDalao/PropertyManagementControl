<?php

namespace App\Modules\OpeningData\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

final class XlsxArchiveReader
{
    private const MAX_XML_BYTES = 25_000_000;

    /** @return array<string, string> */
    public function sheetPaths(ZipArchive $zip): array
    {
        $workbook = $this->document($zip, 'xl/workbook.xml');
        $relationships = $this->document($zip, 'xl/_rels/workbook.xml.rels');
        $targets = [];

        foreach ($this->nodes($relationships, '//*[local-name()="Relationship"]') as $relationship) {
            if ($relationship instanceof DOMElement) {
                $targets[$relationship->getAttribute('Id')] = $relationship->getAttribute('Target');
            }
        }

        $paths = [];

        foreach ($this->nodes($workbook, '//*[local-name()="sheets"]/*[local-name()="sheet"]') as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }

            $relationshipId = $sheet->getAttributeNS(
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                'id',
            );
            $target = $targets[$relationshipId] ?? null;

            if (is_string($target) && $target !== '') {
                $paths[$sheet->getAttribute('name')] = $this->worksheetPath($target);
            }
        }

        return $paths;
    }

    /** @param array<string, string> $sheetPaths */
    public function matchingSheetPath(array $sheetPaths, string $requiredSheet): ?string
    {
        $needle = mb_strtolower(trim($requiredSheet));

        foreach ($sheetPaths as $name => $path) {
            if (mb_strtolower(trim($name)) === $needle) {
                return $path;
            }
        }

        return null;
    }

    /** @return array<int, string> */
    public function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $strings = [];

        foreach ($this->nodes($this->document($zip, 'xl/sharedStrings.xml'), '//*[local-name()="si"]') as $item) {
            if ($item instanceof DOMElement) {
                $strings[] = $this->textContent($item);
            }
        }

        return $strings;
    }

    /** @return array<int, bool> */
    public function dateStyles(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/styles.xml') === false) {
            return [];
        }

        $document = $this->document($zip, 'xl/styles.xml');
        $customFormats = [];

        foreach ($this->nodes($document, '//*[local-name()="numFmts"]/*[local-name()="numFmt"]') as $format) {
            if ($format instanceof DOMElement) {
                $customFormats[(int) $format->getAttribute('numFmtId')] = $format->getAttribute('formatCode');
            }
        }

        $styles = [];

        foreach ($this->nodes($document, '//*[local-name()="cellXfs"]/*[local-name()="xf"]') as $index => $format) {
            if (! $format instanceof DOMElement) {
                continue;
            }

            $numberFormat = (int) $format->getAttribute('numFmtId');
            $code = $customFormats[$numberFormat] ?? '';

            if (in_array($numberFormat, range(14, 22), true)
                || in_array($numberFormat, range(45, 47), true)
                || $this->looksLikeDateFormat($code)) {
                $styles[(int) $index] = true;
            }
        }

        return $styles;
    }

    public function document(ZipArchive $zip, string $path): DOMDocument
    {
        $stat = $zip->statName($path);

        if (! is_array($stat) || $stat['size'] > self::MAX_XML_BYTES) {
            throw new RuntimeException($this->message('app.opening_data.errors.workbook_too_large'));
        }

        $xml = $zip->getFromName($path);

        if (! is_string($xml) || $xml === '') {
            throw new RuntimeException($this->message('app.opening_data.errors.invalid_workbook'));
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException($this->message('app.opening_data.errors.invalid_workbook'));
        }

        return $document;
    }

    /** @return array<int, DOMNode> */
    public function nodes(DOMDocument $document, string $expression): array
    {
        $nodes = (new DOMXPath($document))->query($expression);
        $result = [];

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if ($node instanceof DOMNode) {
                    $result[] = $node;
                }
            }
        }

        return $result;
    }

    public function textContent(DOMElement $element): string
    {
        $text = '';
        $walker = function (DOMNode $node) use (&$walker, &$text): void {
            if ($node instanceof DOMElement && $node->localName === 't') {
                $text .= $node->textContent;

                return;
            }

            foreach ($node->childNodes as $child) {
                $walker($child);
            }
        };
        $walker($element);

        return $text;
    }

    private function worksheetPath(string $target): string
    {
        $target = ltrim(str_replace('\\', '/', $target), '/');

        if (str_starts_with($target, 'xl/')) {
            return $target;
        }

        while (str_starts_with($target, '../')) {
            $target = substr($target, 3);
        }

        return 'xl/'.$target;
    }

    private function looksLikeDateFormat(string $code): bool
    {
        $code = preg_replace('/"[^"]*"|\[[^\]]*\]|\\\\./', '', mb_strtolower($code)) ?? '';

        return preg_match('/(?:y+.*m+.*d+|d+.*m+.*y+)/', $code) === 1;
    }

    /** @param array<string, scalar> $replace */
    private function message(string $key, array $replace = []): string
    {
        $message = trans($key, $replace);

        return is_string($message) ? $message : $key;
    }
}
