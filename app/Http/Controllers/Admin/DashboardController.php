<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\Inquiry;
use App\Traits\QueriesByMonth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use QueriesByMonth;

    private const CACHE_TTL = 300;
    /**
     * Cache key for the aggregate stats block. Keyed by month so the 5-minute
     * TTL naturally resets when the month rolls over.
     */
    public static function cacheKey(): string
    {
        return 'admin.dashboard.stats.'.now()->format('Y-m');
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    public function index(): View
    {
        // Aggregate counts/revenue are expensive (multiple GROUP BY queries)
        // and only change when an inquiry is created/confirmed/cancelled/
        // refunded/deleted, so they are cached for 5 minutes and invalidated
        // by the admin inquiry actions (see Admin\InquiryController).
        $stats = Cache::remember(self::cacheKey(), self::CACHE_TTL, function () {
            $totalCottages = Cottage::count();
            $availableCottages = Cottage::where('is_available', true)->count();
            $pendingInquiries = Inquiry::where('status', Inquiry::STATUS_PENDING)->count();
            $confirmedThisMonth = Inquiry::where('status', Inquiry::STATUS_CONFIRMED)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $paidThisMonth = Inquiry::where('amount_paid', '>', 0)
                ->whereMonth('deposit_paid_at', now()->month)
                ->whereYear('deposit_paid_at', now()->year)
                ->count();
            $revenueThisMonth = Inquiry::where('status', Inquiry::STATUS_CONFIRMED)
                ->where('amount_paid', '>', 0)
                ->whereMonth('deposit_paid_at', now()->month)
                ->whereYear('deposit_paid_at', now()->year)
                ->sum('amount_paid');

            $bookingTypeData = Inquiry::select('booking_type', DB::raw('count(*) as count'))
                ->groupBy('booking_type')
                ->pluck('count', 'booking_type');

            $revenueData = Inquiry::where('status', Inquiry::STATUS_CONFIRMED)
                ->where('amount_paid', '>', 0)
                ->where('deposit_paid_at', '>=', now()->subMonths(6)->startOfMonth())
                ->select(DB::raw("{$this->monthExpression('created_at')} as month"), DB::raw('sum(amount_paid) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month');

            return compact(
                'totalCottages', 'availableCottages', 'pendingInquiries',
                'confirmedThisMonth', 'paidThisMonth', 'revenueThisMonth',
                'bookingTypeData', 'revenueData'
            );
        });

        // The "live" lists are small, cheap queries and must always reflect
        // the latest data, so they stay out of the cache.
        $upcomingCheckIns = Inquiry::with('cottage')
            ->where('status', Inquiry::STATUS_CONFIRMED)
            ->where('check_in', '>=', now()->startOfDay())
            ->orderBy('check_in')
            ->take(8)
            ->get();

        $recentInquiries = Inquiry::with('cottage')->latest()->take(5)->get();

        $popularCottages = Cottage::withCount('inquiries')
            ->orderByDesc('inquiries_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', $stats + compact(
            'upcomingCheckIns', 'recentInquiries', 'popularCottages'
        ));
    }
}
