<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait InsertsRows
{
    /**
     * Insert rows (skipping ones whose primary/unique key already exists, so seeders can be re-run).
     * Every row must define the same set of columns.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function insertRows(string $table, array $rows, bool $timestamps = true): void
    {
        $now = now();

        if ($timestamps) {
            $rows = array_map(fn (array $row) => $row + ['created_at' => $now, 'updated_at' => $now], $rows);
        }

        DB::table($table)->insertOrIgnore($rows);
    }
}
