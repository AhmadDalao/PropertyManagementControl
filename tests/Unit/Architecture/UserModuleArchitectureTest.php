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
            $this->path('app/Modules/Users/Actions/ManageUsers.php'),
            $this->path('app/Modules/Users/Presenters/UserDetailPresenter.php'),
            $this->path('app/Modules/Users/Presenters/UserFormPresenter.php'),
            $this->path('app/Modules/Users/Queries/UserIndexQuery.php'),
            $this->path('app/Modules/Users/Requests/HasUserValidationAttributes.php'),
            $this->path('app/Modules/Users/Requests/StoreUserRequest.php'),
            $this->path('app/Modules/Users/Requests/UpdateUserRequest.php'),
            $this->path('app/Modules/Users/Support/UserAccess.php'),
            $this->path('app/Modules/Users/Support/UserOptions.php'),
            $this->path('resources/js/modules/users/user-filters.ts'),
            $this->path('resources/js/modules/users/user-metrics.tsx'),
            $this->path('resources/js/modules/users/user-table.tsx'),
            $this->path('resources/js/modules/users/types.ts'),
        ] as $path) {
            $this->assertFileExists($path);
        }
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
