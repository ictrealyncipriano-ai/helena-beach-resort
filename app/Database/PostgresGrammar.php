<?php

namespace App\Database;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\PostgresGrammar as BasePostgresGrammar;

/**
 * Postgres query grammar that renders boolean comparisons as native SQL
 * literals (`= true` / `= false`) instead of parameter bindings.
 *
 * Laravel's base Connection::prepareBindings casts every PHP boolean to an
 * integer for ALL drivers, so on Postgres `where('is_active', true)` becomes
 * `where "is_active" = ?` bound as the integer `1`, which Postgres rejects
 * with "operator does not exist: boolean = integer". Emitting the SQL keyword
 * sidesteps PDO/PgBouncer binding-type quirks entirely, and the corresponding
 * (now unused) boolean binding is dropped so placeholder counts stay aligned.
 */
class PostgresGrammar extends BasePostgresGrammar
{
    protected function whereBasic(Builder $query, $where)
    {
        if (is_bool($where['value'])) {
            foreach ($query->bindings['where'] as $index => $binding) {
                if ($binding === $where['value']) {
                    unset($query->bindings['where'][$index]);
                    $query->bindings['where'] = array_values($query->bindings['where']);
                    break;
                }
            }

            return $this->wrap($where['column']).' '.$where['operator'].' '.($where['value'] ? 'true' : 'false');
        }

        return parent::whereBasic($query, $where);
    }
}
