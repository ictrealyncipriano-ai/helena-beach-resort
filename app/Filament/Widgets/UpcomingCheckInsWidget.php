<?php

namespace App\Filament\Widgets;

use App\Models\Inquiry;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class UpcomingCheckInsWidget extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Check-Ins';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Inquiry::query()
                    ->where('status', 'confirmed')
                    ->where('check_in', '>=', now())
                    ->orderBy('check_in')
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->label('Ref #')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Guest')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('cottage.name')
                    ->label('Cottage')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('check_in')
                    ->date('M d, Y')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('check_out')
                    ->date('M d, Y')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('pax')
                    ->label('Pax')
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('booking_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'overnight' ? 'Overnight' : 'Day Tour')
                    ->color(fn (?string $state): string => $state === 'overnight' ? 'warning' : 'info'),
            ]);
    }
}
