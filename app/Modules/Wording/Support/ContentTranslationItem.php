<?php

namespace App\Modules\Wording\Support;

class ContentTranslationItem
{
    /**
     * @return array{module:string,title:string,subtitle:string,missing:string,href:string}
     */
    public static function make(
        string $module,
        string $title,
        string $subtitle,
        string $missing,
        string $href,
    ): array {
        return compact('module', 'title', 'subtitle', 'missing', 'href');
    }
}
