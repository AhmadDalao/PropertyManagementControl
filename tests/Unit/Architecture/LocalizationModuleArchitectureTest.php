<?php

namespace Tests\Unit\Architecture;

use App\Modules\ModuleRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class LocalizationModuleArchitectureTest extends TestCase
{
    #[Test]
    public function locale_http_adapters_delegate_to_the_module(): void
    {
        $controller = $this->source('app/Http/Controllers/LocaleController.php');
        $middleware = $this->source('app/Http/Middleware/SetLocale.php');

        $this->assertLessThanOrEqual(25, substr_count($controller, "\n") + 1);
        $this->assertLessThanOrEqual(35, substr_count($middleware, "\n") + 1);
        $this->assertStringContainsString('SwitchLocale', $controller);
        $this->assertStringContainsString('ApplyRequestLocale', $middleware);
        $this->assertStringNotContainsString('session()->put', $controller.$middleware);
        $this->assertStringNotContainsString('UiTranslationCatalog', $controller.$middleware);
        $this->assertStringNotContainsString('parse_url', $controller.$middleware);
    }

    #[Test]
    public function locale_resolution_and_switching_are_bounded_module_units(): void
    {
        $files = glob($this->path('app/Modules/Localization/**/*.php')) ?: [];

        $this->assertCount(4, $files);

        foreach ($files as $file) {
            $this->assertLessThanOrEqual(
                70,
                substr_count((string) file_get_contents($file), "\n") + 1,
                "{$file} is becoming a localization monolith.",
            );
        }

        $resolver = $this->source(
            'app/Modules/Localization/Support/LocaleResolver.php',
        );
        $switch = $this->source(
            'app/Modules/Localization/Actions/SwitchLocale.php',
        );

        $this->assertStringContainsString("session()->get('locale')", $resolver);
        $this->assertStringContainsString('preferred_locale', $resolver);
        $this->assertStringContainsString("unset(\$query['locale'])", $switch);
        $this->assertArrayHasKey(
            'localization',
            ModuleRegistry::infrastructureModules(),
        );
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
