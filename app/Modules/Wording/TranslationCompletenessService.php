<?php

namespace App\Modules\Wording;

use App\Modules\Wording\Queries\CmsContentTranslationQuery;
use App\Modules\Wording\Queries\DocumentMediaContentTranslationQuery;
use App\Modules\Wording\Queries\PropertyContentTranslationQuery;
use App\Modules\Wording\Queries\ReportPresetContentTranslationQuery;
use Illuminate\Support\Collection;

class TranslationCompletenessService
{
    public const MODULES = [
        'portfolios',
        'assets',
        'documents',
        'media',
        'cms_pages',
        'cms_sections',
        'navigation',
        'report_presets',
    ];

    /** @var Collection<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>|null */
    private ?Collection $items = null;

    public function __construct(
        private readonly PropertyContentTranslationQuery $properties,
        private readonly DocumentMediaContentTranslationQuery $documents,
        private readonly CmsContentTranslationQuery $cms,
        private readonly ReportPresetContentTranslationQuery $reports,
    ) {}

    /**
     * @return Collection<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    public function missing(?string $module = null): Collection
    {
        return $this->all()
            ->when(
                $module && $module !== 'all',
                fn (Collection $items) => $items->where('module', $module),
            )
            ->values();
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return $this->all()
            ->countBy('module')
            ->map(fn (int $count): int => $count)
            ->all();
    }

    /**
     * @return array{items:Collection<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>,total:int,counts:array<string, int>,modules:list<string>}
     */
    public function summary(string $module): array
    {
        $missing = $this->missing($module);

        return [
            'items' => $missing->take(500)->values(),
            'total' => $missing->count(),
            'counts' => $this->counts(),
            'modules' => self::MODULES,
        ];
    }

    /**
     * @return Collection<int, array{module:string,title:string,subtitle:string,missing:string,href:string}>
     */
    private function all(): Collection
    {
        return $this->items ??= collect([
            ...$this->properties->items(),
            ...$this->documents->items(),
            ...$this->cms->items(),
            ...$this->reports->items(),
        ])->sortBy(['module', 'title'])->values();
    }
}
