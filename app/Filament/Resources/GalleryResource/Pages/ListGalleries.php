<?php

namespace App\Filament\Resources\GalleryResource\Pages;

use App\Filament\Resources\GalleryResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

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
                ->requiresConfirmation()
                ->modalHeading('Migrate images to Cloudflare R2?')
                ->modalDescription('Copy all existing gallery and cottage images from the current storage (Supabase) to Cloudflare R2. This may take a moment.')
                ->modalSubmitActionLabel('Migrate')
                ->action(function () {
                    $exitCode = Artisan::call('cloudflare:migrate', ['from' => 'r2']);
                    $output = Artisan::output();

                    if ($exitCode === 0) {
                        Notification::make()
                            ->title('Migration completed')
                            ->body($output)
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Migration failed')
                            ->body($output)
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
