<?php

namespace App\Models;

use App\Models\Concerns\LogsModelActivity;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $portfolio_id
 * @property int|null $initiated_by_user_id
 * @property string $status
 * @property string $trigger
 * @property CarbonInterface $report_date
 * @property string|null $schedule_key
 * @property string $storage_disk
 * @property string|null $pdf_path
 * @property string|null $docx_path
 * @property string|null $xlsx_path
 * @property int $pdf_bytes
 * @property int $docx_bytes
 * @property int $xlsx_bytes
 * @property int $item_count
 * @property array<string, mixed>|null $summary_json
 * @property array<int, array{label:string,value:string}>|null $scope_json
 * @property string|null $failure_summary
 * @property CarbonInterface|null $started_at
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $failed_at
 * @property-read Portfolio|null $portfolio
 * @property-read User|null $initiatedBy
 */
class DailyOperationsReportRun extends Model
{
    use LogsModelActivity;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'report_date' => 'date',
            'pdf_bytes' => 'integer',
            'docx_bytes' => 'integer',
            'xlsx_bytes' => 'integer',
            'item_count' => 'integer',
            'summary_json' => 'array',
            'scope_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Portfolio, $this> */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class);
    }

    /** @return BelongsTo<User, $this> */
    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
