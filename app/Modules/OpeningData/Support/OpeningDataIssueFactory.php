<?php

namespace App\Modules\OpeningData\Support;

final class OpeningDataIssueFactory
{
    /**
     * @param  array<string, mixed>  $row
     * @return array{sheet:string,row:int|null,field:string|null,message:string}
     */
    public function row(
        string $sheet,
        array $row,
        ?string $field,
        mixed $message,
    ): array {
        return [
            'sheet' => $sheet,
            'row' => isset($row['_row']) ? (int) $row['_row'] : null,
            'field' => $field,
            'message' => is_string($message) ? $message : (string) $message,
        ];
    }
}
