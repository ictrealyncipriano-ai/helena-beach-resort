<?php

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Filament\Resources\InquiryResource;
use App\Mail\BookingCancelled;
use App\Mail\BookingConfirmed;
use App\Models\CottageDateBlock;
use App\Models\SiteSetting;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    private ?string $previousStatus = null;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->previousStatus = $this->record->status;
        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->previousStatus === $this->record->status) {
            return;
        }

        if ($this->record->status === 'confirmed') {
            $this->handleConfirmed();
        } elseif ($this->record->status === 'cancelled') {
            $this->handleCancelled();
        }
    }

    private function handleConfirmed(): void
    {
        $record = $this->record;

        if ($record->check_in && $record->check_out && $record->cottage_id) {
            $period = \Carbon\CarbonPeriod::create($record->check_in, $record->check_out)->toArray();
            foreach ($period as $date) {
                CottageDateBlock::firstOrCreate([
                    'cottage_id' => $record->cottage_id,
                    'date' => $date->format('Y-m-d'),
                ], ['reason' => "Booked: {$record->reference_code}"]);
            }
        }

        $guest = $record->guest;
        if ($guest) {
            $guest->increment('total_stays');
            $guest->update(['last_stay_at' => $record->check_out ?? now()]);
        }

        try {
            Mail::to($record->email)->send(new BookingConfirmed($record));
            Notification::make()
                ->title('Booking confirmed & email sent to guest')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::warning('Failed to send booking confirmation email', [
                'inquiry_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            Notification::make()
                ->title('Booking confirmed but email failed to send')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function handleCancelled(): void
    {
        $record = $this->record;

        if ($record->check_in && $record->check_out && $record->cottage_id) {
            CottageDateBlock::where('cottage_id', $record->cottage_id)
                ->whereBetween('date', [$record->check_in, $record->check_out])
                ->where('reason', "Booked: {$record->reference_code}")
                ->delete();
        }

        if ($record->guest) {
            $record->guest->decrement('total_stays');
        }

        try {
            Mail::to($record->email)->send(new BookingCancelled($record));
            $ownerEmail = SiteSetting::getValue('contact_email');
            if ($ownerEmail) {
                Mail::to($ownerEmail)->send(new BookingCancelled($record));
            }
            Notification::make()
                ->title('Cancellation email sent to guest & owner')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::warning('Failed to send cancellation email', [
                'inquiry_id' => $record->id,
                'error' => $e->getMessage(),
            ]);
            Notification::make()
                ->title('Booking cancelled but email failed to send')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
