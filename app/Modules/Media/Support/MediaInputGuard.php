<?php

namespace App\Modules\Media\Support;

use Illuminate\Support\Facades\Validator;

final class MediaInputGuard
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateCreate(array $data): array
    {
        return Validator::make(
            MediaRules::normalize($data),
            MediaRules::create(),
            attributes: MediaRules::attributes(),
        )->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validateUpdate(array $data): array
    {
        return Validator::make(
            MediaRules::normalize($data),
            MediaRules::update(),
            attributes: MediaRules::attributes(),
        )->validate();
    }
}
