<?php

namespace App\Modules\Dashboard\Presenters;

class DashboardActionPresenter
{
    public function __construct(
        private readonly ManagerAssignmentActionPresenter $managerAssignments,
    ) {}

    /**
     * @param  array<int, array{key:string,label:string,description:string,done:bool,href:string,icon:string}>  $checklist
     * @param  array<string, mixed>  $stats
     * @param  array<string, mixed>  $mapSummary
     * @return array<int, array{label:string, description:string, href:string, icon:string}>
     */
    public function operations(
        array $checklist,
        array $stats,
        array $mapSummary,
        ?int $propertyId = null,
        bool $assignmentRestricted = false,
        bool $hasAssignments = true,
    ): array {
        if ($assignmentRestricted && ! $hasAssignments) {
            return $this->managerAssignments->present();
        }

        $actions = [];

        foreach ($checklist as $item) {
            if (! $item['done'] && $item['key'] === 'live_portfolio') {
                $actions[] = $this->setupAction($item);
            }
        }

        if (($stats['hasArrears'] ?? false) === true) {
            $actions[] = $this->action(
                'Collect outstanding rent',
                'Open payment and arrears views before balances get stale.',
                $this->focused('/rent-collection?status=overdue', $propertyId),
                'bi-cash-stack',
            );
        }

        if ((int) ($stats['openRequests'] ?? 0) > 0) {
            $actions[] = $this->action(
                'Triage maintenance backlog',
                'Assign priority, publish tenant updates, and record service cost.',
                $this->focused('/maintenance-requests?status=open', $propertyId),
                'bi-tools',
            );
        }

        if ((int) ($mapSummary['total'] ?? 0) > 0 && (float) ($mapSummary['coverage_percent'] ?? 100) < 100) {
            $actions[] = $this->action(
                'Complete property map',
                trans('app.dashboard.map_action_description', [
                    'positions' => (int) ($mapSummary['needs_position'] ?? 0),
                    'identities' => (int) ($mapSummary['needs_identity'] ?? 0),
                ]),
                $propertyId === null ? '/property-map' : '/assets/'.$propertyId,
                'bi-map',
            );
        }

        foreach ($checklist as $item) {
            if (! $item['done'] && $item['key'] !== 'live_portfolio') {
                $actions[] = $this->setupAction($item);
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

    /**
     * @param  array{label:string,description:string,href:string,icon:string}  $item
     * @return array{label:string, description:string, href:string, icon:string}
     */
    private function setupAction(array $item): array
    {
        return $this->action(
            $item['label'],
            $item['description'],
            $item['href'],
            $item['icon'],
        );
    }

    /** @return array{label:string, description:string, href:string, icon:string} */
    private function action(string $label, string $description, string $href, string $icon): array
    {
        return compact('label', 'description', 'href', 'icon');
    }

    private function focused(string $href, ?int $propertyId): string
    {
        if ($propertyId === null) {
            return $href;
        }

        return $href.(str_contains($href, '?') ? '&' : '?').'property_id='.$propertyId;
    }
}
