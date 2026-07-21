<?php

namespace App\Modules\Dashboard\Presenters;

class DashboardActionPresenter
{
    /**
     * @param  array<int, array{label:string, done:bool, href:string}>  $checklist
     * @param  array<string, int|float>  $stats
     * @param  array<string, mixed>  $mapSummary
     * @return array<int, array{label:string, description:string, href:string, icon:string}>
     */
    public function operations(array $checklist, array $stats, array $mapSummary): array
    {
        $actions = [];

        if ((float) ($stats['arrears'] ?? 0) > 0) {
            $actions[] = $this->action(
                'Collect outstanding rent',
                'Open payment and arrears views before balances get stale.',
                '/payments',
                'bi-cash-stack',
            );
        }

        if ((int) ($stats['openRequests'] ?? 0) > 0) {
            $actions[] = $this->action(
                'Triage maintenance backlog',
                'Assign priority, publish tenant updates, and record service cost.',
                '/maintenance-requests',
                'bi-tools',
            );
        }

        if ((int) ($mapSummary['total'] ?? 0) > 0 && (float) ($mapSummary['coverage_percent'] ?? 100) < 100) {
            $actions[] = $this->action(
                'Complete property map',
                sprintf(
                    'Fix %d missing positions and %d missing zone/land labels before relying on the owner map.',
                    (int) ($mapSummary['needs_position'] ?? 0),
                    (int) ($mapSummary['needs_identity'] ?? 0),
                ),
                '/property-map',
                'bi-map',
            );
        }

        foreach ($checklist as $item) {
            if (! $item['done']) {
                $actions[] = $this->setupAction($item['label'], $item['href']);
            }
        }

        $actions[] = $this->action(
            'Open operating manual',
            'Use workflows, page shortcuts, and control checks before changing production data.',
            '/documentation',
            'bi-journal-richtext',
        );

        return array_slice($actions, 0, 4);
    }

    /** @return array<int, array{label:string, description:string, href:string, icon:string}> */
    public function tenant(bool $hasLease): array
    {
        if (! $hasLease) {
            return [
                $this->action('Wait for lease activation', 'Your owner or manager needs to assign a lease before rent and documents appear.', '/documentation', 'bi-hourglass-split'),
                $this->action('Read tenant guide', 'Learn how payments, documents, and maintenance requests work in this portal.', '/documentation', 'bi-journal-richtext'),
            ];
        }

        return [
            $this->action('Download contract', 'Keep a copy of your current lease contract and tenant statement.', '/dashboard', 'bi-file-earmark-arrow-down'),
            $this->action('Submit maintenance request', 'Report electrical, plumbing, HVAC, or general issues from the service queue.', '/maintenance-requests', 'bi-tools'),
            $this->action('Review tenant guide', 'Check what you can see, download, and request from your portal.', '/documentation', 'bi-journal-richtext'),
        ];
    }

    /** @return array{label:string, description:string, href:string, icon:string} */
    private function setupAction(string $label, string $href): array
    {
        $details = [
            'Create portfolio' => ['Set the owner account boundary before adding data.', 'bi-buildings'],
            'Create users' => ['Add owner, manager, and tenant accounts with clean roles.', 'bi-people'],
            'Create assets' => ['Build buildings, floors, units, spaces, and stakeholder assignments.', 'bi-diagram-3'],
            'Create profiles' => ['Create tenant profiles before writing contracts.', 'bi-person-badge'],
            'Create leases' => ['Connect tenants to rentable assets and generate installments.', 'bi-file-earmark-text'],
            'Publish website' => ['Use the CMS builder to publish the public landing page.', 'bi-layout-wtf'],
        ];
        [$description, $icon] = $details[$label] ?? ['Complete this setup step before scaling operations.', 'bi-arrow-right-circle'];

        return $this->action($label, $description, $href, $icon);
    }

    /** @return array{label:string, description:string, href:string, icon:string} */
    private function action(string $label, string $description, string $href, string $icon): array
    {
        return compact('label', 'description', 'href', 'icon');
    }
}
