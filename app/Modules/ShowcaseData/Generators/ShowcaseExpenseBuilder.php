<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\ExpenseEntry;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Expenses\Support\ExpenseOptions;

class ShowcaseExpenseBuilder
{
    /** @param list<MaintenanceRequest> $maintenance */
    public function build(
        Portfolio $portfolio,
        User $manager,
        Asset $building,
        array $maintenance,
        int $buildingIndex,
    ): void {
        foreach (ExpenseOptions::SHOWCASE_CATEGORIES as $index => $category) {
            ExpenseEntry::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'title' => sprintf('SHOW-B%03d expense %02d', $buildingIndex + 1, $index + 1),
                ],
                [
                    'asset_id' => $building->id,
                    'lease_id' => null,
                    'maintenance_request_id' => $index < 3 ? $maintenance[$index]->id : null,
                    'created_by_user_id' => $manager->id,
                    'category' => $category,
                    'description' => 'Tagged showcase operating expense.',
                    'incurred_on' => now()->subDays(($buildingIndex * 3) + ($index * 4))->toDateString(),
                    'amount' => 450 + ($index * 325) + ($buildingIndex * 15),
                    'currency' => 'SAR',
                    'vendor_name' => 'Showcase Vendor '.($index + 1),
                    'status' => 'posted',
                    'meta_json' => ['showcase' => true],
                ],
            );
        }
    }
}
