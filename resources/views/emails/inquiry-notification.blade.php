<x-mail::message>
# New Booking Inquiry

**Name:** {{ $inquiry->name }}
**Email:** {{ $inquiry->email }}
**Phone:** {{ $inquiry->phone ?? 'N/A' }}
**Check-in:** {{ $inquiry->check_in ?? 'N/A' }}
**Check-out:** {{ $inquiry->check_out ?? 'N/A' }}
**Guests:** {{ $inquiry->pax ?? 'N/A' }}
**Cottage:** {{ $inquiry->cottage?->name ?? 'Not specified' }}

**Message:**
{{ $inquiry->message ?? 'No message' }}

<a href="{{ url('/admin/inquiries/' . $inquiry->id) }}">View in Admin Panel</a>
</x-mail::message>
