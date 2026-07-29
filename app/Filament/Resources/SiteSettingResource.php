<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Models\SiteSetting;
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
 * Admin key-value settings manager (contact info, hero text, social links, etc.).
 * Only super_admin can edit or create settings.
 */
class SiteSettingResource extends Resource
{
    protected static ?string $model = SiteSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('value')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Text',
                        'textarea' => 'Textarea',
                        'image' => 'Image',
                    ])
                    ->default('text'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('value')
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'image' => 'info',
                        'textarea' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([])
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
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
        ];
    }
}
