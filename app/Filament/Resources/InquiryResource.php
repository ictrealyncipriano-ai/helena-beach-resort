<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InquiryResource\Pages;
use App\Models\Inquiry;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Bookings';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Guest Details')
                    ->schema([
                        Forms\Components\TextInput::make('name'),
                        Forms\Components\TextInput::make('email'),
                        Forms\Components\TextInput::make('phone'),
                    ])
                    ->columns(3),
                Section::make('Booking Details')
                    ->schema([
                        Forms\Components\DatePicker::make('check_in'),
                        Forms\Components\DatePicker::make('check_out'),
                        Forms\Components\TextInput::make('pax'),
                        Forms\Components\Select::make('cottage_id')
                            ->relationship('cottage', 'name'),
                        Forms\Components\TextInput::make('status'),
                    ])
                    ->columns(3),
                Section::make('Message')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('cottage.name'),
                Tables\Columns\TextColumn::make('check_in')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('check_out')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pax'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('cottage_id')
                    ->relationship('cottage', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('markConfirmed')
                    ->label('Confirm')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Inquiry $record) => $record->update(['status' => 'confirmed']))
                    ->visible(fn (Inquiry $record) => $record->status === 'pending'),
                Action::make('markCancelled')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(fn (Inquiry $record) => $record->update(['status' => 'cancelled']))
                    ->visible(fn (Inquiry $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Guest Details')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('email'),
                        TextEntry::make('phone'),
                    ])
                    ->columns(3),
                Section::make('Booking Details')
                    ->schema([
                        TextEntry::make('check_in')->date(),
                        TextEntry::make('check_out')->date(),
                        TextEntry::make('pax'),
                        TextEntry::make('cottage.name'),
                        TextEntry::make('status')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                default => 'warning',
                            }),
                    ])
                    ->columns(3),
                Section::make('Message')
                    ->schema([
                        TextEntry::make('message')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'view' => Pages\ViewInquiry::route('/{record}'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
