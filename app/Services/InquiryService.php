<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Inquiry;
use App\Mail\InquiryNotification;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InquiryService
{
    public function store(array $data): Inquiry
    {
        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'pax' => $data['pax'] ?? null,
            'cottage_id' => $data['cottage_id'] ?? null,
            'message' => $data['message'] ?? null,
            'source' => 'website',
        ]);

        $guest = Guest::updateOrCreate(
            ['email' => $data['email']],
            ['name' => $data['name'], 'phone' => $data['phone'] ?? null]
        );
        $inquiry->guest()->associate($guest)->save();

        $ownerEmail = SiteSetting::getValue('contact_email');
        if ($ownerEmail) {
            try {
                Mail::to($ownerEmail)->send(new InquiryNotification($inquiry));
            } catch (\Exception $e) {
                Log::warning('Failed to send inquiry notification', [
                    'inquiry_id' => $inquiry->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $inquiry;
    }
}
