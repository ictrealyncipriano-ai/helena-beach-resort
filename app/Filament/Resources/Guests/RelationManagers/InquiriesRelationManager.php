<?php

namespace App\Filament\Resources\Guests\RelationManagers;

use App\Filament\Resources\InquiryResource;
use App\Models\Inquiry;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InquiriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inquiries';

    protected static ?string $recordTitleAttribute = 'reference_code';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_code')
            ->columns([
                Tables\Columns\TextColumn::make('reference_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cottage.name')
                    ->label('Cottage'),
                Tables\Columns\TextColumn::make('check_in')
                    ->date(),
                Tables\Columns\TextColumn::make('check_out')
                    ->date(),
                Tables\Columns\TextColumn::make('pax'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Inquiry $record): string => InquiryResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([]);
    }
}
