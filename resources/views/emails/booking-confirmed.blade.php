<x-mail::message>
# Booking Confirmed!

Hi **{{ $inquiry->name }}**,

Great news! Your booking at **Helena Beach Resort** has been confirmed.

<x-mail::panel>
**Reference:** {{ $inquiry->reference_code }}

**Cottage:** {{ $inquiry->cottage?->name ?? 'Not specified' }}

**Booking Type:** {{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : ($inquiry->booking_type === 'overnight' ? 'Overnight' : 'Inquiry') }}

@if($inquiry->check_in)
**Check-in:** {{ $inquiry->check_in->format('M d, Y') }}
@endif
@if($inquiry->check_out)
**Check-out:** {{ $inquiry->check_out->format('M d, Y') }}
@endif

**Guests:** {{ $inquiry->pax ?? 'N/A' }}

@if($inquiry->total_amount)
**Total:** ₱{{ number_format($inquiry->total_amount) }}
@endif
</x-mail::panel>

You can view your booking details at any time using the button below.

<x-mail::button :url="route('booking.portal.show', $inquiry)" color="success">
View My Booking
</x-mail::button>

If you have any questions, feel free to reply to this email or contact us directly.

Thanks for choosing Helena Beach Resort!

<x-mail::subcopy>
To view your booking, visit {{ route('booking.portal.show', $inquiry) }}
</x-mail::subcopy>
</x-mail::message>
