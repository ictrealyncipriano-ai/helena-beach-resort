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
        return [
            Stat::make('Total Cottages', Cottage::count())
                ->icon('heroicon-o-home'),
            Stat::make('Pending Inquiries', Inquiry::where('status', 'pending')->count())
                ->icon('heroicon-o-chat-bubble-left')
                ->color('warning'),
            Stat::make('Confirmed This Month', Inquiry::where('status', 'confirmed')
                ->whereMonth('created_at', now()->month)
                ->count())
                ->icon('heroicon-o-check')
                ->color('success'),
        ];
    }
}
