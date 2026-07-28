<x-mail::message>
# Booking Cancelled

Hi **{{ $inquiry->name }}**,

Your booking at **Helena Beach Resort** has been cancelled as requested.

**Reference:** {{ $inquiry->reference_code }}

@if($inquiry->cottage)
**Cottage:** {{ $inquiry->cottage->name }}
@endif

If you have any questions or would like to make a new booking, please don't hesitate to contact us. We'd love to welcome you in the future!

<x-mail::button :url="route('book')" color="primary">
Make a New Booking
</x-mail::button>

Best regards,
**Helena Beach Resort**
</x-mail::message>
