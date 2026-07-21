<?php

namespace App\Modules\Audit\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AuditSubjectRegistry
{
    /** @var array<int, string> */
    private const SUBJECTS = [
        'portfolio',
        'user',
        'asset',
        'asset_stakeholder',
        'tenant_profile',
        'lease',
        'lease_installment',
        'payment',
        'payment_allocation',
        'maintenance_request',
        'maintenance_update',
        'expense_entry',
        'document',
        'media_file',
        'report_preset',
        'label_override',
        'cms_page',
        'cms_section',
        'cms_page_section',
        'navigation_item',
    ];

    /** @var array<int, string> */
    private const PORTFOLIO_SUBJECTS = [
        'portfolio',
        'user',
        'asset',
        'asset_stakeholder',
        'tenant_profile',
        'lease',
        'lease_installment',
        'payment',
        'payment_allocation',
        'maintenance_request',
        'maintenance_update',
        'expense_entry',
        'document',
        'media_file',
        'report_preset',
        'label_override',
    ];

    /** @return array<int, string> */
    public function aliases(): array
    {
        return self::SUBJECTS;
    }

    /** @return array<int, string> */
    public function aliasesFor(User $actor): array
    {
        return $actor->hasRole('superadmin')
            ? self::SUBJECTS
            : self::PORTFOLIO_SUBJECTS;
    }

    /** @return array<int, string> */
    public function typeValues(string $alias): array
    {
        $model = Relation::getMorphedModel($alias);

        return $model === null ? [$alias] : [$alias, $model];
    }

    public function alias(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        if (in_array($type, self::SUBJECTS, true)) {
            return $type;
        }

        foreach (self::SUBJECTS as $alias) {
            if (Relation::getMorphedModel($alias) === $type) {
                return $alias;
            }
        }

        return null;
    }

    public function label(?string $type): string
    {
        $alias = $this->alias($type);

        if ($alias === null) {
            return trans('app.audit.subject_types.system');
        }

        return trans("app.audit.subject_types.{$alias}");
    }

    /** @return array<int, array{label:string,value:string}> */
    public function options(User $actor): array
    {
        return collect($this->aliasesFor($actor))
            ->map(fn (string $alias): array => [
                'label' => $this->label($alias),
                'value' => $alias,
            ])
            ->values()
            ->all();
    }

    public function url(?Model $subject, ?string $type): ?string
    {
        if ($subject === null) {
            return null;
        }

        $alias = $this->alias($type ?? $subject->getMorphClass());

        return match ($alias) {
            'portfolio' => route('portfolios.show', $subject),
            'user' => route('users.show', $subject),
            'asset' => route('assets.show', $subject),
            'asset_stakeholder' => $this->relatedRoute('assets.show', 'asset', $subject->getAttribute('asset_id')),
            'tenant_profile' => route('tenants.show', ['tenant' => $subject->getKey()]),
            'lease' => route('leases.show', $subject),
            'lease_installment' => $this->relatedRoute('leases.show', 'lease', $subject->getAttribute('lease_id')),
            'payment' => route('payments.show', $subject),
            'payment_allocation' => $this->relatedRoute('payments.show', 'payment', $subject->getAttribute('payment_id')),
            'maintenance_request' => route('maintenance-requests.show', $subject),
            'maintenance_update' => $this->relatedRoute(
                'maintenance-requests.show',
                'maintenanceRequest',
                $subject->getAttribute('maintenance_request_id'),
            ),
            'expense_entry' => route('expenses.show', ['expense' => $subject->getKey()]),
            'document' => route('documents.show', $subject),
            'media_file' => route('media-files.show', $subject),
            'report_preset' => route('reports.index'),
            'label_override' => route('wording.index', ['search' => $subject->getAttribute('key')]),
            'cms_page' => route('cms.pages.show', $subject),
            'cms_section' => route('cms.sections.edit', $subject),
            'cms_page_section' => $this->relatedRoute('cms.pages.show', 'cmsPage', $subject->getAttribute('cms_page_id')),
            'navigation_item' => route('cms.navigation.edit', $subject),
            default => null,
        };
    }

    private function relatedRoute(string $route, string $parameter, mixed $id): ?string
    {
        return is_numeric($id) && (int) $id > 0
            ? route($route, [$parameter => (int) $id])
            : null;
    }
}
