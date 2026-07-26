<?php

namespace App\Modules\Audit\Support;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\CollectionFollowUp;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\LabelOverride;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\LeaseMoveOut;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\MediaFile;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Portfolio;
use App\Models\ReportPreset;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\Shared\Authorization\AssignedPropertyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditPortfolioScope
{
    public function __construct(
        private readonly AuditSubjectRegistry $subjects,
        private readonly AssignedPropertyScope $assignments,
    ) {}

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function apply(Builder $query, User $actor, ?int $requestedPortfolioId): Builder
    {
        if ($this->assignments->restricts($actor)) {
            return $this->applyManagerAssignments($query, $actor);
        }

        $portfolioId = $actor->hasRole('superadmin')
            ? $requestedPortfolioId
            : $actor->portfolio_id;

        if ($portfolioId === null) {
            return $actor->hasRole('superadmin')
                ? $query
                : $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($portfolioId): void {
            $portfolioUsers = User::query()->where('portfolio_id', $portfolioId)->select('id');
            $portfolioLeases = Lease::query()->where('portfolio_id', $portfolioId)->select('id');
            $portfolioPayments = Payment::query()->where('portfolio_id', $portfolioId)->select('id');
            $portfolioMaintenance = MaintenanceRequest::query()->where('portfolio_id', $portfolioId)->select('id');

            $this->orSubjectIds($query, 'portfolio', Portfolio::query()->whereKey($portfolioId)->select('id'));
            $this->orSubjectIds($query, 'user', clone $portfolioUsers);
            $this->orSubjectIds($query, 'asset', Asset::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'asset_stakeholder', AssetStakeholder::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'tenant_profile', TenantProfile::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'lease', clone $portfolioLeases);
            $this->orSubjectIds($query, 'lease_installment', LeaseInstallment::query()->whereIn('lease_id', clone $portfolioLeases)->select('id'));
            $this->orSubjectIds($query, 'lease_move_out', LeaseMoveOut::query()->whereIn('lease_id', clone $portfolioLeases)->select('id'));
            $this->orSubjectIds($query, 'collection_follow_up', CollectionFollowUp::query()->whereIn('lease_id', clone $portfolioLeases)->select('id'));
            $this->orSubjectIds($query, 'payment', clone $portfolioPayments);
            $this->orSubjectIds($query, 'payment_allocation', PaymentAllocation::query()->whereIn('payment_id', clone $portfolioPayments)->select('id'));
            $this->orSubjectIds($query, 'maintenance_request', clone $portfolioMaintenance);
            $this->orSubjectIds($query, 'maintenance_update', MaintenanceUpdate::query()->whereIn('maintenance_request_id', clone $portfolioMaintenance)->select('id'));
            $this->orSubjectIds($query, 'maintenance_vendor', MaintenanceVendor::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'maintenance_work_order', MaintenanceWorkOrder::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'expense_entry', ExpenseEntry::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'document', Document::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds($query, 'media_file', MediaFile::query()->where('portfolio_id', $portfolioId)->select('id'));
            $this->orSubjectIds(
                $query,
                'report_preset',
                ReportPreset::query()
                    ->where(function (Builder $query) use ($portfolioId, $portfolioUsers): void {
                        $query
                            ->where('portfolio_id', $portfolioId)
                            ->orWhereIn('user_id', clone $portfolioUsers);
                    })
                    ->select('id'),
            );
            $this->orSubjectIds($query, 'label_override', LabelOverride::query()->where('portfolio_id', $portfolioId)->select('id'));
        });
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    private function applyManagerAssignments(Builder $query, User $actor): Builder
    {
        return $query->where(function (Builder $query) use ($actor): void {
            $assets = $this->assignments->assets(Asset::query(), $actor)->select('id');
            $tenants = $this->assignments->tenants(TenantProfile::query(), $actor)->select('id');
            $leases = $this->assignments->leases(Lease::query(), $actor)->select('id');
            $payments = $this->assignments->payments(Payment::query(), $actor)->select('id');
            $maintenance = $this->assignments
                ->maintenance(MaintenanceRequest::query(), $actor)
                ->select('id');
            $expenses = $this->assignments->expenses(ExpenseEntry::query(), $actor)->select('id');
            $documents = $this->assignments->documents(Document::query(), $actor)->select('id');

            $this->orSubjectIds(
                $query,
                'user',
                User::query()
                    ->whereKey($actor->id)
                    ->orWhereHas(
                        'tenantProfile',
                        fn (Builder $profiles) => $profiles->whereIn('id', clone $tenants),
                    )
                    ->select('id'),
            );
            $this->orSubjectIds($query, 'asset', clone $assets);
            $this->orSubjectIds(
                $query,
                'asset_stakeholder',
                AssetStakeholder::query()->whereIn('asset_id', clone $assets)->select('id'),
            );
            $this->orSubjectIds($query, 'tenant_profile', clone $tenants);
            $this->orSubjectIds($query, 'lease', clone $leases);
            $this->orSubjectIds(
                $query,
                'lease_installment',
                LeaseInstallment::query()->whereIn('lease_id', clone $leases)->select('id'),
            );
            $this->orSubjectIds(
                $query,
                'lease_move_out',
                LeaseMoveOut::query()->whereIn('lease_id', clone $leases)->select('id'),
            );
            $this->orSubjectIds(
                $query,
                'collection_follow_up',
                CollectionFollowUp::query()->whereIn('lease_id', clone $leases)->select('id'),
            );
            $this->orSubjectIds($query, 'payment', clone $payments);
            $this->orSubjectIds(
                $query,
                'payment_allocation',
                PaymentAllocation::query()->whereIn('payment_id', clone $payments)->select('id'),
            );
            $this->orSubjectIds($query, 'maintenance_request', clone $maintenance);
            $this->orSubjectIds(
                $query,
                'maintenance_update',
                MaintenanceUpdate::query()
                    ->whereIn('maintenance_request_id', clone $maintenance)
                    ->select('id'),
            );
            $this->orSubjectIds(
                $query,
                'maintenance_vendor',
                MaintenanceVendor::query()->where('portfolio_id', $actor->portfolio_id ?? 0)->select('id'),
            );
            $this->orSubjectIds(
                $query,
                'maintenance_work_order',
                MaintenanceWorkOrder::query()
                    ->whereIn('maintenance_request_id', clone $maintenance)
                    ->select('id'),
            );
            $this->orSubjectIds($query, 'expense_entry', $expenses);
            $this->orSubjectIds($query, 'document', $documents);
            $this->orSubjectIds(
                $query,
                'media_file',
                MediaFile::query()->where('uploaded_by_user_id', $actor->id)->select('id'),
            );
            $this->orSubjectIds(
                $query,
                'report_preset',
                ReportPreset::query()->where('user_id', $actor->id)->select('id'),
            );
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<Activity>  $query
     * @param  Builder<TModel>  $ids
     */
    private function orSubjectIds(Builder $query, string $alias, Builder $ids): void
    {
        $query->orWhere(function (Builder $query) use ($alias, $ids): void {
            $query
                ->whereIn('subject_type', $this->subjects->typeValues($alias))
                ->whereIn('subject_id', $ids);
        });
    }
}
