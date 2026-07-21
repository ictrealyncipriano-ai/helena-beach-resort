<?php

namespace App\Filament\Resources\CottageResource\Pages;

use App\Filament\Resources\CottageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCottages extends ListRecords
{
    protected static string $resource = CottageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
