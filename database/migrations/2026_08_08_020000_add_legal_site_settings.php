<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'legal_privacy', 'type' => 'textarea', 'value' => <<<'TXT'
<h2>Privacy Policy</h2><p>At Helena Beach Resort, we respect your privacy. We collect only the personal information you provide when you submit a booking or inquiry: your name, email address, phone number, and the details of your requested stay.</p><h3>How we use your information</h3><p>Your information is used solely to process your booking, communicate reservation updates, and send payment receipts. We do not sell, rent, or share your personal data with third parties except as required to process payments (e.g. our payment provider, PayMongo) or as required by law.</p><h3>Data retention</h3><p>Booking records are retained for as long as needed to fulfil our legal, tax, and accounting obligations. You may request access to or deletion of your personal data by contacting us through our contact details.</p><h3>Contact</h3><p>For any privacy questions, please reach out to us at our contact information on this website.</p>
TXT],
            ['key' => 'legal_terms', 'type' => 'textarea', 'value' => <<<'TXT'
<h2>Terms &amp; Conditions</h2><p>By submitting a booking request, you agree to these Terms &amp; Conditions.</p><h3>Reservations</h3><p>A booking becomes confirmed once our team approves it. A pending request does not guarantee availability; dates are held pending confirmation and may be released if not confirmed or paid in time.</p><h3>Payments</h3><p>Payment is collected through our secure online checkout or other accepted methods. Full or partial payment may be required to guarantee your stay, as indicated on your booking confirmation.</p><h3>Cancellations</h3><p>Cancellations must be requested through the booking portal or by contacting us directly. Refunds, if applicable, will be processed back to the original payment method.</p><h3>Conduct</h3><p>Guests are responsible for the conduct of their party and for any damage caused to the resort property during their stay.</p>
TXT],
            ['key' => 'legal_booking_policy', 'type' => 'textarea', 'value' => <<<'TXT'
<h2>Booking Policy</h2><p>Here is what to expect when you book with us.</p><h3>Day Tours</h3><p>Day tour access is typically from 8:00 AM to 6:00 PM. Rates are per cottage per day and cover the full day tour period.</p><h3>Overnight Stays</h3><p>Check-in is available after 2:00 PM and check-out is before 12:00 noon on your departure date. Overnight rates are per cottage per night.</p><h3>Deposits</h3><p>A deposit may be required to guarantee your reservation. The remaining balance is payable during your stay or online as arranged.</p><h3>Unavailable Dates</h3><p>Dates already reserved or under maintenance will be blocked. We recommend booking early, especially during weekends and holidays.</p>Please reach out with any questions before booking.
TXT],
        ];

        foreach ($settings as $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'type' => $setting['type']]
            );
        }

        Cache::forget('settings.all');
    }

    public function down(): void
    {
        SiteSetting::whereIn('key', ['legal_privacy', 'legal_terms', 'legal_booking_policy'])->delete();

        Cache::forget('settings.all');
    }
};
