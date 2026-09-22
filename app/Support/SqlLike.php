<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;

/**
 * LIKE predicates over user-controlled search terms.
 *
 * Search values are bound parameters (no SQL injection), but unescaped
 * `%`, `_` (and `\`) still act as wildcards — e.g. searching `A_B` would
 * also match `AXB`. The explicit ESCAPE clause is load-bearing:
 * backslash escaping is a MySQL/PostgreSQL LIKE default, but SQLite only
 * honors it when ESCAPE is stated — so the clause keeps all three
 * drivers (and the test suite) consistent.
 *
 * Column names must always be hardcoded by callers, never derived from
 * input: they are interpolated into raw SQL by design.
 */
final class SqlLike
{
    public static function escape(string $term): string
    {
        return addcslashes($term, '\\%_');
    }

    public static function whereLike(Builder|EloquentBuilder $query, string $column, string $term, string $boolean = 'and'): void
    {
        $query->whereRaw("{$column} LIKE ? ESCAPE '\\'", ['%'.static::escape($term).'%'], $boolean);
    }
}
