<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cottage;
use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
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
        $upcomingCheckIns = Inquiry::where('status', 'confirmed')
            ->where('check_in', '>=', now()->startOfDay())
            ->orderBy('check_in')
            ->take(8)
            ->get();

        $recentInquiries = Inquiry::latest()->take(5)->get();

        $popularCottages = Cottage::withCount('inquiries')
            ->orderByDesc('inquiries_count')
            ->take(5)
            ->get();

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

        return view('admin.dashboard', compact(
            'totalCottages', 'availableCottages',
            'pendingInquiries', 'confirmedThisMonth', 'paidThisMonth',
            'revenueThisMonth', 'upcomingCheckIns',
            'recentInquiries', 'popularCottages',
            'bookingTypeData', 'revenueData'
        ));
    }
}
