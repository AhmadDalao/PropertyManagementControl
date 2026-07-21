<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShellModuleArchitectureTest extends TestCase
{
    #[Test]
    public function admin_layout_is_only_a_shell_composer(): void
    {
        $source = $this->source('resources/js/layouts/admin-layout.tsx');

        $this->assertLessThanOrEqual(50, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('@/modules/shell/admin-sidebar', $source);
        $this->assertStringContainsString('@/modules/shell/admin-topbar', $source);
        $this->assertStringContainsString('@/modules/shell/use-admin-shell', $source);
        $this->assertStringNotContainsString('useState', $source);
        $this->assertStringNotContainsString('useEffect', $source);
        $this->assertStringNotContainsString('localStorage', $source);
        $this->assertStringNotContainsString('MODULE_NAV_GROUPS', $source);
        $this->assertStringNotContainsString("router.post('/logout')", $source);
    }

    #[Test]
    public function shell_state_access_and_rendering_stay_separate(): void
    {
        foreach ([
            'account-menu.tsx',
            'admin-sidebar.tsx',
            'admin-topbar.tsx',
            'navigation-access.ts',
            'temporary-password-notice.tsx',
            'use-admin-shell.ts',
        ] as $file) {
            $path = "resources/js/modules/shell/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                190,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $state = $this->source('resources/js/modules/shell/use-admin-shell.ts');
        $access = $this->source('resources/js/modules/shell/navigation-access.ts');
        $sidebar = $this->source('resources/js/modules/shell/admin-sidebar.tsx');
        $account = $this->source('resources/js/modules/shell/account-menu.tsx');

        $this->assertStringContainsString('matchMedia', $state);
        $this->assertStringContainsString('localStorage', $state);
        $this->assertStringContainsString("event.key === 'Escape'", $state);
        $this->assertStringContainsString('pmc-drawer-open', $state);
        $this->assertStringNotContainsString('MODULE_NAV_GROUPS', $state);
        $this->assertStringContainsString('MODULE_NAV_GROUPS', $access);
        $this->assertStringContainsString('module_settings', $access);
        $this->assertStringContainsString('aria-current', $sidebar);
        $this->assertStringContainsString('inert={drawerHidden}', $sidebar);
        $this->assertStringContainsString('closeOutside', $account);
        $this->assertStringContainsString('closeOnEscape', $account);
    }

    #[Test]
    public function shell_styles_are_layered_and_bounded(): void
    {
        $entry = $this->source('resources/css/styles/shell.css');

        $this->assertLessThanOrEqual(10, substr_count($entry, "\n") + 1);

        foreach ([
            'layout.css',
            'sidebar.css',
            'topbar.css',
            'search.css',
            'account.css',
            'responsive.css',
        ] as $file) {
            $this->assertStringContainsString("./shell/{$file}", $entry);

            $source = $this->source("resources/css/styles/shell/{$file}");
            $this->assertLessThanOrEqual(
                200,
                substr_count($source, "\n") + 1,
                "shell/{$file} is becoming a stylesheet monolith.",
            );
        }

        $account = $this->source('resources/css/styles/shell/account.css');
        $this->assertStringContainsString('.pmc-account-trigger', $account);
        $this->assertStringNotContainsString('.pmc-account-menu > summary', $account);
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
