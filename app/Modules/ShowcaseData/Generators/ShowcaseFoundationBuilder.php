<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Portfolio;
use App\Models\ShowcaseDataset;
use App\Modules\ShowcaseData\Support\ShowcaseLocations;
use App\Modules\ShowcaseData\Support\ShowcaseTargets;
use Illuminate\Support\Str;

class ShowcaseFoundationBuilder
{
    public function __construct(
        private readonly ShowcaseLocations $locations,
        private readonly ShowcaseTargets $targets,
        private readonly ShowcaseUserFactory $users,
    ) {}

    public function build(ShowcaseDataset $dataset): void
    {
        foreach ($this->locations->all() as $portfolioIndex => $location) {
            $portfolio = Portfolio::query()->updateOrCreate(
                ['code' => $this->targets->portfolioCode($dataset, $portfolioIndex)],
                [
                    'showcase_dataset_id' => $dataset->id,
                    'owner_user_id' => null,
                    'name_en' => "{$location['city_en']} Showcase Portfolio",
                    'name_ar' => "محفظة {$location['city_ar']} التجريبية",
                    'slug' => Str::slug("{$dataset->key}-{$location['city_en']}"),
                    'status' => 'active',
                    'contact_email' => 'portfolio.p'.($portfolioIndex + 1).".d{$dataset->id}@showcase.invalid",
                    'contact_phone' => '+966500'.str_pad((string) $portfolioIndex, 6, '0', STR_PAD_LEFT),
                    'city' => $location['city_en'],
                    'country' => 'Saudi Arabia',
                    'address' => $location['address_en'],
                    'address_ar' => $location['address_ar'],
                    'default_currency' => 'SAR',
                    'module_settings' => $this->targets->moduleSettings(),
                ],
            );

            $owner = $this->users->make(
                $dataset,
                $portfolio,
                'owner.p'.($portfolioIndex + 1).".d{$dataset->id}@showcase.invalid",
                'Showcase Owner '.($portfolioIndex + 1),
                'owner',
                $portfolioIndex * 10,
            );
            $portfolio->update(['owner_user_id' => $owner->id]);

            for ($managerIndex = 0; $managerIndex < ShowcaseTargets::MANAGERS_PER_PORTFOLIO; $managerIndex++) {
                $this->users->make(
                    $dataset,
                    $portfolio,
                    'manager.p'.($portfolioIndex + 1).'.m'.($managerIndex + 1).".d{$dataset->id}@showcase.invalid",
                    'Showcase Manager '.($portfolioIndex + 1).'-'.($managerIndex + 1),
                    'property_manager',
                    ($portfolioIndex * 10) + $managerIndex + 1,
                );
            }
        }
    }
}
