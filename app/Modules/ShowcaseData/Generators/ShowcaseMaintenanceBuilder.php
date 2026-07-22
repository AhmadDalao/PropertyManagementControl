<?php

namespace App\Modules\ShowcaseData\Generators;

use App\Models\Asset;
use App\Models\Lease;
use App\Models\MaintenanceRequest;
use App\Models\Portfolio;
use App\Models\TenantProfile;
use App\Models\User;

class ShowcaseMaintenanceBuilder
{
    /**
     * @param  list<array{lease:Lease, tenant:TenantProfile, unit:Asset, user:User}>  $records
     * @return list<MaintenanceRequest>
     */
    public function build(
        Portfolio $portfolio,
        User $manager,
        array $records,
        int $buildingIndex,
    ): array {
        $items = [];
        $categories = ['electrical', 'plumbing', 'hvac', 'appliance'];
        $statuses = ['open', 'in_progress', 'resolved', 'open'];
        $priorities = ['low', 'medium', 'high', 'urgent'];

        for ($index = 0; $index < 8; $index++) {
            $record = $records[$index];
            $status = $statuses[$index % count($statuses)];
            $request = MaintenanceRequest::query()->updateOrCreate(
                [
                    'portfolio_id' => $portfolio->id,
                    'asset_id' => $record['unit']->id,
                    'title' => sprintf('SHOW-B%03d maintenance %02d', $buildingIndex + 1, $index + 1),
                ],
                [
                    'lease_id' => $record['lease']->id,
                    'tenant_profile_id' => $record['tenant']->id,
                    'submitted_by_user_id' => $record['user']->id,
                    'assigned_to_user_id' => $manager->id,
                    'category' => $categories[$index % count($categories)],
                    'priority' => $priorities[$index % count($priorities)],
                    'status' => $status,
                    'description' => 'Showcase service request used to test queues, filters, and reports.',
                    'requested_at' => now()->subDays(($buildingIndex * 2) + $index),
                    'due_at' => now()->addDays(($index % 5) + 1),
                    'resolved_at' => $status === 'resolved' ? now()->subDay() : null,
                    'internal_notes' => 'Tagged showcase request.',
                    'meta_json' => ['showcase' => true],
                ],
            );
            $request->updates()->updateOrCreate(
                ['user_id' => $manager->id, 'comment' => 'Showcase request reviewed by property management.'],
                ['status_from' => null, 'status_to' => $status, 'is_public_comment' => true],
            );
            $items[] = $request;
        }

        return $items;
    }
}
