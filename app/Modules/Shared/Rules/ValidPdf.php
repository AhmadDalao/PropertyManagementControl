<?php

namespace App\Modules\Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ValidPdf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            return;
        }

        $path = $value->getRealPath();
        $header = $path ? file_get_contents($path, false, null, 0, 1024) : false;

        if ($header === false || ! str_contains($header, '%PDF-')) {
            $fail(trans('app.errors.invalid_pdf'));
        }
    }
}
