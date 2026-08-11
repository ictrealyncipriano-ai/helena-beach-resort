<?php

namespace App\Database;

use Illuminate\Database\PostgresConnection as BasePostgresConnection;

/**
 * Postgres connection used on the serverless (Vercel) runtime.
 *
 * The base Connection binds PHP booleans as integers, which is valid for
 * SQLite/MySQL but not for Postgres. Boolean values appearing in INSERT or
 * UPDATE bindings are therefore rewritten to the strings `true` / `false`,
 * which Postgres can cast into boolean parameters when they are inferred from
 * the target column type (comparisons are handled separately by
 * PostgresGrammar, which emits native `true` / `false` literals).
 */
class PostgresConnection extends BasePostgresConnection
{
    public function prepareBindings(array $bindings)
    {
        foreach ($bindings as $key => $value) {
            if ($value === true) {
                $bindings[$key] = 'true';
            } elseif ($value === false) {
                $bindings[$key] = 'false';
            }
        }

        return parent::prepareBindings($bindings);
    }

    protected function getDefaultQueryGrammar()
    {
        return new PostgresGrammar($this);
    }
}