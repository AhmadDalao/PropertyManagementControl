<?php

namespace App\Modules\Dashboard\Presenters;

use App\Models\Portfolio;
use App\Modules\Audit\Presenters\AuditActivityPresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

final readonly class PlatformActivityPresenter
{
    public function __construct(private AuditActivityPresenter $activities) {}

    /**
     * @param  Collection<int, Portfolio>  $portfolios
     * @return array<string, mixed>|null
     */
    public function present(Activity $activity, Collection $portfolios): ?array
    {
        $subject = $activity->subject;

        if (! $subject instanceof Model) {
            return null;
        }

        $portfolioId = $this->portfolioId($subject);
        $portfolio = $portfolioId === null ? null : $portfolios->get($portfolioId);

        if ($this->isShowcase($subject, $portfolioId, $portfolio)) {
            return null;
        }

        $row = $this->activities->row($activity);

        if (! is_string($row['subject_url']) || $row['subject_url'] === '') {
            return null;
        }

        return [
            ...$row,
            'portfolio' => $portfolio instanceof Portfolio ? [
                'id' => $portfolio->id,
                'name' => app()->isLocale('ar')
                    ? ($portfolio->name_ar ?: $portfolio->name_en)
                    : ($portfolio->name_en ?: $portfolio->name_ar),
                'url' => route('portfolios.show', $portfolio),
            ] : null,
        ];
    }

    public function portfolioId(?Model $subject): ?int
    {
        if ($subject instanceof Portfolio) {
            return (int) $subject->id;
        }

        $value = $subject?->getAttribute('portfolio_id');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function isShowcase(
        Model $subject,
        ?int $portfolioId,
        mixed $portfolio,
    ): bool {
        if ($subject->getAttribute('showcase_dataset_id') !== null) {
            return true;
        }

        return $portfolioId !== null
            && (! $portfolio instanceof Portfolio || $portfolio->showcase_dataset_id !== null);
    }
}
