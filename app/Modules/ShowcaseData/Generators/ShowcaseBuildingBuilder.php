<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Models\User;
use App\Modules\ShowcaseData\Support\ShowcaseLocations;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Illuminate\Support\Str;

class ShowcaseBuildingBuilder
{
    public function __construct(
        private readonly ShowcaseLocations $locations,
        private readonly ShowcaseTargets $targets,
    ) {}

    public function build(
        ShowcaseDataset $dataset,
        Portfolio $portfolio,
        User $owner,
        User $manager,
        int $index,
    ): Asset {
        $location = $this->locations->forBuilding($index);
        $number = $index + 1;
        $usage = match ($index % 5) {
            0 => 'commercial',
            1 => 'mixed',
            default => 'residential',
        };
        $building = Asset::query()->updateOrCreate(
            ['code' => $this->targets->buildingCode($dataset, $index)],
            [
                'portfolio_id' => $portfolio->id,
                'parent_id' => null,
                'asset_type' => 'building',
                'usage_type' => $usage,
                'title_en' => "{$location['city_en']} Operations Building {$number}",
                'title_ar' => "مبنى العمليات {$number} - {$location['city_ar']}",
                'slug' => Str::slug("{$dataset->key}-building-{$number}"),
                'status' => 'active',
                'occupancy_status' => 'partially_occupied',
                'rentable' => false,
                'valuation_amount' => 8_500_000 + ($index * 175_000),
                'currency' => 'SAR',
                'area' => 2_400 + ($index * 10),
                'sort_order' => $index,
                'address' => "{$number} {$location['address_en']}",
                'address_ar' => "{$location['address_ar']}، مبنى {$number}",
                'description_en' => 'Tagged showcase building for property operations scale testing.',
                'description_ar' => 'مبنى تجريبي موسوم لاختبار عمليات إدارة العقارات تحت الحمل.',
                'meta_json' => [
                    'map' => [
                        'zone' => $location['zone_en'],
                        'zone_en' => $location['zone_en'],
                        'zone_ar' => $location['zone_ar'],
                        'land_number' => sprintf('%s-%03d', $location['land_prefix'], $number),
                        'latitude' => (float) $location['latitude'] + ((($index % 8) - 3.5) * 0.011),
                        'longitude' => (float) $location['longitude'] + ((($index % 7) - 3) * 0.012),
                    ],
                ],
            ],
        );

        $building->stakeholders()->updateOrCreate(
            ['user_id' => $owner->id, 'relationship_type' => 'owner'],
            [
                'portfolio_id' => $portfolio->id,
                'is_primary' => true,
                'starts_on' => now()->subYears(2)->toDateString(),
            ],
        );
        $building->stakeholders()->updateOrCreate(
            ['user_id' => $manager->id, 'relationship_type' => 'manager'],
            [
                'portfolio_id' => $portfolio->id,
                'is_primary' => true,
                'starts_on' => now()->subYear()->toDateString(),
            ],
        );

        return $building;
    }
}
