<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserModuleArchitectureTest extends TestCase
{
    #[Test]
    public function user_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source($this->path('app/Http/Controllers/UserController.php'));

        $this->assertLessThanOrEqual(110, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('UserIndexQuery', $source);
        $this->assertStringContainsString('UserFormPresenter', $source);
        $this->assertStringContainsString('UserDetailPresenter', $source);
        $this->assertStringContainsString('ManageUsers', $source);
        $this->assertStringNotContainsString('User::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }

    #[Test]
    public function user_frontend_entry_only_composes_module_components(): void
    {
        $source = $this->source($this->path('resources/js/modules/users/index-page.tsx'));

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString("from './user-metrics'", $source);
        $this->assertStringContainsString("from './user-table'", $source);
        $this->assertStringContainsString("from './types'", $source);
        $this->assertStringNotContainsString("from '@/components/data-table'", $source);
    }

    #[Test]
    public function user_module_owns_each_resource_responsibility(): void
    {
        foreach ([
            $this->path('app/Http/Middleware/EnsureActiveAccount.php'),
            $this->path('app/Modules/Users/Actions/CreateUser.php'),
            $this->path('app/Modules/Users/Actions/ManageUsers.php'),
            $this->path('app/Modules/Users/Actions/SuspendUser.php'),
            $this->path('app/Modules/Users/Actions/UpdateUser.php'),
            $this->path('app/Modules/Users/Data/UserDetailData.php'),
            $this->path('app/Modules/Users/Data/UserFormData.php'),
            $this->path('app/Modules/Users/Presenters/UserCreateFormPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserDetailPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserDetailHeaderPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserDetailOverviewPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserEditFormPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserFormDefinitionPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserFormPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserRelatedPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserTableRowPresenter.php'),
            $this->path('app/Modules/Users/Queries/UserDetailQuery.php'),
            $this->path('app/Modules/Users/Queries/UserDirectoryQuery.php'),
            $this->path('app/Modules/Users/Queries/UserFormOptionsQuery.php'),
            $this->path('app/Modules/Users/Queries/UserIndexQuery.php'),
            $this->path('app/Modules/Users/Queries/UserInsightsQuery.php'),
            $this->path('app/Modules/Users/Requests/HasUserValidationAttributes.php'),
            $this->path('app/Modules/Users/Requests/StoreUserRequest.php'),
            $this->path('app/Modules/Users/Requests/UpdateUserRequest.php'),
            $this->path('app/Modules/Users/Support/UserAccess.php'),
            $this->path('app/Modules/Users/Support/UserContinuityGuard.php'),
            $this->path('app/Modules/Users/Support/UserInputGuard.php'),
            $this->path('app/Modules/Users/Support/UserOptions.php'),
            $this->path('app/Modules/Users/Support/UserPortfolioOwnership.php'),
            $this->path('app/Modules/Users/Support/UserPortfolioResolver.php'),
            $this->path('app/Modules/Users/Support/UserRoleGuard.php'),
            $this->path('app/Modules/Users/Support/UserSessionRevoker.php'),
            $this->path('app/Modules/Users/Support/UserTenantProfileSynchronizer.php'),
            $this->path('resources/js/modules/users/user-filters.ts'),
            $this->path('resources/js/modules/users/user-metrics.tsx'),
            $this->path('resources/js/modules/users/user-table-cells.tsx'),
            $this->path('resources/js/modules/users/user-table-config.tsx'),
            $this->path('resources/js/modules/users/user-table.tsx'),
            $this->path('resources/js/modules/users/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
    }

    #[Test]
    public function user_facades_and_frontend_composers_stay_small(): void
    {
        foreach ([
            'app/Modules/Users/Actions/ManageUsers.php' => 40,
            'app/Modules/Users/Presenters/UserDetailPresenter.php' => 45,
            'app/Modules/Users/Presenters/UserFormPresenter.php' => 45,
            'app/Modules/Users/Queries/UserIndexQuery.php' => 90,
            'resources/js/modules/users/user-table.tsx' => 65,
            'resources/js/modules/users/user-table-config.tsx' => 100,
            'resources/js/modules/users/user-table-cells.tsx' => 140,
        ] as $path => $maximumLines) {
            $source = $this->source($this->path($path));

            $this->assertLessThanOrEqual(
                $maximumLines,
                substr_count($source, "\n") + 1,
                "{$path} should only coordinate focused collaborators.",
            );
        }
    }

    #[Test]
    public function authenticated_routes_enforce_active_account_status(): void
    {
        $routes = $this->source($this->path('routes/web.php'));
        $bootstrap = $this->source($this->path('bootstrap/app.php'));

        $this->assertStringContainsString("['auth', 'account.active', 'password.changed']", $routes);
        $this->assertStringContainsString("'account.active' => EnsureActiveAccount::class", $bootstrap);
    }

    private function source(string $path): string
    {
        $source = file_get_contents($path);

        $this->assertNotFalse($source);

        return $source;
    }

    private function path(string $relativePath): string
    {
        return dirname(__DIR__, 3).'/'.$relativePath;
    }
}
