<?php

namespace App\Filament\Resources\Guests\Pages;

use App\Filament\Resources\Guests\GuestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewGuest extends ViewRecord
{
    protected static string $resource = GuestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Guest Information')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('name'),
                        \Filament\Infolists\Components\TextEntry::make('email'),
                        \Filament\Infolists\Components\TextEntry::make('phone'),
                        \Filament\Infolists\Components\TextEntry::make('total_stays')->label('Total Stays'),
                        \Filament\Infolists\Components\TextEntry::make('last_stay_at')->label('Last Stay')->date(),
                        \Filament\Infolists\Components\TextEntry::make('notes')->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            \App\Filament\Resources\Guests\RelationManagers\InquiriesRelationManager::class,
        ];
    }
}
