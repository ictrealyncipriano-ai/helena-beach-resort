<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Cache key for the aggregate stats block. Keyed by month so the 5-minute
     * TTL naturally resets when the month rolls over.
     */
    public static function cacheKey(): string
    {
        return 'admin.dashboard.stats.'.now()->format('Y-m');
    }

    public function index()
    {
        // Aggregate counts/revenue are expensive (multiple GROUP BY queries)
        // and only change when an inquiry is created/confirmed/cancelled/
        // refunded/deleted, so they are cached for 5 minutes and invalidated
        // by the admin inquiry actions (see Admin\InquiryController).
        $stats = Cache::remember(self::cacheKey(), 300, function () {
            $totalCottages = Cottage::count();
            $availableCottages = Cottage::where('is_available', true)->count();
            $pendingInquiries = Inquiry::where('status', 'pending')->count();
            $confirmedThisMonth = Inquiry::where('status', 'confirmed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $paidThisMonth = Inquiry::whereNotNull('paid_at')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->count();
            $revenueThisMonth = Inquiry::where('status', 'confirmed')
                ->whereNotNull('paid_at')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('paid_amount');

            $bookingTypeData = Inquiry::select('booking_type', DB::raw('count(*) as count'))
                ->groupBy('booking_type')
                ->pluck('count', 'booking_type');

            $driver = DB::getDriverName();
            $monthExpr = $driver === 'pgsql'
                ? "to_char(created_at, 'YYYY-MM')"
                : ($driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')");

            $revenueData = Inquiry::where('status', 'confirmed')
                ->whereNotNull('paid_at')
                ->where('paid_at', '>=', now()->subMonths(6)->startOfMonth())
                ->select(DB::raw("{$monthExpr} as month"), DB::raw('sum(paid_amount) as total'))
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
            ->where('status', 'confirmed')
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
