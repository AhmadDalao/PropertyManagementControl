<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RentCollectionModuleArchitectureTest extends TestCase
{
    #[Test]
    public function rent_collection_controller_stays_a_thin_http_adapter(): void
    {
        $source = $this->source('app/Http/Controllers/RentCollectionController.php');

        $this->assertLessThanOrEqual(70, substr_count($source, "\n") + 1);
        $this->assertStringContainsString('RentCollectionIndexQuery', $source);
        $this->assertStringContainsString('CollectionFollowUpPagePresenter', $source);
        $this->assertStringContainsString('RecordCollectionFollowUp', $source);
        $this->assertStringNotContainsString('LeaseInstallment::query()', $source);
        $this->assertStringNotContainsString('->validate([', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }

    #[Test]
    public function collection_follow_up_responsibilities_stay_in_focused_units(): void
    {
        foreach ([
            'app/Models/CollectionFollowUp.php',
            'app/Modules/RentCollection/Actions/RecordCollectionFollowUp.php',
            'app/Modules/RentCollection/Presenters/CollectionFollowUpPagePresenter.php',
            'app/Modules/RentCollection/Presenters/CollectionFollowUpPresenter.php',
            'app/Modules/RentCollection/Queries/CollectionAssigneeQuery.php',
            'app/Modules/RentCollection/Requests/StoreCollectionFollowUpRequest.php',
            'app/Modules/RentCollection/Support/CollectionFollowUpAccess.php',
            'app/Modules/RentCollection/Support/CollectionFollowUpOptions.php',
            'app/Modules/RentCollection/Support/CollectionFollowUpQueryState.php',
            'app/Modules/RentCollection/Support/CollectionFollowUpState.php',
            'resources/js/modules/rent-collection/follow-up-form.tsx',
            'resources/js/modules/rent-collection/follow-up-history.tsx',
            'resources/js/modules/rent-collection/follow-up-page.tsx',
            'resources/js/modules/rent-collection/follow-up-summary.tsx',
        ] as $path) {
            $this->assertFileExists($this->path($path));
        }
    }

    #[Test]
    public function collection_follow_up_composers_and_styles_remain_modular(): void
    {
        foreach ([
            'app/Modules/RentCollection/Actions/RecordCollectionFollowUp.php' => 100,
            'app/Modules/RentCollection/Presenters/CollectionFollowUpPagePresenter.php' => 170,
            'app/Modules/RentCollection/Presenters/CollectionFollowUpPresenter.php' => 90,
            'app/Modules/RentCollection/Queries/CollectionAssigneeQuery.php' => 75,
            'app/Modules/RentCollection/Support/CollectionFollowUpState.php' => 100,
            'resources/js/modules/rent-collection/follow-up-page.tsx' => 90,
            'resources/js/modules/rent-collection/follow-up-form.tsx' => 280,
            'resources/js/modules/rent-collection/follow-up-history.tsx' => 190,
            'resources/js/modules/rent-collection/follow-up-summary.tsx' => 220,
        ] as $path => $maximumLines) {
            $source = $this->source($path);

            $this->assertLessThanOrEqual(
                $maximumLines,
                substr_count($source, "\n") + 1,
                "{$path} should only coordinate focused collaborators.",
            );
        }

        $page = $this->source('resources/js/modules/rent-collection/follow-up-page.tsx');
        $this->assertStringContainsString("from './follow-up-form'", $page);
        $this->assertStringContainsString("from './follow-up-history'", $page);
        $this->assertStringContainsString("from './follow-up-summary'", $page);
        $this->assertStringNotContainsString('useForm', $page);

        $facade = $this->source('resources/css/styles/rent-collection/follow-up.css');
        $this->assertLessThanOrEqual(8, substr_count($facade, "\n") + 1);

        foreach (['base.css', 'summary.css', 'form.css', 'history.css', 'responsive.css'] as $file) {
            $path = "resources/css/styles/rent-collection/follow-up/{$file}";

            $this->assertStringContainsString(
                "@import './follow-up/{$file}';",
                $facade,
            );
            $this->assertLessThanOrEqual(
                190,
                substr_count($this->source($path), "\n") + 1,
                "{$path} is becoming a monolith.",
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
