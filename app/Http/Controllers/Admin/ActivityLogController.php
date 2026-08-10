<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Read-only audit trail viewer. Lists recent activity entries with filters
 * for actor, action, and date range, plus a detail view for a single entry.
 */
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($user = trim((string) $request->get('user'))) {
            $query->where('user_name', 'like', "%{$user}%");
        }

        if ($action = $request->get('action')) {
            $query->where('action', $action);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->get('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->get('to'));
        }

        $logs = $query->latest('id')->paginate(25)->withQueryString();

        $actions = ActivityLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.activity-logs.index', compact('logs', 'actions'));
    }

    public function show(ActivityLog $activityLog)
    {
        return view('admin.activity-logs.show', ['log' => $activityLog]);
    }
}
