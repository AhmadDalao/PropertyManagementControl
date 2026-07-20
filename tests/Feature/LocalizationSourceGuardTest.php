<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalizationSourceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_localized_workspaces_do_not_add_visible_english_jsx_literals(): void
    {
        $files = [
            'resources/js/modules/property-map/map-workspace.tsx',
            'resources/js/modules/showcase-data/index-page.tsx',
            'resources/js/modules/wording/index-page.tsx',
            'resources/js/layouts/admin-layout.tsx',
            'resources/js/layouts/public-layout.tsx',
            'resources/js/pages/auth/login.tsx',
        ];

        foreach ($files as $file) {
            $source = (string) file_get_contents(base_path($file));
            $source = preg_replace('/>\s*(?:PMC|PC)\s*</', '><', $source) ?? $source;

            $this->assertDoesNotMatchRegularExpression(
                '/>\s*[A-Z][A-Za-z][^<{]*</',
                $source,
                "Visible English JSX text was added to [{$file}]. Use a translation key.",
            );
            $this->assertDoesNotMatchRegularExpression(
                '/(?:placeholder|aria-label|title)="[A-Z][^"]+"/',
                $source,
                "A visible English attribute was added to [{$file}]. Use a translation key.",
            );
        }
    }

    public function test_server_success_and_authorization_messages_use_translation_keys(): void
    {
        $directories = [
            app_path('Http/Controllers'),
            app_path('Http/Middleware'),
            app_path('Http/Requests'),
            app_path('Services'),
        ];

        foreach ($directories as $directory) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $source = (string) file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    "/->with\\('(success|error)',\\s*'[A-Z]/",
                    $source,
                    "Hardcoded flash copy found in [{$file->getPathname()}].",
                );
                $this->assertDoesNotMatchRegularExpression(
                    "/abort_(?:if|unless)\\([^\\n]*'[A-Z][A-Za-z ]{5,}/",
                    $source,
                    "Hardcoded authorization copy found in [{$file->getPathname()}].",
                );
            }
        }
    }
}
