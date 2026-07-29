<?php

namespace App\Filament\Widgets;

use App\Models\Cottage;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PopularCottagesWidget extends BaseWidget
{
    protected static ?string $heading = 'Popular Cottages (By Bookings)';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Cottage::query()
                    ->withCount('inquiries')
                    ->orderBy('inquiries_count', 'desc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Cottage')
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('inquiries_count')
                    ->label('Total Bookings')
                    ->badge()
                    ->color('primary')
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Max Pax')
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('rate_daytour')
                    ->label('Day Tour Rate')
                    ->money('PHP')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('rate_overnight')
                    ->label('Overnight Rate')
                    ->money('PHP')
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Available')
                    ->boolean()
                    ->alignment('center'),
            ]);
    }
}
