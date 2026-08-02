<?php

namespace App\Modules\Exports\Support;

use App\Modules\Exports\Support\Docx\DocxDocumentBuilder;
use App\Modules\Exports\Support\Docx\DocxPackageParts;
use RuntimeException;
use ZipArchive;

final readonly class SimpleDocx
{
    public function __construct(
        private DocxDocumentBuilder $documents,
        private DocxPackageParts $parts,
    ) {}

    /**
     * @param  array<int, array{type:string,text?:string,style?:string,rows?:array<int,array<int,string>>}>  $blocks
     * @param  array{orientation?:'auto'|'portrait'|'landscape',title?:string}  $options
     */
    public function create(array $blocks, array $options = []): string
    {
        throw_unless(
            class_exists(ZipArchive::class),
            RuntimeException::class,
            'The PHP zip extension is required to create Word files.',
        );

        $path = tempnam(sys_get_temp_dir(), 'pmc-word-');
        throw_if($path === false, RuntimeException::class, 'Unable to create a temporary Word file.');

        $document = $this->documents->build($blocks, $options);
        $zip = new ZipArchive;
        throw_if(
            $zip->open($path, ZipArchive::OVERWRITE) !== true,
            RuntimeException::class,
            'Unable to create the Word archive.',
        );

        foreach ($this->parts->all($document) as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $path;
    }
}
