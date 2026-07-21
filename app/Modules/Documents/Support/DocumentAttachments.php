<?php

namespace App\Modules\Documents\Support;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Lease;
use App\Models\Payment;
use App\Modules\Shared\MorphTypes;
use App\Modules\Shared\ResourcePresenter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class DocumentAttachments
{
    public function __construct(
        private readonly MorphTypes $morphTypes,
        private readonly ResourcePresenter $resources,
    ) {}

    public function resolve(string $alias, int $id): Asset|Lease|Payment
    {
        $model = match ($alias) {
            'lease' => Lease::query()->find($id),
            'asset' => Asset::query()->find($id),
            'payment' => Payment::query()->find($id),
            default => throw ValidationException::withMessages([
                'documentable_type' => trans('app.errors.unsupported_document_attachment'),
            ]),
        };

        if (! $model) {
            throw (new ModelNotFoundException)->setModel($this->classFor($alias), [$id]);
        }

        return $model;
    }

    public function aliasForModel(Model $model): ?string
    {
        return match (true) {
            $model instanceof Lease => 'lease',
            $model instanceof Asset => 'asset',
            $model instanceof Payment => 'payment',
            default => null,
        };
    }

    public function aliasForDocument(Document $document): ?string
    {
        foreach (DocumentOptions::ATTACHMENTS as $alias) {
            if ($this->matches($document, $alias)) {
                return $alias;
            }
        }

        return null;
    }

    public function matches(Document $document, string $alias): bool
    {
        return in_array($document->documentable_type, $this->typesFor($alias), true);
    }

    /** @return array<int, string> */
    public function typesFor(string $alias): array
    {
        return match ($alias) {
            'lease' => $this->morphTypes->for(new Lease),
            'asset' => $this->morphTypes->for(new Asset),
            'payment' => $this->morphTypes->for(new Payment),
            default => [$alias],
        };
    }

    /** @return array{type:string,label:string,url:string}|null */
    public function present(?Model $model): ?array
    {
        return match (true) {
            $model instanceof Lease => [
                'type' => 'lease',
                'label' => $model->code,
                'url' => route('leases.show', $model),
            ],
            $model instanceof Asset => [
                'type' => 'asset',
                'label' => $this->resources->localized($model->title_en, $model->title_ar) ?: $model->code,
                'url' => route('assets.show', $model),
            ],
            $model instanceof Payment => [
                'type' => 'payment',
                'label' => $model->reference ?: '#'.$model->id,
                'url' => route('payments.show', $model),
            ],
            default => null,
        };
    }

    /** @return class-string<Asset|Lease|Payment> */
    private function classFor(string $alias): string
    {
        return match ($alias) {
            'lease' => Lease::class,
            'asset' => Asset::class,
            'payment' => Payment::class,
            default => Lease::class,
        };
    }
}
