<?php

namespace App\Modules\Exports\Support;

use App\Modules\Shared\ResourcePresenter;
use App\Modules\Wording\UiTranslationCatalog;
use App\Services\XlsxWorkbook;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ResourceWorkbook
{
    public function __construct(
        private readonly XlsxWorkbook $workbook,
        private readonly UiTranslationCatalog $translations,
        private readonly ResourcePresenter $resources,
    ) {}

    /**
     * @template TModel of Model
     *
     * @param  array<int, string>  $headers
     * @param  Builder<TModel>  $query
     * @param  callable(TModel): array<int, mixed>  $rowMapper
     */
    public function download(
        string $resource,
        array $headers,
        Builder $query,
        callable $rowMapper,
    ): BinaryFileResponse {
        $rows = [$this->labels($headers)];

        $query->reorder()->chunkByIdDesc(500, function ($records) use (&$rows, $rowMapper): void {
            foreach ($records as $record) {
                $rows[] = array_values($rowMapper($record));
            }
        });

        $path = $this->workbook->create(
            $rows,
            $this->copy(Str::of($resource)->replace('-', ' ')->headline()->toString()),
        );

        return response()->download(
            $path,
            $resource.'-export-'.now()->format('Ymd-His').'.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, string>
     */
    public function labels(array $labels): array
    {
        return array_map($this->copy(...), $labels);
    }

    public function copy(string $value): string
    {
        return $this->translations->text($value);
    }

    public function option(?string $value): string
    {
        if (blank($value)) {
            return '';
        }

        foreach (['status', 'roles'] as $group) {
            $key = "app.{$group}.{$value}";

            if (trans()->has($key)) {
                return trans($key);
            }
        }

        return $this->copy(Str::of($value)->replace('_', ' ')->headline()->toString());
    }

    public function date(mixed $value, bool $withTime = false): string
    {
        if (! $value instanceof CarbonInterface) {
            return '';
        }

        if (app()->isLocale('ar')) {
            return $value->format($withTime ? 'Y/m/d H:i' : 'Y/m/d');
        }

        return $value->format($withTime ? 'M j, Y H:i' : 'M j, Y');
    }

    public function localized(
        ?Model $model,
        string $englishAttribute,
        string $arabicAttribute,
    ): ?string {
        if ($model === null) {
            return null;
        }

        $english = $model->getAttribute($englishAttribute);
        $arabic = $model->getAttribute($arabicAttribute);

        return $this->resources->localized(
            is_string($english) ? $english : null,
            is_string($arabic) ? $arabic : null,
        );
    }

    public function yesNo(bool $value): string
    {
        return $this->copy($value ? 'Yes' : 'No');
    }
}
