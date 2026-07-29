<?php

namespace App\Filament\Widgets;

use App\Models\Inquiry;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class BookingRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Revenue (Last 6 Months)';
    protected ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $data = Trend::model(Inquiry::class)
            ->between(
                start: now()->subMonths(6)->startOfMonth(),
                end: now()->endOfMonth(),
            )
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (₱)',
                    'data' => $data->map(fn (TrendValue $value) => round(floatval($value->aggregate ?? 0), 2)),
                    'borderColor' => '#0d9488',
                    'backgroundColor' => 'rgba(13, 148, 136, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                ],
            ],
            'labels' => $data->map(fn (TrendValue $value) => \Carbon\Carbon::parse($value->date)->format('M Y')),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => ['color' => 'rgba(0,0,0,0.05)'],
                    'ticks' => ['callback' => 'function(value) { return "₱" + value.toLocaleString(); }'],
                ],
                'x' => [
                    'grid' => ['display' => false],
                ],
            ],
        ];
    }
}
