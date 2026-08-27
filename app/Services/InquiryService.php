<?php

namespace App\Services;

use App\Exceptions\BookingConflictException;
use App\Mail\InquiryAcknowledgment;
use App\Mail\InquiryNotification;
use App\Models\Cottage;
use App\Models\Guest;
use App\Models\Inquiry;
use App\Models\PromoCode;
use App\Models\SiteSetting;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Handles the business logic for storing new inquiries/bookings.
 * Calculates total amount, creates/updates guest record, sends notification to owner.
 */
class InquiryService
{
    private const MAX_REFERENCE_RETRIES = 3;

    public function __construct(
        private ActivityLogger $logger,
        private PricingService $pricing,
    ) {}

    /**
     * Store a new inquiry with automatic total calculation and owner notification.
     *
     * The inquiry, its date blocks and the guest link are created inside a
     * single transaction. On a (cottage_id, date) block conflict we convert
     * the failure into a ValidationException ("These dates are no longer
     * available") instead of a 500, and the rollback guarantees no orphaned
     * inquiry row. Reference codes are regenerated on a unique-violation
     * retry (up to 3 attempts) so concurrent creates stay race-free.
     */
    public function store(array $data): Inquiry
    {
        // Calculate total amount based on booking type and cottage rates
        $subtotal = $this->calculateTotal($data);
        $data['source'] = $data['source'] ?? Inquiry::SOURCE_WEBSITE;

        $promo = null;
        $promoGiven = ! empty($data['promo_code']);
        if ($promoGiven) {
            $promo = PromoCode::findUsable($data['promo_code'], $subtotal);
            if (! $promo) {
                throw ValidationException::withMessages([
                    'promo_code' => 'The promo code is invalid, expired, or inapplicable.',
                ]);
            }
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_REFERENCE_RETRIES; $attempt++) {
            try {
                $inquiry = DB::transaction(function () use ($data, $subtotal, $promo) {
                    $pricing = $this->pricing->applyDiscount($subtotal, $promo);
                    $totalAmount = $pricing['total'];
                    $discount = $pricing['discount'];

                    if ($promo) {
                        $promo->consume();
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
                        'total_amount' => $totalAmount === null
                            ? null
                            : formatPrice($totalAmount, 2, false),
                        'discount_amount' => $discount,
                        'promo_code_id' => $promo?->id,
                        'source' => $data['source'],
                        'reference_code' => Inquiry::generateReferenceCode(),
                    ]);

                    // Atomically hold the cottage dates. Throws a
                    // BookingConflictException (converted below) when any
                    // date in the range is already taken.
                    $inquiry->reserveBlocks();

                    // Find the guest by email including soft-deleted rows
                    // (SoftDeletes hides trashed rows from a plain lookup, so
                    // updateOrCreate would try an INSERT that collides with the
                    // guests.email unique index and 500). Reuse + restore the
                    // trashed profile instead of failing. Emails are matched
                    // case-insensitively and normalized, so an email typed with
                    // different casing never creates a duplicate profile.
                    // Never overwrite name/phone on an existing profile — the
                    // request is unauthenticated, so its fields are not trusted.
                    $guest = Guest::findByEmailOrCreate(
                        $data['email'],
                        ['name' => $data['name'], 'phone' => $data['phone'] ?? null]
                    );

                    if ($guest->trashed()) {
                        $guest->restore();
                        // A trashed profile carries another person's history;
                        // reset the transient stay/notes fields so the 'new'
                        // guest does not inherit it.
                        $guest->update(['total_stays' => 0, 'last_stay_at' => null, 'notes' => null]);
                    }

                    $inquiry->guest()->associate($guest)->save();

                    return $inquiry;
                });
            } catch (BookingConflictException $e) {
                // The transaction rolled back — no orphaned inquiry row and
                // no email side effects (emails are only sent after commit).
                throw ValidationException::withMessages([
                    'check_in' => 'These dates are no longer available.',
                ]);
            } catch (UniqueConstraintViolationException $e) {
                // Almost certainly a reference-code collision on this attempt;
                // the transaction already rolled back, so retry with a fresh code.
                $lastException = $e;

                continue;
            }

            // Emails go out only after the transaction committed.
            $this->sendNotifications($inquiry);

            $this->logger->record('inquiry.submitted', $inquiry, "New {$inquiry->source} inquiry {$inquiry->reference_code} submitted.", [
                'booking_type' => $inquiry->booking_type,
                'total_amount' => $inquiry->total_amount,
            ]);

            return $inquiry;
        }

        throw $lastException;
    }

    /**
     * Send the owner notification and guest acknowledgment. Deliberately runs
     * outside the DB transaction so a failed email can never be the cause of
     * a rollback, and a rollback can never cause a half-sent email for a
     * booking that does not exist.
     */
    private function sendNotifications(Inquiry $inquiry): void
    {
        // Notify resort owner about new inquiry
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

        // Send the guest an acknowledgment with their reference code so they
        // don't lose track of the request before it is confirmed.
        try {
            Mail::to($inquiry->email)->send(new InquiryAcknowledgment($inquiry));
        } catch (\Exception $e) {
            Log::warning('Failed to send inquiry acknowledgment to guest', [
                'inquiry_id' => $inquiry->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Calculate the total amount for a booking based on booking type and
     * cottage rates. Returns null when the amount cannot be determined
     * (e.g. missing cottage or incomplete overnight dates).
     */
    public function calculateTotal(array $data): ?string
    {
        if (empty($data['booking_type']) || empty($data['cottage_id'])) {
            return null;
        }

        $cottage = Cottage::find($data['cottage_id']);
        if (! $cottage) {
            return null;
        }

        $checkIn = ! empty($data['check_in']) ? Carbon::parse($data['check_in']) : null;
        $checkOut = ! empty($data['check_out']) ? Carbon::parse($data['check_out']) : null;

        return $this->pricing->calculateTotal($cottage, $checkIn, $checkOut, $data['booking_type']);
    }
}
