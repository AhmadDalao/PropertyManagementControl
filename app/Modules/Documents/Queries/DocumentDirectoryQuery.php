<?php

namespace App\Modules\Documents\Queries;

use App\Models\Document;
use App\Models\User;
use App\Modules\Documents\Support\DocumentAccess;
use App\Modules\Shared\TableQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final class DocumentDirectoryQuery
{
    public function __construct(
        private readonly DocumentAccess $access,
        private readonly TableQuery $tables,
    ) {}

    /** @return Builder<Document> */
    public function base(User $actor): Builder
    {
        return $this->access->directoryScope(Document::query(), $actor);
    }

    /**
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    public function listing(Builder $query): Builder
    {
        return $query
            ->select([
                'id',
                'portfolio_id',
                'uploaded_by_user_id',
                'documentable_type',
                'documentable_id',
                'type',
                'title_en',
                'title_ar',
                'original_name',
                'mime_type',
                'file_size',
                'is_public',
                'created_at',
            ])
            ->with(['portfolio:id,name_en,name_ar', 'uploadedBy:id,name', 'documentable']);
    }

    /**
     * @param  Builder<Document>  $query
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Document>
     */
    public function paginate(Builder $query, array $filters): LengthAwarePaginator
    {
        return $this->tables->paginate($query, $filters, [
            'created_at',
            'title_en',
            'type',
            'original_name',
            'file_size',
        ]);
    }
}
