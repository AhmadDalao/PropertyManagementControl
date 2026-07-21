<?php

namespace App\Modules\Shared;

use App\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

class ResourcePresenter
{
    public function localized(?string $english, ?string $arabic): ?string
    {
        $primary = app()->isLocale('ar') ? $arabic : $english;
        $fallback = app()->isLocale('ar') ? $english : $arabic;
        $value = trim((string) ($primary ?: $fallback));

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function detailItems(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => isset($item['value']) && $item['value'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, array{label:string,value:string}>
     */
    public function fieldOptions(array $values): array
    {
        return collect($values)
            ->map(fn (string $value) => [
                'value' => $value,
                'label' => str($value)->replace('_', ' ')->headline()->toString(),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, array{description:string,fields:array<int, string>}>  $sections
     * @return array<int, array<string, mixed>>
     */
    public function sectionFields(array $fields, array $sections): array
    {
        return collect($fields)->map(function (array $field) use ($sections): array {
            foreach ($sections as $section => $definition) {
                if (in_array($field['name'], $definition['fields'], true)) {
                    return [
                        ...$field,
                        'section' => $section,
                        'sectionDescription' => $definition['description'],
                    ];
                }
            }

            return $field;
        })->all();
    }

    /**
     * @param  Collection<int, Document>|array<int, Document>  $documents
     * @return array<int, array<string, mixed>>
     */
    public function documentStrip(Collection|array $documents): array
    {
        return collect($documents)
            ->map(fn (Document $document) => [
                'id' => $document->id,
                'title' => $this->localized($document->title_en, $document->title_ar) ?: $document->original_name,
                'subtitle' => trim(($document->type ?: 'document').' · '.($document->original_name ?: 'file')),
                'badge' => $document->is_public
                    ? trans('app.documents.portal_visible')
                    : trans('app.documents.internal'),
                'href' => route('documents.download', $document),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activityTimeline(Model $model, int $limit = 8): array
    {
        $subjectTypes = array_values(array_unique([$model::class, $model->getMorphClass()]));

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
                'causer' => data_get($activity, 'causer.name', 'System'),
                'created_at' => $activity->created_at?->toDateTimeString(),
            ])
            ->all();
    }
}
