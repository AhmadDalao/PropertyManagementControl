<?php

namespace Tests\Feature;

use App\Models\SystemBackupRun;
use App\Modules\SystemBackups\Actions\CreateSystemBackup;
use App\Modules\SystemBackups\Contracts\DatabaseBackupWriter;
use App\Modules\SystemBackups\Contracts\DocumentBackupWriter;
use App\Modules\SystemBackups\Jobs\CreateSystemBackupJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SystemBackupControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_backup_control_is_superadmin_only_and_localized(): void
    {
        $portfolio = $this->createPortfolio();
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $portfolio);

        SystemBackupRun::query()->create([
            'initiated_by_user_id' => $superadmin->id,
            'status' => 'completed',
            'trigger' => 'manual',
            'archive_disk' => 'local',
            'archive_path' => 'system-backups/ready.tar.gz',
            'archive_bytes' => 2048,
            'table_count' => 12,
            'database_row_count' => 340,
            'document_count' => 8,
            'archive_sha256' => str_repeat('a', 64),
            'completed_at' => now(),
        ]);
        Storage::disk('local')->put('system-backups/ready.tar.gz', 'archive');

        $this->actingAs($superadmin)
            ->get(route('system-backups.index', ['locale' => 'ar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/system-backups/index')
                ->has('backups.data', 1)
                ->where('backups.data.0.status_label', 'مكتملة')
                ->where('backups.data.0.archive_available', true)
                ->where('backups.data.0.can_download', true)
                ->where('summary.completed', 1)
                ->where('summary.stored_bytes', 2048));

        $this->actingAs($superadmin)
            ->withSession(['locale' => 'ar'])
            ->get('/documentation/backup-control')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/documentation/show')
                ->where('guide.slug', 'backup-control')
                ->where('guide.title', 'إدارة النسخ الاحتياطية'));

        $this->actingAs($owner)
            ->get(route('system-backups.index'))
            ->assertForbidden();

        $this->actingAs($owner)
            ->post(route('system-backups.store'))
            ->assertForbidden();
    }

    public function test_superadmin_can_queue_only_one_backup_at_a_time(): void
    {
        Queue::fake();
        $superadmin = $this->createUserWithRole('superadmin');

        $this->actingAs($superadmin)
            ->post(route('system-backups.store'))
            ->assertRedirect(route('system-backups.index'))
            ->assertSessionHas('success');

        $run = SystemBackupRun::query()->sole();

        $this->assertSame('queued', $run->status);
        $this->assertSame('manual', $run->trigger);
        $this->assertSame($superadmin->id, $run->initiated_by_user_id);
        Queue::assertPushed(
            CreateSystemBackupJob::class,
            fn (CreateSystemBackupJob $job): bool => $job->runId === $run->id,
        );

        $this->actingAs($superadmin)
            ->post(route('system-backups.store'))
            ->assertSessionHasErrors('backup');

        $this->assertDatabaseCount('system_backup_runs', 1);
    }

    public function test_completed_package_can_be_downloaded_and_pruned_without_erasing_audit(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $owner = $this->createUserWithRole('owner', $this->createPortfolio());
        $path = 'system-backups/downloadable.tar.gz';
        Storage::disk('local')->put($path, "\x1f\x8bbackup");
        $run = SystemBackupRun::query()->create([
            'status' => 'completed',
            'trigger' => 'scheduled',
            'archive_disk' => 'local',
            'archive_path' => $path,
            'archive_bytes' => 8,
            'archive_sha256' => hash('sha256', "\x1f\x8bbackup"),
            'completed_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('system-backups.download', $run))
            ->assertForbidden();

        $this->actingAs($superadmin)
            ->get(route('system-backups.download', $run))
            ->assertOk()
            ->assertHeader('content-type', 'application/gzip')
            ->assertDownload('downloadable.tar.gz');

        $this->actingAs($superadmin)
            ->delete(route('system-backups.destroy', $run))
            ->assertRedirect(route('system-backups.index'))
            ->assertSessionHas('success');

        $this->assertFalse(Storage::disk('local')->exists($path));
        $this->assertDatabaseHas('system_backup_runs', [
            'id' => $run->id,
            'status' => 'pruned',
            'archive_path' => null,
        ]);
    }

    public function test_backup_action_builds_a_private_gzip_package_and_applies_retention(): void
    {
        config()->set('operations.backup_retention_count', 1);
        $this->bindBackupWriters();
        $oldPath = 'system-backups/old.tar.gz';
        Storage::disk('local')->put($oldPath, 'old');
        $old = SystemBackupRun::query()->create([
            'status' => 'completed',
            'trigger' => 'scheduled',
            'archive_disk' => 'local',
            'archive_path' => $oldPath,
            'archive_bytes' => 3,
            'completed_at' => now()->subWeek(),
        ]);
        $run = SystemBackupRun::query()->create([
            'status' => 'queued',
            'trigger' => 'command',
            'archive_disk' => 'local',
        ]);

        $completed = app(CreateSystemBackup::class)->handle($run->id);

        $this->assertSame('completed', $completed->status);
        $this->assertSame(4, $completed->table_count);
        $this->assertSame(25, $completed->database_row_count);
        $this->assertSame(3, $completed->document_count);
        $this->assertNotNull($completed->archive_path);
        $this->assertTrue(Storage::disk('local')->exists((string) $completed->archive_path));
        $this->assertSame(
            "\x1f\x8b",
            substr((string) Storage::disk('local')->get((string) $completed->archive_path), 0, 2),
        );
        $this->assertSame(
            hash('sha256', (string) Storage::disk('local')->get((string) $completed->archive_path)),
            $completed->archive_sha256,
        );
        $this->assertSame('pruned', $old->refresh()->status);
        $this->assertFalse(Storage::disk('local')->exists($oldPath));
    }

    public function test_readiness_measures_backup_availability_without_auto_confirming_restore(): void
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $path = 'system-backups/current.tar.gz';
        Storage::disk('local')->put($path, "\x1f\x8bcurrent");
        SystemBackupRun::query()->create([
            'status' => 'completed',
            'trigger' => 'manual',
            'archive_disk' => 'local',
            'archive_path' => $path,
            'archive_bytes' => 10,
            'completed_at' => now()->subDay(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('system-readiness.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemChecks', fn (mixed $checks): bool => collect($checks)
                    ->contains(fn (array $check): bool => $check['key'] === 'backups'
                        && $check['status'] === 'ready'
                        && str_contains((string) $check['href'], '/system/backups')))
                ->where('systemConfirmations', fn (mixed $checks): bool => collect($checks)
                    ->contains(fn (array $check): bool => $check['key'] === 'restore_drill'
                        && $check['is_confirmed'] === false)));
    }

    private function bindBackupWriters(): void
    {
        $this->app->bind(
            DatabaseBackupWriter::class,
            fn (): DatabaseBackupWriter => new class implements DatabaseBackupWriter
            {
                public function write(string $outputPath): array
                {
                    File::ensureDirectoryExists(dirname($outputPath));
                    File::put($outputPath, "\x1f\x8bdatabase");

                    return [
                        'table_count' => 4,
                        'row_count' => 25,
                        'bytes' => File::size($outputPath),
                        'sha256' => hash_file('sha256', $outputPath),
                    ];
                }
            },
        );
        $this->app->bind(
            DocumentBackupWriter::class,
            fn (): DocumentBackupWriter => new class implements DocumentBackupWriter
            {
                public function write(string $outputPath): array
                {
                    File::ensureDirectoryExists(dirname($outputPath));
                    File::put($outputPath, "\x1f\x8bdocuments");

                    return [
                        'file_count' => 3,
                        'source_bytes' => 128,
                        'archive_bytes' => File::size($outputPath),
                        'sha256' => hash_file('sha256', $outputPath),
                    ];
                }
            },
        );
    }
}
