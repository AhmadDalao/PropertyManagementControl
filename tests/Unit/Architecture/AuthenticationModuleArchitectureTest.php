<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AuthenticationModuleArchitectureTest extends TestCase
{
    #[Test]
    public function authentication_controllers_only_adapt_http_requests(): void
    {
        foreach ([
            'AuthenticatedSessionController.php',
            'ForgotPasswordController.php',
            'ResetPasswordController.php',
        ] as $file) {
            $path = "app/Http/Controllers/Auth/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                55,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a workflow service.",
            );
            $this->assertStringNotContainsString('Auth::', $source);
            $this->assertStringNotContainsString('Password::', $source);
            $this->assertStringNotContainsString('Hash::', $source);
            $this->assertStringNotContainsString('->validate(', $source);
            $this->assertStringContainsString(
                'App\Modules\Authentication',
                $source,
            );
        }
    }

    #[Test]
    public function authentication_workflows_live_in_bounded_module_units(): void
    {
        $files = glob($this->path('app/Modules/Authentication/**/*.php')) ?: [];

        $this->assertCount(9, $files);

        foreach ($files as $file) {
            $this->assertLessThanOrEqual(
                90,
                substr_count((string) file_get_contents($file), "\n") + 1,
                "{$file} is becoming an authentication monolith.",
            );
        }

        $login = $this->source(
            'app/Modules/Authentication/Actions/AuthenticateUser.php',
        );
        $reset = $this->source(
            'app/Modules/Authentication/Actions/ResetUserPassword.php',
        );

        $this->assertStringContainsString('RateLimiter::', $login);
        $this->assertStringContainsString("status !== 'active'", $login);
        $this->assertStringContainsString("'last_login_at' => now()", $login);
        $this->assertStringContainsString('Password::reset', $reset);
        $this->assertStringContainsString("'force_password_reset' => false", $reset);
        $presenter = $this->source(
            'app/Modules/Authentication/Presenters/AuthenticatedUserPresenter.php',
        );
        $middleware = $this->source(
            'app/Http/Middleware/HandleInertiaRequests.php',
        );
        $this->assertStringContainsString('PortfolioModules::normalize', $presenter);
        $this->assertStringContainsString('AuthenticatedUserPresenter', $middleware);
        $this->assertStringNotContainsString("'force_password_reset' =>", $middleware);
        $this->assertFileDoesNotExist(
            $this->path('app/Http/Requests/Auth/LoginRequest.php'),
        );
        $notification = $this->source(
            'app/Notifications/ResetPasswordNotification.php',
        );
        $this->assertLessThanOrEqual(
            6,
            substr_count($notification, "\n") + 1,
        );
        $this->assertStringContainsString(
            'extends \App\Modules\Authentication\Notifications\ResetPasswordNotification',
            $notification,
        );
    }

    #[Test]
    public function authentication_frontend_and_styles_are_module_owned(): void
    {
        foreach (['login', 'forgot-password', 'reset-password'] as $page) {
            $source = $this->source("resources/js/pages/auth/{$page}.tsx");

            $this->assertLessThanOrEqual(3, substr_count($source, "\n") + 1);
            $this->assertStringContainsString('@/modules/auth/', $source);
        }

        $files = $this->recursiveFiles('resources/js/modules/auth', ['ts', 'tsx']);
        $this->assertCount(9, $files);

        foreach ($files as $file) {
            $this->assertLessThanOrEqual(
                130,
                substr_count((string) file_get_contents($file), "\n") + 1,
                "{$file} is becoming an authentication page monolith.",
            );
        }

        $stylesheet = $this->source('resources/css/styles/auth.css');
        $this->assertLessThanOrEqual(5, substr_count($stylesheet, "\n") + 1);
        $this->assertStringContainsString('./auth/base.css', $stylesheet);
        $this->assertStringContainsString('./auth/responsive.css', $stylesheet);
        $this->assertFileDoesNotExist(
            $this->path('resources/js/components/auth-shell.tsx'),
        );
        $this->assertFileDoesNotExist(
            $this->path('resources/css/styles/public/auth.css'),
        );
    }

    /** @param array<int, string> $extensions */
    private function recursiveFiles(string $directory, array $extensions): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path($directory)),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array($file->getExtension(), $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
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
