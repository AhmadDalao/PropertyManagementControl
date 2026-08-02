<?php

namespace App\Modules\SystemBackups\Support;

use App\Modules\SystemBackups\Contracts\DatabaseBackupWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use RuntimeException;

final class MySqlDatabaseBackupWriter implements DatabaseBackupWriter
{
    private const CHUNK_SIZE = 250;

    public function write(string $outputPath): array
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            throw new RuntimeException(trans('app.backups.mysql_required'));
        }

        File::ensureDirectoryExists(dirname($outputPath));
        File::delete($outputPath);
        $stream = gzopen($outputPath, 'wb9');

        if ($stream === false) {
            throw new RuntimeException(trans('app.backups.database_open_failed'));
        }

        $tableCount = 0;
        $rowCount = 0;

        try {
            $connection->statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $connection->beginTransaction();
            $this->writeLine($stream, '-- Property Management Control database backup');
            $this->writeLine($stream, '-- Generated: '.now()->toIso8601String());
            $this->writeLine($stream, 'SET NAMES utf8mb4;');
            $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS=0;');
            $this->writeLine($stream);

            $tables = collect(DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"))
                ->map(fn (object $row): string => (string) array_values((array) $row)[0])
                ->sort()
                ->values();

            foreach ($tables as $table) {
                $tableCount++;
                $quotedTable = $this->identifier($table);
                $createRow = DB::selectOne("SHOW CREATE TABLE {$quotedTable}");
                $createSql = (string) (array_values((array) $createRow)[1] ?? '');

                if ($createSql === '') {
                    throw new RuntimeException(trans('app.backups.table_schema_failed', ['table' => $table]));
                }

                $columns = [];

                foreach (DB::select("SHOW COLUMNS FROM {$quotedTable}") as $column) {
                    $metadata = (array) $column;

                    if (str_contains(strtoupper((string) ($metadata['Extra'] ?? '')), 'GENERATED')) {
                        continue;
                    }

                    $field = $metadata['Field'] ?? null;

                    if (! is_string($field) || $field === '') {
                        throw new RuntimeException(trans('app.backups.table_schema_failed', ['table' => $table]));
                    }

                    $columns[] = $field;
                }
                $countRow = DB::selectOne("SELECT COUNT(*) AS aggregate FROM {$quotedTable}");
                $totalRows = (int) ($countRow->aggregate ?? 0);

                $this->writeLine($stream, 'DROP TABLE IF EXISTS '.$quotedTable.';');
                $this->writeLine($stream, $createSql.';');

                for ($offset = 0; $offset < $totalRows; $offset += self::CHUNK_SIZE) {
                    $rows = DB::select(sprintf(
                        'SELECT * FROM %s LIMIT %d OFFSET %d',
                        $quotedTable,
                        self::CHUNK_SIZE,
                        $offset,
                    ));

                    if ($rows === []) {
                        break;
                    }

                    $this->writeInsert(
                        $stream,
                        $quotedTable,
                        $columns,
                        array_values($rows),
                        $connection->getPdo(),
                    );
                    $rowCount += count($rows);
                }

                $this->writeLine($stream);
            }

            $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS=1;');
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }

            throw $exception;
        } finally {
            gzclose($stream);
        }

        if (! File::isFile($outputPath) || File::size($outputPath) === 0) {
            throw new RuntimeException(trans('app.backups.database_empty'));
        }

        return [
            'table_count' => $tableCount,
            'row_count' => $rowCount,
            'bytes' => File::size($outputPath),
            'sha256' => $this->checksum($outputPath),
        ];
    }

    /**
     * @param  resource  $stream
     * @param  list<string>  $columns
     * @param  list<object>  $rows
     */
    private function writeInsert($stream, string $table, array $columns, array $rows, PDO $pdo): void
    {
        $columnSql = implode(', ', array_map($this->identifier(...), $columns));
        $values = array_map(
            fn (object $row): string => '('.implode(', ', array_map(
                fn (string $column): string => $this->value($row->{$column} ?? null, $pdo),
                $columns,
            )).')',
            $rows,
        );

        $this->writeLine(
            $stream,
            "INSERT INTO {$table} ({$columnSql}) VALUES\n".implode(",\n", $values).';',
        );
    }

    private function identifier(string $value): string
    {
        return '`'.str_replace('`', '``', $value).'`';
    }

    private function value(mixed $value, PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return sprintf('%.17g', $value);
        }

        $quoted = $pdo->quote((string) $value);

        if ($quoted === false) {
            throw new RuntimeException(trans('app.backups.database_quote_failed'));
        }

        return $quoted;
    }

    /** @param resource $stream */
    private function writeLine($stream, string $line = ''): void
    {
        if (gzwrite($stream, $line."\n") === false) {
            throw new RuntimeException(trans('app.backups.database_write_failed'));
        }
    }

    private function checksum(string $path): string
    {
        $checksum = hash_file('sha256', $path);

        if (! is_string($checksum)) {
            throw new RuntimeException(trans('app.backups.checksum_failed'));
        }

        return $checksum;
    }
}
