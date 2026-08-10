<?php

namespace App\Providers;

use App\Models\Asset;
use App\Models\AssetStakeholder;
use App\Models\CmsPage;
use App\Models\CmsPageSection;
use App\Models\CmsSection;
use App\Models\CollectionFollowUp;
use App\Models\DailyOperationsReportRun;
use App\Models\Document;
use App\Models\ExpenseEntry;
use App\Models\InfrastructureSetting;
use App\Models\LabelOverride;
use App\Models\Lease;
use App\Models\LeaseInstallment;
use App\Models\LeaseMoveOut;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceUpdate;
use App\Models\MaintenanceVendor;
use App\Models\MaintenanceWorkOrder;
use App\Models\MediaFile;
use App\Models\NavigationItem;
use App\Models\OperationalReadinessCheck;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Portfolio;
use App\Models\ReportPreset;
use App\Models\SystemBackupRun;
use App\Models\TenantProfile;
use App\Models\User;
use App\Modules\EmailDelivery\Actions\RecordEmailDelivery;
use App\Modules\InfrastructureSettings\Actions\ApplyInfrastructureSettings;
use App\Modules\SystemBackups\Contracts\DatabaseBackupWriter;
use App\Modules\SystemBackups\Contracts\DocumentBackupWriter;
use App\Modules\SystemBackups\Support\MySqlDatabaseBackupWriter;
use App\Modules\SystemBackups\Support\TarDocumentBackupWriter;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ApplyInfrastructureSettings::class);
        $this->app->bind(DatabaseBackupWriter::class, MySqlDatabaseBackupWriter::class);
        $this->app->bind(DocumentBackupWriter::class, TarDocumentBackupWriter::class);

        if (! $this->app->environment(['local', 'testing'])) {
            return;
        }

        foreach ([
            'Laravel\Pail\PailServiceProvider',
            'Laravel\Pao\Laravel\ServiceProvider',
            'Laravel\Sail\SailServiceProvider',
            'NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider',
        ] as $provider) {
            if (class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        app(ApplyInfrastructureSettings::class)->handle();
        $this->configureDefaults();
        $this->registerEmailDeliveryTracking();
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
            'maintenance_vendor' => MaintenanceVendor::class,
            'maintenance_work_order' => MaintenanceWorkOrder::class,
            'expense_entry' => ExpenseEntry::class,
            'infrastructure_setting' => InfrastructureSetting::class,
            'cms_page' => CmsPage::class,
            'cms_section' => CmsSection::class,
            'cms_page_section' => CmsPageSection::class,
            'navigation_item' => NavigationItem::class,
            'media_file' => MediaFile::class,
            'label_override' => LabelOverride::class,
            'report_preset' => ReportPreset::class,
            'operational_readiness_check' => OperationalReadinessCheck::class,
            'system_backup_run' => SystemBackupRun::class,
            'daily_operations_report_run' => DailyOperationsReportRun::class,
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

    private function registerEmailDeliveryTracking(): void
    {
        Event::listen(
            NotificationSending::class,
            fn (NotificationSending $event) => app(RecordEmailDelivery::class)->starting($event),
        );
        Event::listen(
            MessageSending::class,
            fn (MessageSending $event) => app(RecordEmailDelivery::class)->message($event),
        );
        Event::listen(
            NotificationSent::class,
            fn (NotificationSent $event) => app(RecordEmailDelivery::class)->accepted($event),
        );
        Event::listen(
            NotificationFailed::class,
            fn (NotificationFailed $event) => app(RecordEmailDelivery::class)->failed($event),
        );
    }
}
