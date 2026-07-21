<?php

namespace App\Modules\Maintenance\Presenters;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\User;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Support\Collection;

class MaintenanceDetailPresenter
{
    public function __construct(private readonly ResourcePresenter $resources) {}

    /**
     * @return array<string, mixed>
     */
    public function present(MaintenanceRequest $request, User $actor): array
    {
        $tenantMode = $actor->hasRole('tenant');
        $relations = [
            'portfolio',
            'asset',
            'lease',
            'tenantProfile.user',
            'submittedBy',
            'assignedTo',
            'updates.user',
        ];

        if (! $tenantMode) {
            $relations[] = 'expenses';
        }

        $request->loadMissing($relations);
        $updates = $request->updates
            ->when($tenantMode, fn ($items) => $items->where('is_public_comment', true))
            ->sortByDesc('created_at')
            ->values();
        $related = [$this->updatesPanel($updates, $tenantMode)];

        if (! $tenantMode) {
            $related[] = $this->expensesPanel($request);
        }
        $actions = [[
            'label' => $tenantMode ? 'Add comment' : 'Triage request',
            'href' => route('maintenance-requests.edit', $request),
            'variant' => 'primary',
        ]];
        $stats = [
            [
                'label' => 'Status',
                'value' => $request->status,
                'tone' => $request->status === 'resolved' ? 'teal' : 'primary',
            ],
            [
                'label' => 'Priority',
                'value' => $request->priority,
                'tone' => in_array($request->priority, ['high', 'urgent'], true) ? 'danger' : 'muted',
            ],
            ['label' => 'Updates', 'value' => $updates->count()],
        ];

        if (! $tenantMode && $request->asset) {
            $actions[] = [
                'label' => 'Open asset',
                'href' => route('assets.show', $request->asset),
                'variant' => 'secondary',
            ];
        }

        if (! $tenantMode) {
            $stats[] = [
                'label' => 'Cost',
                'value' => number_format((float) $request->expenses->sum('amount'), 2),
                'tone' => 'primary',
            ];
        }

        return [
            'header' => [
                'eyebrow' => 'Maintenance detail',
                'title' => '#'.$request->id.' '.$request->title,
                'description' => trim($request->category.' · '.$request->priority.' · '.$request->status),
                'backHref' => route('maintenance-requests.index'),
                'backLabel' => 'Maintenance queue',
                'actions' => $actions,
            ],
            'stats' => $this->resources->detailItems($stats),
            'sections' => [[
                'title' => 'Request',
                'description' => 'Problem, people, asset, and SLA context.',
                'items' => $this->resources->detailItems([
                    [
                        'label' => 'Asset',
                        'value' => $this->resources->localized($request->asset?->title_en, $request->asset?->title_ar),
                        'href' => ! $tenantMode && $request->asset ? route('assets.show', $request->asset) : null,
                    ],
                    [
                        'label' => 'Tenant',
                        'value' => $request->tenantProfile?->user?->name,
                        'href' => ! $tenantMode && $request->tenantProfile
                            ? route('tenants.show', $request->tenantProfile)
                            : null,
                    ],
                    [
                        'label' => 'Lease',
                        'value' => $request->lease?->code,
                        'href' => $request->lease ? route('leases.show', $request->lease) : null,
                    ],
                    ['label' => 'Submitted by', 'value' => $request->submittedBy?->name],
                    ['label' => 'Assigned to', 'value' => $request->assignedTo?->name],
                    ['label' => 'Requested at', 'value' => $request->requested_at?->toDateTimeString()],
                    ['label' => 'Due at', 'value' => $request->due_at?->toDateTimeString()],
                    ['label' => 'Resolved at', 'value' => $request->resolved_at?->toDateTimeString()],
                    ['label' => 'Description', 'value' => $request->description],
                    ['label' => 'Internal notes', 'value' => $tenantMode ? null : $request->internal_notes],
                ]),
            ]],
            'related' => $related,
            'documents' => [],
            'timeline' => $this->resources->activityTimeline($request),
        ];
    }

    /**
     * @param  Collection<int, MaintenanceUpdate>  $updates
     * @return array<string, mixed>
     */
    private function updatesPanel(Collection $updates, bool $tenantMode): array
    {
        return [
            'title' => 'Updates',
            'description' => $tenantMode
                ? 'Comments and status updates shared with you.'
                : 'Public comments, internal notes, and status transitions.',
            'columns' => ['By', 'From', 'To', 'Comment'],
            'rows' => $updates->map(fn (MaintenanceUpdate $update) => [
                'By' => $update->user->name ?? 'System',
                'From' => $update->status_from ?? '-',
                'To' => $update->status_to ?? '-',
                'Comment' => $update->comment,
            ])->all(),
            'emptyText' => 'No updates yet.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function expensesPanel(MaintenanceRequest $request): array
    {
        return [
            'title' => 'Expenses',
            'description' => 'Maintenance costs linked to this request.',
            'columns' => ['Expense', 'Vendor', 'Status', 'Amount'],
            'rows' => $request->expenses->map(fn ($expense) => [
                'Expense' => $expense->title,
                'Vendor' => $expense->vendor_name ?? '-',
                'Status' => $expense->status,
                'Amount' => number_format((float) $expense->amount, 2).' '.$expense->currency,
            ])->all(),
            'emptyText' => 'No expenses linked yet.',
            'actionHref' => route('expenses.create', ['maintenance_request_id' => $request->id]),
            'actionLabel' => 'Add expense',
        ];
    }
}
