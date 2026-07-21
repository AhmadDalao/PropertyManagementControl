<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PropertyMapModuleArchitectureTest extends TestCase
{
    #[Test]
    public function property_map_presenter_only_orchestrates_focused_units(): void
    {
        $source = $this->source('app/Modules/Assets/PropertyMapPresenter.php');

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('PropertyMapSourceQuery', $source);
        $this->assertStringContainsString('PropertyMapActivityQuery', $source);
        $this->assertStringContainsString('PropertyMapAssetPresenter', $source);
        $this->assertStringContainsString('PropertyMapPayloadPresenter', $source);
        $this->assertStringNotContainsString('Lease::query()', $source);
        $this->assertStringNotContainsString('MaintenanceRequest::query()', $source);
        $this->assertStringNotContainsString("->where('meta_json'", $source);
    }

    #[Test]
    public function backend_map_responsibilities_stay_in_separate_units(): void
    {
        foreach ([
            'app/Modules/Assets/Presenters/PropertyMapAssetPresenter.php',
            'app/Modules/Assets/Presenters/PropertyMapPayloadPresenter.php',
            'app/Modules/Assets/Queries/PropertyMapActivityQuery.php',
            'app/Modules/Assets/Queries/PropertyMapSourceQuery.php',
            'app/Modules/Assets/Support/PropertyMapCoordinates.php',
            'app/Modules/Assets/Support/PropertyMapHierarchy.php',
            'app/Modules/Assets/Support/PropertyMapLocalization.php',
        ] as $path) {
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                160,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $sourceQuery = $this->source(
            'app/Modules/Assets/Queries/PropertyMapSourceQuery.php',
        );
        $assetPresenter = $this->source(
            'app/Modules/Assets/Presenters/PropertyMapAssetPresenter.php',
        );

        $this->assertStringContainsString('MAX_MARKERS = 40', $sourceQuery);
        $this->assertStringNotContainsString('Lease::query()', $assetPresenter);
        $this->assertStringNotContainsString('MaintenanceRequest::query()', $assetPresenter);
    }

    #[Test]
    public function frontend_map_composer_delegates_state_and_rendering(): void
    {
        $composer = $this->source(
            'resources/js/modules/property-map/map-workspace.tsx',
        );

        $this->assertLessThanOrEqual(100, substr_count($composer, "\n") + 1);
        $this->assertStringContainsString("from './map-filters'", $composer);
        $this->assertStringContainsString("from './map-stage'", $composer);
        $this->assertStringContainsString("from './property-map-directory'", $composer);
        $this->assertStringContainsString("from './use-property-map-workspace'", $composer);
        $this->assertStringNotContainsString('useState', $composer);
        $this->assertStringNotContainsString("from 'leaflet'", $composer);
    }

    #[Test]
    public function frontend_map_units_stay_focused(): void
    {
        foreach ([
            'geographic-map.tsx',
            'map-filters.tsx',
            'map-metrics.tsx',
            'map-setup-status.tsx',
            'map-stage.tsx',
            'map-utils.ts',
            'portfolio-filter.tsx',
            'property-map-detail.tsx',
            'property-map-directory.tsx',
            'types.ts',
            'use-property-map-workspace.ts',
        ] as $file) {
            $path = "resources/js/modules/property-map/{$file}";
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                250,
                substr_count($source, "\n") + 1,
                "{$path} is becoming a monolith.",
            );
        }

        $map = $this->source(
            'resources/js/modules/property-map/geographic-map.tsx',
        );
        $state = $this->source(
            'resources/js/modules/property-map/use-property-map-workspace.ts',
        );

        $this->assertStringContainsString("from 'leaflet'", $map);
        $this->assertStringNotContainsString('filterMapAssets', $map);
        $this->assertStringContainsString('useState', $state);
        $this->assertStringNotContainsString("from 'leaflet'", $state);
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
