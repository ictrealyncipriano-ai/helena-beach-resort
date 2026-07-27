<?php

namespace App\Services;

use App\Models\Cottage;
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
        $totalAmount = null;
        if (!empty($data['booking_type']) && !empty($data['cottage_id'])) {
            $cottage = Cottage::find($data['cottage_id']);
            if ($cottage) {
                if ($data['booking_type'] === 'day_tour') {
                    $totalAmount = $cottage->rate_daytour;
                } elseif ($data['booking_type'] === 'overnight' && !empty($data['check_in']) && !empty($data['check_out'])) {
                    $nights = \Carbon\Carbon::parse($data['check_in'])->diffInDays(\Carbon\Carbon::parse($data['check_out']));
                    $totalAmount = $cottage->rate_overnight * max($nights, 1);
                }
            }
        }

        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'pax' => $data['pax'] ?? null,
            'cottage_id' => $data['cottage_id'] ?? null,
            'message' => $data['message'] ?? null,
            'booking_type' => $data['booking_type'] ?? null,
            'total_amount' => $totalAmount,
            'source' => $data['source'] ?? 'website',
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
