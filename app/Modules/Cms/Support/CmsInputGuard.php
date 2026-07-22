<?php

namespace App\Modules\Cms\Support;

use App\Models\CmsPage;
use Illuminate\Support\Facades\Validator;

final class CmsInputGuard
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function page(array $data, ?CmsPage $page = null): array
    {
        return $this->validate(CmsRules::normalizePage($data), CmsRules::page($page));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function section(array $data): array
    {
        return $this->validate(CmsRules::normalizeSection($data), CmsRules::section());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function navigation(array $data): array
    {
        return $this->validate(CmsRules::normalizeNavigation($data), CmsRules::navigation());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function attachment(array $data): array
    {
        return $this->validate($data, CmsRules::attachment());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function pageSection(array $data): array
    {
        return $this->validate($data, CmsRules::pageSection());
    }

    /**
     * @param  array<int|string, mixed>  $orderedIds
     * @return array<int, int>
     */
    public function reorder(array $orderedIds): array
    {
        $validated = $this->validate(['ordered_ids' => $orderedIds], CmsRules::reorder());

        return array_map('intval', $validated['ordered_ids']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array<int, mixed>>  $rules
     * @return array<string, mixed>
     */
    private function validate(array $data, array $rules): array
    {
        return Validator::make(
            $data,
            $rules,
            attributes: CmsRules::attributes(),
        )->validate();
    }
}
