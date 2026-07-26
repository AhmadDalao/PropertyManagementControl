<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\Portfolio;
use App\Models\User;

final class ShowcaseWorkOrderBuilder
{
    /**
     * @param  list<MaintenanceRequest>  $requests
     * @return list<MaintenanceWorkOrder>
     */
    public function build(
        Portfolio $portfolio,
        User $manager,
        array $requests,
        int $buildingIndex,
    ): array {
        $items = [];

        foreach ($requests as $index => $request) {
            $category = $this->category($request->category);
            $vendor = MaintenanceVendor::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'name' => 'SHOW '.str($category)->replace('_', ' ')->headline().' Services',
                ],
                [
                    'contact_name' => 'Showcase Dispatch',
                    'phone' => sprintf('+966590%06d', ($portfolio->id * 10) + $index),
                    'email' => "dispatch.{$category}.p{$portfolio->id}@showcase.invalid",
                    'service_category' => $category,
                    'status' => 'active',
                    'notes' => 'Tagged showcase contractor for work-order scale testing.',
                    'meta_json' => ['showcase' => true],
                ],
            );
            $status = $this->status($request->status, $index);
            $scheduledAt = $status === 'draft'
                ? null
                : now()->addDays(($index % 5) + 1)->setTime(9 + ($index % 7), 30);
            $completed = $status === 'completed';

            $items[] = MaintenanceWorkOrder::query()->updateOrCreate(
                [
                    'maintenance_request_id' => $request->id,
                    'reference_code' => sprintf(
                        'SHOW-WO-D%03d-B%03d-%02d',
                        $portfolio->showcase_dataset_id,
                        $buildingIndex + 1,
                        $index + 1,
                    ),
                ],
                [
                    'portfolio_id' => $portfolio->id,
                    'vendor_id' => $vendor->id,
                    'created_by_user_id' => $manager->id,
                    'assigned_to_user_id' => $manager->id,
                    'vendor_name' => $vendor->name,
                    'vendor_phone' => $vendor->phone,
                    'status' => $status,
                    'scheduled_at' => $scheduledAt,
                    'completed_at' => $completed ? now()->subDays(($index % 4) + 1) : null,
                    'estimated_amount' => 250 + (($buildingIndex + $index) * 25),
                    'final_amount' => $completed ? 300 + (($buildingIndex + $index) * 25) : null,
                    'currency' => $portfolio->default_currency ?: 'SAR',
                    'scope' => 'Inspect the reported issue, complete the required repair, and verify normal operation.',
                    'completion_notes' => $completed
                        ? 'Showcase repair completed, tested, and handed back to the tenant.'
                        : null,
                    'tenant_access_required' => $index % 2 === 0,
                    'meta_json' => ['showcase' => true],
                ],
            );
        }

        return $items;
    }

    private function category(string $category): string
    {
        return match ($category) {
            'electrical', 'electricity' => 'electricity',
            'hvac', 'ac' => 'ac',
            'plumbing' => 'plumbing',
            default => 'general',
        };
    }

    private function status(string $requestStatus, int $index): string
    {
        return match ($requestStatus) {
            'resolved' => 'completed',
            'in_progress' => 'in_progress',
            'cancelled' => 'cancelled',
            default => $index % 2 === 0 ? 'draft' : 'scheduled',
        };
    }
}
