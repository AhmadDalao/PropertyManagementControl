<?php

namespace App\Modules\Media\Requests;

use App\Models\MediaFile;
use App\Modules\Media\Support\MediaAccess;
use App\Modules\Media\Support\MediaRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $mediaFile = $this->route('mediaFile');

        return $mediaFile instanceof MediaFile
            && app(MediaAccess::class)->canManage($actor, $mediaFile);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return MediaRules::update();
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
