<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $initiated_by_user_id
 * @property string $status
 * @property string $trigger
 * @property string $archive_disk
 * @property string|null $archive_path
 * @property int $database_bytes
 * @property int $documents_bytes
 * @property int $archive_bytes
 * @property int $table_count
 * @property int $database_row_count
 * @property int $document_count
 * @property string|null $archive_sha256
 * @property string|null $failure_summary
 * @property array<string, mixed>|null $meta_json
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $failed_at
 * @property-read User|null $initiatedBy
 */
class SystemBackupRun extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'database_bytes' => 'integer',
            'documents_bytes' => 'integer',
            'archive_bytes' => 'integer',
            'table_count' => 'integer',
            'database_row_count' => 'integer',
            'document_count' => 'integer',
            'meta_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
