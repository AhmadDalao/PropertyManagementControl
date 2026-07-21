<?php

namespace App\Support;

use ArPHP\I18N\Arabic;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;

class BilingualPdf
{
    public function __construct(private readonly Arabic $arabic) {}

    /**
     * @param  view-string  $view
     * @param  array<string, mixed>  $data
     */
    public function loadView(string $view, array $data = []): DomPdfDocument
    {
        $html = view($view, $data)->render();

        return Pdf::loadHTML($this->shapeArabicTextNodes($html));
    }

    public function shapeArabicTextNodes(string $html): string
    {
        return preg_replace_callback(
            '/>([^<]*\p{Arabic}[^<]*)</u',
            fn (array $matches): string => '>'.$this->shapeTextNode($matches[1]).'<',
            $html,
        ) ?? $html;
    }

    private function shapeTextNode(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // DomPDF does not perform Arabic glyph joining, so provide visual-order glyphs.
        $shaped = @$this->arabic->utf8Glyphs($decoded, 10000, false, false);

        return htmlspecialchars($shaped, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
