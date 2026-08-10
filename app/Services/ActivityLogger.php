<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Thin helper for writing activity-log entries. Snapshots the authenticated
 * admin's id + name at write time (so entries survive later user deletion)
 * and exposes a single call site used across admin and portal actions.
 */
class ActivityLogger
{
    /**
     * Record an audit-trail entry.
     *
     * @param  Model|array{type: string, id: int}|null  $subject
     * @param  array<string, mixed>|null  $properties
     */
    public function record(
        string $action,
        Model|array|null $subject = null,
        ?string $description = null,
        ?array $properties = null
    ): void {
        [$type, $id] = $this->normalizeSubject($subject);

        ActivityLog::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'user_name' => Auth::check() ? Auth::user()->name : null,
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => $id,
            'description' => $description,
            'properties' => $properties,
        ]);
    }

    /**
     * @param  Model|array{type: string, id: int}|null  $subject
     * @return array{0: string|null, 1: int|null}
     */
    private function normalizeSubject(Model|array|null $subject): array
    {
        if ($subject === null) {
            return [null, null];
        }

        if (is_array($subject)) {
            return [$subject['type'] ?? null, $subject['id'] ?? null];
        }

        return [$subject->getMorphClass(), $subject->getKey()];
    }
}
