<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CottageResource\Pages;
use App\Models\Cottage;
use App\Models\User;
use Filament\Forms;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CottageResource extends Resource
{
    protected static ?string $model = Cottage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

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
                Section::make('Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('capacity')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('rate_daytour')
                            ->numeric()
                            ->prefix('₱'),
                        Forms\Components\TextInput::make('rate_overnight')
                            ->numeric()
                            ->prefix('₱'),
                        Forms\Components\Toggle::make('is_available')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),

                Section::make('Amenities')
                    ->schema([
                        Forms\Components\Repeater::make('amenities')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                                Forms\Components\Select::make('icon')
                                    ->options([
                                        'snowflake' => 'Aircon',
                                        'device-tv' => 'TV',
                                        'wifi' => 'WiFi',
                                        'cooking-pot' => 'Kitchen',
                                        'toilet' => 'CR',
                                        'car' => 'Parking',
                                        'microphone' => 'Karaoke',
                                        'campfire' => 'Grill',
                                    ]),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Availability')
                    ->schema([
                        Forms\Components\Repeater::make('dateBlocks')
                            ->relationship()
                            ->schema([
                                Forms\Components\DatePicker::make('date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('M d, Y'),
                                Forms\Components\TextInput::make('reason')
                                    ->placeholder('e.g. Maintenance, Private Event'),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->label('Blocked Dates'),
                    ]),

                Section::make('Photos')
                    ->schema([
                        Forms\Components\Repeater::make('photos')
                            ->relationship()
                            ->schema([
                                Forms\Components\FileUpload::make('photo_path')
                                    ->image()
                                    ->directory('cottages'),
                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Primary Photo'),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0),
                            ])
                            ->columns(3),
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
                Tables\Columns\TextColumn::make('capacity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_daytour')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rate_overnight')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_available'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListCottages::route('/'),
            'create' => Pages\CreateCottage::route('/create'),
            'edit' => Pages\EditCottage::route('/{record}/edit'),
        ];
    }
}
