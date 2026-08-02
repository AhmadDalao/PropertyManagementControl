<?php

namespace App\Modules\Exports\Support\Docx;

final class DocxText
{
    public function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    public function isRightToLeft(string $value): bool
    {
        return preg_match('/\p{Arabic}/u', $value) === 1
            && preg_match('/[A-Za-z]/', $value) !== 1;
    }
}
