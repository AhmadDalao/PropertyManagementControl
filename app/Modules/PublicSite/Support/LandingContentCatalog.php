<?php

namespace App\Modules\PublicSite\Support;

use LogicException;

class LandingContentCatalog
{
    /** @return array<string, mixed> */
    public function page(): array
    {
        return $this->getArray('page');
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        return array_values($this->getArray('sections'));
    }

    /** @return array<int, array<string, mixed>> */
    public function navigation(): array
    {
        return array_values($this->getArray('navigation'));
    }

    /** @return array<string, mixed> */
    public function fallbackPage(): array
    {
        $page = $this->page();
        $page['id'] = 0;
        $page['page_sections'] = array_map(
            fn (array $section, int $index): array => [
                'id' => -$index - 1,
                'cms_page_id' => 0,
                'cms_section_id' => -$index - 1,
                'sort_order' => $index + 1,
                'is_visible' => true,
                'section' => [
                    'id' => -$index - 1,
                    'section_type' => $section['section_type'],
                    'name_en' => $section['name_en'],
                    'name_ar' => $section['name_ar'],
                    'content_en' => $section['content_en'],
                    'content_ar' => $section['content_ar'],
                    'status' => 'active',
                ],
            ],
            $this->sections(),
            array_keys($this->sections()),
        );

        return $page;
    }

    /** @return array<mixed> */
    private function getArray(string $key): array
    {
        $value = config("public-site.{$key}");

        if (! is_array($value)) {
            throw new LogicException("Public site configuration [{$key}] must be an array.");
        }

        return $value;
    }
}
