<?php

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqs extends ListRecords
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('activateAll')
                ->label('Activate All FAQs')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(fn (): string => route('admin.faqs.activate-all')),
        ];
    }
}
