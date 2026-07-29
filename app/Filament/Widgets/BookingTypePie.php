<?php

namespace App\Filament\Widgets;

use App\Models\Inquiry;
use Filament\Widgets\ChartWidget;

class BookingTypePie extends ChartWidget
{
    protected static ?string $heading = 'Booking Type Distribution';
    protected static ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $dayTour = Inquiry::where('booking_type', 'day_tour')->count();
        $overnight = Inquiry::where('booking_type', 'overnight')->count();
        $unspecified = Inquiry::whereNull('booking_type')->count();

        return [
            'datasets' => [
                [
                    'data' => [$dayTour, $overnight, $unspecified],
                    'backgroundColor' => ['#0d9488', '#f59e0b', '#e5e7eb'],
                    'borderColor' => ['#ffffff', '#ffffff', '#ffffff'],
                    'borderWidth' => 3,
                ],
            ],
            'labels' => ['Day Tour', 'Overnight', 'Unspecified'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 16,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                    ],
                ],
            ],
            'cutout' => '60%',
            'maintainAspectRatio' => false,
        ];
    }
}
