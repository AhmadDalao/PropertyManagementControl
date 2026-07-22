<?php

namespace App\Modules\Documentation\Support;

class DocumentationConfiguration
{
    /** @var list<string> */
    private const COLLECTIONS = [
        'quick_starts',
        'workflows',
        'page_shortcuts',
        'control_checks',
        'role_guides',
        'guides',
    ];

    public function supports(string $collection): bool
    {
        return in_array($collection, self::COLLECTIONS, true);
    }

    /** @return list<array<string, mixed>> */
    public function items(string $collection): array
    {
        $configured = config("property_docs.{$collection}", []);

        if (! is_array($configured)) {
            return [];
        }

        $items = [];

        foreach ($configured as $item) {
            if (is_array($item)) {
                $items[] = $this->stringKeyed($item);
            }
        }

        return $items;
    }

    /**
     * @param  array<array-key, mixed>  $item
     * @return array<string, mixed>
     */
    private function stringKeyed(array $item): array
    {
        $normalized = [];

        foreach ($item as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
