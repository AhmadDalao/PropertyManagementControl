<?php

namespace App\Modules\Audit\Presenters;

use App\Modules\Audit\Support\AuditSubjectRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class AuditActivityPresenter
{
    public function __construct(private readonly AuditSubjectRegistry $subjects) {}

    /** @return array<string, mixed> */
    public function row(Activity $activity): array
    {
        $event = $activity->event ?: 'activity';
        $changedKeys = $this->changedKeys($activity);

        return [
            'id' => $activity->id,
            'event' => $event,
            'event_label' => $this->eventLabel($event),
            'description' => $activity->description,
            'subject_type' => $this->subjects->alias($activity->subject_type),
            'subject_type_label' => $this->subjects->label($activity->subject_type),
            'subject_label' => $this->modelLabel($activity->subject, $activity->subject_id, $activity->subject_type),
            'subject_url' => $this->subjects->url($activity->subject, $activity->subject_type),
            'causer_label' => $this->modelLabel(
                $activity->causer,
                $activity->causer_id,
                $activity->causer_type,
                trans('app.audit.system'),
            ),
            'changed_keys' => $changedKeys,
            'changed_count' => count($changedKeys),
            'created_at' => $activity->created_at?->toDateTimeString(),
        ];
    }

    /** @return array<int, string> */
    private function changedKeys(Activity $activity): array
    {
        $changes = $activity->attribute_changes instanceof Collection
            ? $activity->attribute_changes->toArray()
            : (array) $activity->attribute_changes;
        $properties = $activity->properties instanceof Collection
            ? $activity->properties->toArray()
            : (array) $activity->properties;
        $attributes = $changes['attributes'] ?? $properties['attributes'] ?? [];

        return collect(array_keys((array) $attributes))
            ->map(fn (int|string $key): string => (string) $key)
            ->reject(fn (string $key): bool => Str::contains(Str::lower($key), [
                'password',
                'remember_token',
                'secret',
                'token',
            ]))
            ->values()
            ->all();
    }

    private function modelLabel(
        ?Model $model,
        ?int $id,
        ?string $type,
        ?string $fallback = null,
    ): string {
        if ($model === null) {
            return $type !== null && $id !== null
                ? trans('app.audit.record_number', [
                    'type' => $this->subjects->label($type),
                    'id' => $id,
                ])
                : ($fallback ?? trans('app.audit.unknown'));
        }

        $localized = app()->isLocale('ar')
            ? ['name_ar', 'title_ar', 'name_en', 'title_en']
            : ['name_en', 'title_en', 'name_ar', 'title_ar'];

        foreach ([...$localized, 'name', 'code', 'reference', 'email', 'original_name', 'title', 'company_name', 'national_id', 'key'] as $attribute) {
            $value = trim((string) $model->getAttribute($attribute));

            if ($value !== '') {
                return $value;
            }
        }

        return trans('app.audit.record_number', [
            'type' => $this->subjects->label($type ?? $model->getMorphClass()),
            'id' => $model->getKey(),
        ]);
    }

    private function eventLabel(string $event): string
    {
        $key = "app.audit.events.{$event}";

        return trans()->has($key)
            ? trans($key)
            : str($event)->replace('_', ' ')->title()->toString();
    }
}
