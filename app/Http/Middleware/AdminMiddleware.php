<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    protected array $access = [
        'dashboard' => ['super_admin', 'admin', 'staff'],
        'cottages' => ['super_admin', 'admin'],
        'testimonials' => ['super_admin', 'admin'],
        'services' => ['super_admin', 'admin'],
        'faqs' => ['super_admin', 'admin'],
        'gallery' => ['super_admin', 'admin'],
        'inquiries' => ['super_admin', 'admin', 'staff'],
        'guests' => ['super_admin', 'admin'],
        'users' => ['super_admin', 'admin'],
        'site-settings' => ['super_admin', 'admin'],
    ];

    protected array $writeAccess = [
        'cottages' => ['super_admin', 'admin'],
        'testimonials' => ['super_admin', 'admin'],
        'services' => ['super_admin', 'admin'],
        'faqs' => ['super_admin', 'admin'],
        'gallery' => ['super_admin', 'admin'],
        'inquiries' => ['super_admin', 'admin'],
        'guests' => ['super_admin', 'admin'],
        'users' => ['super_admin', 'admin'],
        'site-settings' => ['super_admin'],
    ];

    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['super_admin', 'admin', 'staff'])) {
            abort(403, 'Unauthorized access.');
        }

        $route = $request->route();
        $name = $route ? $route->getName() : '';
        $parts = explode('.', $name);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';

        if (!isset($this->access[$resource])) {
            return $next($request);
        }

        if (!in_array($user->role, $this->access[$resource])) {
            abort(403, 'You do not have permission to access this resource.');
        }

        $writeActions = ['create', 'store', 'edit', 'update', 'destroy', 'confirm', 'cancel', 'activate-all', 'mark-paid', 'refund'];
        if (in_array($action, $writeActions) && isset($this->writeAccess[$resource])) {
            if (!in_array($user->role, $this->writeAccess[$resource])) {
                abort(403, 'You do not have permission to perform this action.');
            }
        }

        if ($user->role === 'staff' && !in_array($resource, ['dashboard', 'inquiries'])) {
            abort(403, 'Staff can only access the dashboard and inquiries.');
        }

        return $next($request);
    }
}
