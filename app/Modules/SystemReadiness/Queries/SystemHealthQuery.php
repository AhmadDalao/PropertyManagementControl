<?php

namespace App\Modules\SystemReadiness\Queries;

use App\Models\Document;
use App\Modules\SystemReadiness\Actions\RecordSchedulerHeartbeat;
use App\Modules\SystemReadiness\Support\MailReadiness;
use App\Modules\SystemReadiness\Support\ReadinessLocale;
use App\Modules\SystemReadiness\Support\SchedulerHeartbeatHistory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class SystemHealthQuery
{
    public function __construct(
        private readonly MailReadiness $mail,
        private readonly ReadinessLocale $locale,
    ) {}

    /** @return list<array<string, mixed>> */
    public function checks(): array
    {
        return [
            $this->environment(),
            $this->runtime(),
            $this->mail(),
            $this->scheduler(),
            $this->queue(),
            $this->storage(),
            $this->documents(),
        ];
    }

    /** @return array<string, mixed> */
    private function environment(): array
    {
        $production = app()->environment('production');
        $safe = ! (bool) config('app.debug');
        $https = str_starts_with((string) config('app.url'), 'https://');
        $ready = $production && $safe && $https;

        return $this->check(
            'environment',
            $ready ? 'ready' : ($production ? 'blocked' : 'attention'),
            trans($ready ? 'app.readiness.environment_ready' : 'app.readiness.environment_attention'),
        );
    }

    /** @return array<string, mixed> */
    private function runtime(): array
    {
        $extensions = ['calendar', 'mbstring'];
        $missing = array_values(array_filter($extensions, fn (string $extension): bool => ! extension_loaded($extension)));
        $runtimeVersion = phpversion();
        $versionReady = version_compare($runtimeVersion, '8.4.1', '>=');
        $ready = $versionReady && $missing === [];

        return $this->check(
            'runtime',
            $ready ? 'ready' : 'blocked',
            $ready
                ? trans('app.readiness.runtime_ready', ['version' => PHP_VERSION])
                : trans('app.readiness.runtime_blocked', [
                    'version' => PHP_VERSION,
                    'extensions' => $missing === [] ? trans('app.readiness.none') : implode(', ', $missing),
                ]),
        );
    }

    /** @return array<string, mixed> */
    private function mail(): array
    {
        $configured = $this->mail->configured();

        return $this->check(
            'mail',
            $configured ? 'ready' : 'blocked',
            trans(
                $configured ? 'app.readiness.mail_ready' : 'app.readiness.mail_blocked',
                ['mailer' => $this->mail->mailer()],
            ),
        );
    }

    /** @return array<string, mixed> */
    private function scheduler(): array
    {
        $history = SchedulerHeartbeatHistory::from(
            Cache::get(RecordSchedulerHeartbeat::CACHE_KEY),
        );
        $lastSeen = $history->latest();

        if (! $lastSeen) {
            return $this->check('scheduler', 'blocked', trans('app.readiness.scheduler_missing'));
        }

        $minutes = max(0, (int) $lastSeen->diffInMinutes(now()));
        $cadenceConfirmed = $history->cadenceConfirmed();
        $status = $minutes <= 3 && $cadenceConfirmed
            ? 'ready'
            : ($minutes <= 15 ? 'attention' : 'blocked');
        $detail = $minutes <= 3 && ! $cadenceConfirmed
            ? trans('app.readiness.scheduler_unconfirmed', [
                'count' => $this->locale->number($history->sampleCount()),
            ])
            : trans('app.readiness.scheduler_seen', [
                'minutes' => $this->locale->number($minutes),
            ]);

        return $this->check(
            'scheduler',
            $status,
            $detail,
            [
                'last_seen_at' => $lastSeen->toIso8601String(),
                'sample_count' => $history->sampleCount(),
                'cadence_confirmed' => $cadenceConfirmed,
            ],
        );
    }

    /** @return array<string, mixed> */
    private function queue(): array
    {
        try {
            $pending = (int) DB::table('jobs')->count();
            $failed = (int) DB::table('failed_jobs')->count();
            $oldest = DB::table('jobs')->min('available_at');
            $oldestTimestamp = filter_var($oldest, FILTER_VALIDATE_INT);
            $oldestMinutes = is_int($oldestTimestamp)
                ? max(0, (int) CarbonImmutable::createFromTimestamp($oldestTimestamp)->diffInMinutes(now()))
                : 0;
            $databaseQueue = config('queue.default') === 'database';
            $status = ! $databaseQueue || $failed > 0 || $oldestMinutes > 15
                ? 'blocked'
                : ($pending > 0 ? 'attention' : 'ready');

            return $this->check(
                'queue',
                $status,
                trans('app.readiness.queue_detail', [
                    'connection' => (string) config('queue.default'),
                    'pending' => $this->locale->number($pending),
                    'failed' => $this->locale->number($failed),
                    'oldest' => $this->locale->number($oldestMinutes),
                ]),
                [
                    'pending' => $pending,
                    'failed' => $failed,
                    'oldest_minutes' => $oldestMinutes,
                ],
            );
        } catch (Throwable) {
            return $this->check('queue', 'blocked', trans('app.readiness.queue_unavailable'));
        }
    }

    /** @return array<string, mixed> */
    private function storage(): array
    {
        $privatePath = storage_path('app/private');
        $publicPath = storage_path('app/public');
        $publicTarget = public_path('storage');
        $ready = is_dir($privatePath)
            && is_writable($privatePath)
            && is_dir($publicPath)
            && is_writable($publicPath)
            && file_exists($publicTarget);

        return $this->check(
            'storage',
            $ready ? 'ready' : 'blocked',
            trans($ready ? 'app.readiness.storage_ready' : 'app.readiness.storage_blocked'),
        );
    }

    /** @return array<string, mixed> */
    private function documents(): array
    {
        $total = Document::query()->count();
        $sample = Document::query()->latest('id')->limit(50)->get(['disk', 'file_path']);
        $missing = 0;

        foreach ($sample as $document) {
            try {
                if (! Storage::disk((string) $document->disk)->exists((string) $document->file_path)) {
                    $missing++;
                }
            } catch (Throwable) {
                $missing++;
            }
        }

        $status = $missing > 0 ? 'blocked' : ($total > 0 ? 'ready' : 'attention');

        return $this->check(
            'documents',
            $status,
            trans('app.readiness.documents_detail', [
                'total' => $this->locale->number($total),
                'sample' => $this->locale->number($sample->count()),
                'missing' => $this->locale->number($missing),
            ]),
            ['total' => $total, 'sampled' => $sample->count(), 'missing' => $missing],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    private function check(string $key, string $status, string $detail, array $meta = []): array
    {
        return [
            'key' => $key,
            'label' => trans("app.readiness.automatic.{$key}.label"),
            'description' => trans("app.readiness.automatic.{$key}.description"),
            'status' => $status,
            'detail' => $detail,
            'meta' => $meta,
        ];
    }
}
