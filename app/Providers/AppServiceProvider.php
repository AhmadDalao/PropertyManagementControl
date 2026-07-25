<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\CollectionFollowUp;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\LabelOverride;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\LeaseMoveOut;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\MediaFile;
use App\Models\NavigationItem;
use App\Models\OperationalReadinessCheck;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Portfolio;
use App\Models\ReportPreset;
use App\Models\TenantProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Relation::enforceMorphMap([
            'user' => User::class,
            'portfolio' => Portfolio::class,
            'asset' => Asset::class,
            'asset_stakeholder' => AssetStakeholder::class,
            'lease' => Lease::class,
            'lease_installment' => LeaseInstallment::class,
            'lease_move_out' => LeaseMoveOut::class,
            'collection_follow_up' => CollectionFollowUp::class,
            'payment' => Payment::class,
            'payment_allocation' => PaymentAllocation::class,
            'tenant_profile' => TenantProfile::class,
            'document' => Document::class,
            'maintenance_attachment' => MaintenanceAttachment::class,
            'maintenance_request' => MaintenanceRequest::class,
            'maintenance_update' => MaintenanceUpdate::class,
            'expense_entry' => ExpenseEntry::class,
            'cms_page' => CmsPage::class,
            'cms_section' => CmsSection::class,
            'cms_page_section' => CmsPageSection::class,
            'navigation_item' => NavigationItem::class,
            'media_file' => MediaFile::class,
            'label_override' => LabelOverride::class,
            'report_preset' => ReportPreset::class,
            'operational_readiness_check' => OperationalReadinessCheck::class,
        ]);

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
