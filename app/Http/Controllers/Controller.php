<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

abstract class Controller
{
    use AuthorizesRequests;

    protected const ADMIN_PER_PAGE = 15;
    protected const ACTIVITY_LOG_PER_PAGE = 25;

    /**
     * Turn a cached collection into a paginator without caching the paginator
     * itself (paginator objects embed request state).
     */
    protected function paginate(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = Paginator::resolveCurrentPage();
        $slice = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }
}
