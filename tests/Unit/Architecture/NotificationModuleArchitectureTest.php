<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class NotificationModuleArchitectureTest extends TestCase
{
    #[Test]
    public function notification_controller_and_inertia_entry_stay_thin(): void
    {
        $controller = $this->source('app/Http/Controllers/NotificationController.php');
        $entry = $this->source('resources/js/pages/admin/notifications/index.tsx');

        $this->assertLessThanOrEqual(50, substr_count($controller, "\n") + 1);
        $this->assertLessThanOrEqual(2, substr_count($entry, "\n") + 1);
        $this->assertStringNotContainsString('::query()', $controller);
        $this->assertStringNotContainsString('DB::', $controller);
        $this->assertStringContainsString(
            "from '@/modules/notifications/index-page'",
            $entry,
        );
    }

    #[Test]
    public function notifications_have_owned_queries_actions_and_presenters(): void
    {
        foreach ([
            'app/Modules/Notifications/Actions/MarkNotificationsRead.php',
            'app/Modules/Notifications/Actions/SendMaintenanceActivityNotification.php',
            'app/Modules/Notifications/Actions/SendOperationalActivityNotification.php',
            'app/Modules/Notifications/Data/OperationalNotificationData.php',
            'app/Modules/Notifications/Notifications/MaintenanceActivityNotification.php',
            'app/Modules/Notifications/Notifications/OperationalActivityNotification.php',
            'app/Modules/Notifications/Presenters/NotificationItemPresenter.php',
            'app/Modules/Notifications/Presenters/OperationalNotificationFactory.php',
            'app/Modules/Notifications/Presenters/NotificationSummaryPresenter.php',
            'app/Modules/Notifications/Queries/MaintenanceNotificationRecipientsQuery.php',
            'app/Modules/Notifications/Queries/NotificationIndexQuery.php',
            'app/Modules/Notifications/Queries/OperationalNotificationRecipientsQuery.php',
            'app/Modules/Notifications/Requests/NotificationIndexRequest.php',
            'resources/js/modules/notifications/index-page.tsx',
            'resources/js/modules/notifications/notification-filters.tsx',
            'resources/js/modules/notifications/notification-list.tsx',
            'resources/js/modules/notifications/notification-menu.tsx',
            'resources/js/modules/notifications/types.ts',
        ] as $path) {
            $this->assertFileExists($this->path($path));
        }

        $entry = $this->source('resources/js/modules/notifications/index-page.tsx');
        $this->assertLessThanOrEqual(80, substr_count($entry, "\n") + 1);
        $this->assertStringContainsString("from './notification-filters'", $entry);
        $this->assertStringContainsString("from './notification-list'", $entry);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->path($relativePath));
        $this->assertNotFalse($source);

        return $source;
    }

    private function path(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
