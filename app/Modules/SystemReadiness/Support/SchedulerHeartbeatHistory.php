<?php

namespace App\Modules\SystemReadiness\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class SchedulerHeartbeatHistory
{
    private const MAX_SAMPLES = 5;

    private const MIN_SAMPLE_GAP_SECONDS = 30;

    private const REQUIRED_SAMPLES = 3;

    private const REQUIRED_SPAN_SECONDS = 90;

    /** @param list<CarbonImmutable> $samples */
    private function __construct(
        private array $samples,
    ) {}

    public static function from(mixed $stored): self
    {
        $values = is_string($stored)
            ? [$stored]
            : (is_array($stored) && is_array($stored['samples'] ?? null)
                ? $stored['samples']
                : []);
        $samples = [];

        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }

            try {
                $samples[] = CarbonImmutable::parse($value);
            } catch (Throwable) {
                continue;
            }
        }

        usort(
            $samples,
            fn (CarbonImmutable $left, CarbonImmutable $right): int => $left->getTimestamp() <=> $right->getTimestamp(),
        );

        return new self(array_slice($samples, -self::MAX_SAMPLES));
    }

    public function record(CarbonImmutable $recordedAt): self
    {
        $samples = $this->samples;
        $latest = $this->latest();

        if (! $latest || $latest->diffInSeconds($recordedAt, true) >= self::MIN_SAMPLE_GAP_SECONDS) {
            $samples[] = $recordedAt;
        } elseif ($recordedAt->greaterThan($latest)) {
            array_pop($samples);
            $samples[] = $recordedAt;
        }

        usort(
            $samples,
            fn (CarbonImmutable $left, CarbonImmutable $right): int => $left->getTimestamp() <=> $right->getTimestamp(),
        );

        return new self(array_slice($samples, -self::MAX_SAMPLES));
    }

    public function latest(): ?CarbonImmutable
    {
        return $this->samples === [] ? null : $this->samples[array_key_last($this->samples)];
    }

    public function cadenceConfirmed(): bool
    {
        if (count($this->samples) < self::REQUIRED_SAMPLES) {
            return false;
        }

        $samples = array_slice($this->samples, -self::REQUIRED_SAMPLES);
        $first = reset($samples);
        $latest = end($samples);

        if (! $first instanceof CarbonImmutable || ! $latest instanceof CarbonImmutable) {
            return false;
        }

        return $first->diffInSeconds($latest, true) >= self::REQUIRED_SPAN_SECONDS;
    }

    public function sampleCount(): int
    {
        return count($this->samples);
    }

    /** @return array{version: int, samples: list<string>} */
    public function toCacheValue(): array
    {
        return [
            'version' => 2,
            'samples' => array_map(
                fn (CarbonImmutable $sample): string => $sample->toIso8601String(),
                $this->samples,
            ),
        ];
    }
}
