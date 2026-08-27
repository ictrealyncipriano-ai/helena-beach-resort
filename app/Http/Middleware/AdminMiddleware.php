<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    protected array $access = [
        'dashboard' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF],
        'cottages' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'posts' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'testimonials' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'services' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'faqs' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'gallery' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'inquiries' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF],
        'guests' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'promo-codes' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'exports' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'activity-logs' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'availability' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF],
        'users' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'site-settings' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
    ];

    protected array $writeAccess = [
        'cottages' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'posts' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'testimonials' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'services' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'faqs' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'gallery' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'inquiries' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'guests' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'promo-codes' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'users' => [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN],
        'site-settings' => [User::ROLE_SUPER_ADMIN],
    ];

    // Routes inside the admin group that any authenticated staff may reach and
    // that are not gated by a resource in $access (the /admin -> dashboard
    // redirect and logout). Everything else must be explicitly mapped.
    protected array $openResources = [
        'home',
        'logout',
    ];

    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $user = Auth::user();
        if (! $user || ! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF])) {
            abort(403, 'Unauthorized access.');
        }

        $route = $request->route();
        $name = $route ? $route->getName() : '';
        $parts = explode('.', $name);
        $resource = $parts[1] ?? '';
        $action = $parts[2] ?? '';

        if (in_array($resource, $this->openResources, true)) {
            return $next($request);
        }

        // Fail closed: an admin route that is not explicitly granted is denied
        // rather than silently opened to every staff account.
        if (! isset($this->access[$resource])) {
            abort(403, 'You do not have permission to access this resource.');
        }

        if (! in_array($user->role, $this->access[$resource])) {
            abort(403, 'You do not have permission to access this resource.');
        }

        // 'payment-proof' covers inquiries.payment-proof.approve|reject — the
        // route segment after "inquiries" is "payment-proof", so without this
        // entry staff would be able to approve payment proofs even though the
        // rest of their inquiry access is read-only.
        $writeActions = ['create', 'store', 'edit', 'update', 'destroy', 'confirm', 'cancel', 'activate-all', 'mark-paid', 'refund', 'payment-proof'];
        if (in_array($action, $writeActions) && isset($this->writeAccess[$resource])) {
            if (! in_array($user->role, $this->writeAccess[$resource])) {
                abort(403, 'You do not have permission to perform this action.');
            }
        }

        if ($user->role === User::ROLE_STAFF && ! in_array($resource, ['dashboard', 'inquiries'])) {
            abort(403, 'Staff can only access the dashboard and inquiries.');
        }

        return $next($request);
    }
}
