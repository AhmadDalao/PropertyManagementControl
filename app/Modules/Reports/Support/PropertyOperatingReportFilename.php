<?php

namespace App\Modules\Reports\Support;

use Illuminate\Support\Str;

final class PropertyOperatingReportFilename
{
    /** @param array<string, mixed> $data */
    public function make(array $data, string $extension): string
    {
        $property = is_array($data['property'] ?? null) ? $data['property'] : [];
        $identity = Str::slug((string) ($property['code'] ?? ''))
            ?: 'property-'.(int) ($property['id'] ?? 0);

        return "property-operating-report-{$identity}-"
            .now()->format('Ymd-His')
            .'.'.$extension;
    }
}
