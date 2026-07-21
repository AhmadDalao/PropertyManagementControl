<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Search\Presenters\SearchResultPresenter;
use App\Modules\Search\Support\ModuleSearchSource;
use App\Modules\Shared\TableQuery;

class DocumentSearch extends ModuleSearchSource
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly TableQuery $tables,
        private readonly SearchResultPresenter $results,
    ) {}

    public function results(User $actor, string $query): array
    {
        if ((! $this->isManager($actor) && ! $actor->hasRole('tenant'))
            || ! $this->moduleEnabled($actor, 'documents')) {
            return [];
        }

        $documents = $actor->hasRole('tenant')
            ? $this->access->tenantPortalScope(Document::query(), $actor)
            : $this->access->directoryScope(Document::query(), $actor);
        $this->tables->search($documents, $query, [
            'title_en',
            'title_ar',
            'original_name',
            'type',
        ]);

        return $documents
            ->limit(5)
            ->get()
            ->map(fn (Document $document): array => $this->results->result(
                $actor->hasRole('tenant') ? trans('app.search.my_documents') : trans('app.nav.documents'),
                $this->results->localized($document->title_en, $document->title_ar),
                $document->original_name,
                $this->results->status($document->type),
                $actor->hasRole('tenant')
                    ? route('documents.download', $document)
                    : route('documents.show', $document),
            ))
            ->all();
    }
}
