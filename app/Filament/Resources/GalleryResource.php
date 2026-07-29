<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryResource\Pages;
use App\Models\Gallery;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Admin CRUD for photo gallery entries with category tagging.
 */
class GalleryResource extends Resource
{
    protected static ?string $model = Gallery::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    public static function canCreate(): bool
    {
        return in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    public static function canEdit($record): bool
    {
        return in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    public static function canDelete($record): bool
    {
        return in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('title'),
                Forms\Components\FileUpload::make('photo_path')
                    ->image()
                    ->directory('gallery')
                    ->disk('cloudflare')
                    ->required(),
                Forms\Components\Select::make('category')
                    ->options([
                        'resort' => 'Resort',
                        'beach' => 'Beach',
                        'food' => 'Food',
                        'events' => 'Events',
                    ]),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->square()
                    ->size(60),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'beach' => 'info',
                        'food' => 'warning',
                        'events' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Sort')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'resort' => 'Resort',
                        'beach' => 'Beach',
                        'food' => 'Food',
                        'events' => 'Events',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                EditAction::make()->icon('heroicon-o-pencil'),
                DeleteAction::make()->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleries::route('/'),
            'create' => Pages\CreateGallery::route('/create'),
            'edit' => Pages\EditGallery::route('/{record}/edit'),
        ];
    }
}
