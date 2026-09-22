<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait QueriesByMonth
{
    /**
     * Database-agnostic SQL expression that groups a timestamp column by
     * year-month (e.g. "2026-08"). Works on MySQL, SQLite and PostgreSQL.
     *
     * The column is allow-listed: only the documented grouping expressions
     * are interpolated, everything else fails closed instead of reaching
     * raw SQL.
     */
    protected function monthExpression(string $column): string
    {
        if (! in_array($column, [
            'deposit_paid_at',
            'fully_paid_at',
            'created_at',
            'COALESCE(fully_paid_at, deposit_paid_at)',
        ], true)) {
            throw new \InvalidArgumentException("Unsupported month grouping column: {$column}");
        }

        return match (DB::getDriverName()) {
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            'sqlite' => "strftime('%Y-%m', {$column})",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }
}
