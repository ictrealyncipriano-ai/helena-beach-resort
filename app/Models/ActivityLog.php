<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A single audit-trail entry describing a write action (admin or guest) and,
 * when applicable, the subject row it touched.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'user_name', 'action', 'subject_type', 'subject_id',
        'description', 'properties',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-friendly label for a stored action key, falling back to a
     * readable version of the raw key when no mapping exists.
     */
    public function actionLabel(): string
    {
        return match ($this->action) {
            'inquiry.created' => 'Inquiry created',
            'inquiry.submitted' => 'Inquiry submitted',
            'inquiry.updated' => 'Inquiry updated',
            'inquiry.confirmed' => 'Booking confirmed',
            'inquiry.cancelled' => 'Booking cancelled',
            'inquiry.deleted' => 'Inquiry deleted',
            'inquiry.refunded' => 'Payment refunded',
            'inquiry.marked_paid' => 'Booking marked as paid',
            'inquiry.modified' => 'Booking modified',
            'payment_proof.approved' => 'Payment proof approved',
            'payment_proof.rejected' => 'Payment proof rejected',
            'payment.received' => 'Payment received',
            'guest.cancelled' => 'Guest cancelled booking',
            'guest.modified' => 'Guest modified booking',
            'guest.updated' => 'Guest updated',
            'guest.deleted' => 'Guest deleted',
            'guest.payment_proof' => 'Guest uploaded payment proof',
            'guest.reviewed' => 'Guest submitted a review',
            'cottage.created' => 'Cottage created',
            'cottage.updated' => 'Cottage updated',
            'cottage.deleted' => 'Cottage deleted',
            'testimonial.created' => 'Testimonial created',
            'testimonial.updated' => 'Testimonial updated',
            'testimonial.deleted' => 'Testimonial deleted',
            'service.created' => 'Service created',
            'service.updated' => 'Service updated',
            'service.deleted' => 'Service deleted',
            'faq.created' => 'FAQ created',
            'faq.updated' => 'FAQ updated',
            'faq.deleted' => 'FAQ deleted',
            'faq.activated' => 'All FAQs activated',
            'gallery.created' => 'Gallery image added',
            'gallery.updated' => 'Gallery image updated',
            'gallery.deleted' => 'Gallery image deleted',
            'promo.created' => 'Promo code created',
            'promo.updated' => 'Promo code updated',
            'promo.deleted' => 'Promo code deleted',
            'user.created' => 'User created',
            'user.updated' => 'User updated',
            'user.deleted' => 'User deleted',
            'setting.created' => 'Setting created',
            'setting.updated' => 'Setting updated',
            'setting.deleted' => 'Setting deleted',
            default => ucfirst(str_replace(['.', '_'], ' ', $this->action)),
        };
    }

    /**
     * The actor's display name: the logged-in admin snapshot when present,
     * otherwise a "guest" marker for portal-originated entries.
     */
    public function actorLabel(): string
    {
        return $this->user_name ?: 'Guest';
    }
}
