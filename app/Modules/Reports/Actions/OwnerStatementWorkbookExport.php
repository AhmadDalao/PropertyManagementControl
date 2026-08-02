<?php

namespace App\Modules\Reports\Actions;

use App\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class OwnerStatementWorkbookExport
{
    public function __construct(private ReportWorkbookExport $workbook) {}

    /**
     * @param  array<string, mixed>  $statement
     * @param  array{period:string,date_from:string,date_to:string,portfolio_id:int|null,property_id:int|null}  $filters
     */
    public function download(array $statement, array $filters, User $actor): BinaryFileResponse
    {
        return $this->workbook->download(
            $statement,
            $filters,
            $actor,
            'owner-statement',
        );
    }
}
