<?php

namespace App\Modules\Portfolios\Actions;

use App\Models\Portfolio;
use App\Models\User;
use App\Modules\Portfolios\Support\PortfolioAccess;
use App\Support\PortfolioModules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManagePortfolios
{
    public function __construct(private readonly PortfolioAccess $access) {}

    /** @param array<string, mixed> $data */
    public function create(User $actor, array $data): Portfolio
    {
        $this->access->ensureCanCreate($actor);

        return DB::transaction(function () use ($data): Portfolio {
            $code = filled($data['code'] ?? null)
                ? (string) $data['code']
                : $this->uniqueCode();

            return Portfolio::query()->create([
                ...$data,
                'code' => $code,
                'slug' => $this->uniqueSlug((string) $data['name_en']),
                'module_settings' => PortfolioModules::normalize(
                    is_array($data['module_settings'] ?? null) ? $data['module_settings'] : null,
                ),
            ]);
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $actor, Portfolio $target, array $data): Portfolio
    {
        $this->access->ensureCanUpdate($actor, $target);

        return DB::transaction(function () use ($actor, $target, $data): Portfolio {
            $portfolio = Portfolio::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $this->access->ensureCanUpdate($actor, $portfolio);

            if ($portfolio->status !== 'archived' && $data['status'] === 'archived') {
                $this->ensureArchivable($portfolio);
            }

            $data['module_settings'] = PortfolioModules::normalize(
                is_array($data['module_settings'] ?? null)
                    ? $data['module_settings']
                    : $portfolio->module_settings,
            );
            $portfolio->update($data);

            return $portfolio->refresh();
        });
    }

    /** @return string|null A translated blocking reason, or null after success. */
    public function archive(User $actor, Portfolio $target): ?string
    {
        $this->access->ensureCanArchive($actor);

        return DB::transaction(function () use ($actor, $target): ?string {
            $portfolio = Portfolio::query()->lockForUpdate()->whereKey($target->id)->firstOrFail();
            $this->access->ensureCanArchive($actor);

            if ($portfolio->status === 'archived') {
                return null;
            }

            $blockingReason = $this->archivalBlocker($portfolio);

            if ($blockingReason !== null) {
                return $blockingReason;
            }

            $portfolio->update(['status' => 'archived']);

            return null;
        });
    }

    private function ensureArchivable(Portfolio $portfolio): void
    {
        $blockingReason = $this->archivalBlocker($portfolio);

        if ($blockingReason !== null) {
            throw ValidationException::withMessages(['status' => $blockingReason]);
        }
    }

    private function archivalBlocker(Portfolio $portfolio): ?string
    {
        if ($portfolio->leases()->where('status', 'active')->exists()) {
            return trans('app.errors.portfolio_has_active_leases');
        }

        if ($portfolio->maintenanceRequests()->whereIn('status', ['open', 'in_progress'])->exists()) {
            return trans('app.errors.portfolio_has_open_maintenance');
        }

        return null;
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Portfolio::query()->where('code', $code)->exists());

        return $code;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'portfolio';
        $slug = $base;

        while (Portfolio::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(5));
        }

        return $slug;
    }
}
