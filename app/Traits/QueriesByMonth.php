<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait QueriesByMonth
{
    /**
     * Database-agnostic SQL expression that groups a timestamp column by
     * year-month (e.g. "2026-08"). Works on MySQL, SQLite and PostgreSQL.
     */
    protected function monthExpression(string $column): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
