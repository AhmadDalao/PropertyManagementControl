<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProfileModuleArchitectureTest extends TestCase
{
    #[Test]
    public function profile_controller_only_adapts_http_requests(): void
    {
        $source = $this->source('app/Http/Controllers/ProfileController.php');

        $this->assertLessThanOrEqual(60, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('ProfilePresenter', $source);
        $this->assertStringContainsString('UpdateProfileRequest', $source);
        $this->assertStringContainsString('UpdateProfilePasswordRequest', $source);
        $this->assertStringContainsString('UpdateProfilePassword', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('Hash::', $source);
        $this->assertStringNotContainsString('loadMissing', $source);
        $this->assertStringNotContainsString('ValidationException', $source);
    }

    #[Test]
    public function profile_backend_responsibilities_stay_focused(): void
    {
        foreach ([
            'Actions/UpdateProfile.php',
            'Actions/UpdateProfilePassword.php',
            'Presenters/ProfilePresenter.php',
            'Requests/UpdateProfileRequest.php',
            'Requests/UpdateProfilePasswordRequest.php',
        ] as $file) {
            $path = "app/Modules/Profile/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                70,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $passwordRequest = $this->source('app/Modules/Profile/Requests/UpdateProfilePasswordRequest.php');
        $passwordAction = $this->source('app/Modules/Profile/Actions/UpdateProfilePassword.php');
        $presenter = $this->source('app/Modules/Profile/Presenters/ProfilePresenter.php');
        $routes = $this->source('routes/web.php');

        $this->assertStringContainsString('current_password:web', $passwordRequest);
        $this->assertStringContainsString('Password::defaults()', $passwordRequest);
        $this->assertStringContainsString('Hash::make', $passwordAction);
        $this->assertStringContainsString("'force_password_reset' => false", $passwordAction);
        $this->assertStringContainsString("loadMissing(['portfolio', 'tenantProfile'])", $presenter);
        $this->assertStringNotContainsString("'password' =>", $presenter);
        $this->assertStringContainsString("->middleware('throttle:6,1')", $routes);
    }

    #[Test]
    public function profile_frontend_and_styles_are_composed_from_bounded_units(): void
    {
        $entry = $this->source('resources/js/pages/admin/profile/index.tsx');
        $this->assertLessThanOrEqual(3, substr_count($entry, "\n") + 1);
        $this->assertStringContainsString('@/modules/profile/profile-page', $entry);

        foreach ([
            'profile-access-context.tsx',
            'profile-details-form.tsx',
            'profile-field.tsx',
            'profile-header.tsx',
            'profile-page.tsx',
            'profile-password-form.tsx',
            'profile-summary.tsx',
            'types.ts',
        ] as $file) {
            $path = "resources/js/modules/profile/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                160,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $stylesheet = $this->source('resources/css/styles/profile.css');
        $this->assertLessThanOrEqual(8, substr_count($stylesheet, "\n") + 1);

        foreach (['base.css', 'summary.css', 'forms.css', 'context.css', 'responsive.css'] as $file) {
            $this->assertStringContainsString("./profile/{$file}", $stylesheet);

            $source = $this->source("resources/css/styles/profile/{$file}");
            $this->assertLessThanOrEqual(
                130,
                substr_count($source, "\n") + 1,
                "profile/{$file} is becoming a stylesheet monolith.",
            );
        }
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
