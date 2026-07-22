<?php

namespace App\Modules\Media\Requests;

use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(MediaAccess::class)->canCreate($this->user());
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return MediaRules::create();
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return MediaRules::attributes();
    }

    protected function prepareForValidation(): void
    {
        $this->replace(MediaRules::normalize($this->all()));
    }
}
