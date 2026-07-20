<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

trait BuildsResourcePages
{
    /**
     * @return array<int, array{label:string,value:mixed,href?:string|null,tone?:string|null}>
     */
    protected function detailItems(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => ($item['value'] ?? null) !== null && ($item['value'] ?? '') !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label:string,value:string|int}>
     */
    protected function selectOptions(iterable $records, string $valueKey, string $labelKey): array
    {
        return collect($records)
            ->map(fn ($record) => [
                'value' => data_get($record, $valueKey),
                'label' => (string) data_get($record, $labelKey),
            ])
            ->filter(fn (array $option): bool => $option['value'] !== null && $option['label'] !== '')
            ->values()
            ->all();
    }

    protected function localized(?string $english, ?string $arabic): ?string
    {
        $primary = app()->isLocale('ar') ? $arabic : $english;
        $fallback = app()->isLocale('ar') ? $english : $arabic;
        $value = trim((string) ($primary ?: $fallback));

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    protected function fieldOptions(array $values): array
    {
        return collect($values)
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => str($value)->replace('_', ' ')->headline()->toString(),
            ])
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function documentStrip(Collection|array $documents): array
    {
        return collect($documents)
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $this->localized($document->title_en, $document->title_ar) ?: $document->original_name,
                'subtitle' => trim(($document->type ?: 'document').' · '.($document->original_name ?: 'file')),
                'badge' => $document->is_public ? 'Public' : 'Private',
                'href' => route('documents.download', $document),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function activityTimeline(Model $model, int $limit = 8): array
    {
        $subjectTypes = array_values(array_unique([
            $model::class,
            $model->getMorphClass(),
        ]));

        return Activity::query()
            ->with('causer')
            ->whereIn('subject_type', $subjectTypes)
            ->where('subject_id', $model->getKey())
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'event' => $activity->event ?: $activity->description,
                'description' => $activity->description,
                'causer' => $activity->causer?->name ?? 'System',
                'created_at' => $activity->created_at?->toDateTimeString(),
            ])
            ->all();
    }
}
