<?php

namespace App\Modules\Documents\Support;

use Illuminate\Support\Facades\Validator;

final class DocumentInputGuard
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateCreate(array $data): array
    {
        return Validator::make(
            DocumentRules::normalize($data),
            DocumentRules::create(),
            attributes: DocumentRules::attributes(),
        )->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateUpdate(array $data): array
    {
        return Validator::make(
            DocumentRules::normalize($data),
            DocumentRules::update(),
            attributes: DocumentRules::attributes(),
        )->validate();
    }
}
