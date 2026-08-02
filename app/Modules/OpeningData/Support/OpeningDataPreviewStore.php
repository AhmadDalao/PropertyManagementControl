<?php

namespace App\Modules\OpeningData\Support;

use App\Models\Portfolio;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;

final class OpeningDataPreviewStore
{
    private const EXPIRY_MINUTES = 120;

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $data
     * @param  array<int, array{sheet:string,row:int|null,field:string|null,message:string}>  $issues
     */
    public function create(
        User $actor,
        Portfolio $portfolio,
        array $data,
        array $issues,
    ): string {
        $this->cleanup($actor);
        $token = Str::random(48);
        $counts = [];

        foreach (OpeningDataWorkbookSchema::SHEETS as $sheet => $_headers) {
            $counts[$sheet] = count($data[$sheet] ?? []);
        }

        $manifest = [
            'version' => 1,
            'actor_id' => $actor->id,
            'portfolio_id' => $portfolio->id,
            'portfolio' => [
                'id' => $portfolio->id,
                'code' => $portfolio->code,
                'name_en' => $portfolio->name_en,
                'name_ar' => $portfolio->name_ar,
            ],
            'created_at' => now()->toIso8601String(),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES)->toIso8601String(),
            'ready' => $issues === [],
            'counts' => $counts,
            'issues' => $issues,
            'data' => $data,
        ];

        try {
            Storage::disk('local')->put(
                $this->path($actor, $token),
                json_encode($manifest, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            );
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => trans('app.opening_data.errors.preview_store_failed'),
            ]);
        }

        return $token;
    }

    /** @return array<string, mixed> */
    public function load(User $actor, string $token): array
    {
        $this->ensureToken($actor, $token);
        $path = $this->path($actor, $token);
        $contents = Storage::disk('local')->get($path);

        try {
            $manifest = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->delete($actor, $token);
            throw $this->expired();
        }

        if (! is_array($manifest)
            || (int) ($manifest['actor_id'] ?? 0) !== $actor->id
            || ! isset($manifest['expires_at'])
            || CarbonImmutable::parse((string) $manifest['expires_at'])->isPast()) {
            $this->delete($actor, $token);
            throw $this->expired();
        }

        return $manifest;
    }

    public function delete(User $actor, string $token): void
    {
        if (! preg_match('/^[A-Za-z0-9]{48}$/', $token)) {
            return;
        }

        Storage::disk('local')->delete($this->path($actor, $token));
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, mixed>
     */
    public function publicPayload(string $token, array $manifest): array
    {
        $data = is_array($manifest['data'] ?? null) ? $manifest['data'] : [];
        $samples = [];

        foreach (OpeningDataWorkbookSchema::SHEETS as $sheet => $_headers) {
            $rows = is_array($data[$sheet] ?? null) ? $data[$sheet] : [];
            $samples[$sheet] = array_slice($rows, 0, 4);
        }

        $issues = is_array($manifest['issues'] ?? null) ? $manifest['issues'] : [];

        return [
            'token' => $token,
            'portfolio' => $manifest['portfolio'] ?? null,
            'expires_at' => $manifest['expires_at'] ?? null,
            'ready' => (bool) ($manifest['ready'] ?? false),
            'counts' => $manifest['counts'] ?? [],
            'issue_count' => count($issues),
            'issues' => array_slice($issues, 0, 100),
            'issues_truncated' => count($issues) > 100,
            'samples' => $samples,
        ];
    }

    private function cleanup(User $actor): void
    {
        $directory = 'opening-data/previews/'.$actor->id;

        foreach (Storage::disk('local')->files($directory) as $file) {
            $modified = Storage::disk('local')->lastModified($file);

            if ($modified < now()->subDay()->timestamp) {
                Storage::disk('local')->delete($file);
            }
        }
    }

    private function path(User $actor, string $token): string
    {
        return "opening-data/previews/{$actor->id}/{$token}.json";
    }

    private function ensureToken(User $actor, string $token): void
    {
        if (! preg_match('/^[A-Za-z0-9]{48}$/', $token)
            || ! Storage::disk('local')->exists($this->path($actor, $token))) {
            throw $this->expired();
        }
    }

    private function expired(): ValidationException
    {
        return ValidationException::withMessages([
            'preview_token' => trans('app.opening_data.errors.preview_expired'),
        ]);
    }
}
