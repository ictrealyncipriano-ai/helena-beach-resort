<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGalleries extends ListRecords
{
    protected static string $resource = GalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('migrateCloudflare')
                ->label('Migrate to Cloudflare')
                ->icon('heroicon-o-cloud-arrow-up')
                ->visible(fn () => in_array(auth()->user()?->role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]))
                ->url(route('admin.migrate-cloudflare')),
        ];
    }
}
