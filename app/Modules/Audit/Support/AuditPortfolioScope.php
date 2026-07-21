<?php

namespace App\Modules\Audit\Support;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\LabelOverride;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\MediaFile;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Portfolio;
use App\Models\ReportPreset;
use App\Models\TenantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class AuditPortfolioScope
{
    public function __construct(private readonly AuditSubjectRegistry $subjects) {}

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function apply(Builder $query, User $actor, ?int $requestedPortfolioId): Builder
    {
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
            $this->orSubjectIds($query, 'payment', clone $portfolioPayments);
            $this->orSubjectIds($query, 'payment_allocation', PaymentAllocation::query()->whereIn('payment_id', clone $portfolioPayments)->select('id'));
            $this->orSubjectIds($query, 'maintenance_request', clone $portfolioMaintenance);
            $this->orSubjectIds($query, 'maintenance_update', MaintenanceUpdate::query()->whereIn('maintenance_request_id', clone $portfolioMaintenance)->select('id'));
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
