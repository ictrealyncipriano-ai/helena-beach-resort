<?php

namespace App\Filament\Widgets;

use App\Models\Cottage;
use App\Models\Inquiry;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $confirmedThisMonth = Inquiry::where('status', 'confirmed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        $revenueThisMonth = $confirmedThisMonth->sum('total_amount');

        return [
            Stat::make('Total Cottages', Cottage::count())
                ->icon('heroicon-o-home')
                ->description('Available: ' . Cottage::where('is_available', true)->count())
                ->color('primary'),
            Stat::make('Pending Inquiries', Inquiry::where('status', 'pending')->count())
                ->icon('heroicon-o-chat-bubble-left')
                ->color('warning')
                ->description('Awaiting confirmation'),
            Stat::make('Confirmed This Month', $confirmedThisMonth->count())
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->description('₱' . number_format($revenueThisMonth, 2) . ' revenue'),
            Stat::make('Upcoming Check-Ins', Inquiry::where('status', 'confirmed')
                ->where('check_in', '>=', now())
                ->count())
                ->icon('heroicon-o-calendar')
                ->color('info'),
        ];
    }
}
