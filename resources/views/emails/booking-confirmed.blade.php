@extends('emails.layouts.modern')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
<div style="display: inline-block; width: 60px; height: 60px; background: #ecfdf5; border-radius: 50%; line-height: 60px; font-size: 28px; color: #059669;">✓</div>
<h2 style="margin: 12px 0 4px; font-size: 22px; font-weight: 700; color: #059669; letter-spacing: -0.02em;">Booking Confirmed!</h2>
<p style="margin: 0; font-size: 14px; color: #64748b;">Hi <strong style="color: #1e293b;">{{ $inquiry->name }}</strong>, your stay is confirmed.</p>
</div>

<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Reference</p>
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">{{ $inquiry->reference_code }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Booking Type</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">
@if($inquiry->booking_type === 'day_tour') Day Tour
@elseif($inquiry->booking_type === 'overnight') Overnight
@else Inquiry @endif
</p>
</td>
</tr>
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Cottage</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->cottage?->name ?? 'Not specified' }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Guests</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->pax ?? 'N/A' }}</p>
</td>
</tr>
@if($inquiry->check_in)
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Check-in</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_in->format('M d, Y') }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Check-out</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_out?->format('M d, Y') ?? 'Same day' }}</p>
</td>
</tr>
@endif
@if($inquiry->total_amount)
<tr>
<td colspan="2" style="padding: 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Total Amount</p>
<p style="margin: 0; font-size: 18px; font-weight: 700; color: #0d9488;">₱{{ number_format($inquiry->total_amount) }}</p>
</td>
</tr>
@endif
</table>
</div>

<div style="text-align: center; margin-bottom: 24px;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
<tr>
<td style="background: #0d9488; border-radius: 8px; text-align: center;">
<a href="{{ route('booking.portal.lookup') }}" class="btn" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">View My Booking</a>
</td>
</tr>
</table>
</div>

@if(! $inquiry->isPaid() && $inquiry->total_amount)
<div style="background: #fffbeb; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid #fde68a;">
<p style="margin: 0 0 12px; font-size: 13px; color: #92400e; line-height: 1.6;">
To secure your booking, please complete payment of <strong>₱{{ number_format($inquiry->total_amount) }}</strong> via QR Ph.
Use the "View My Booking" button above to open your booking and pay from there.
</p>
</div>
@endif

<div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
<p style="margin: 0 0 8px; font-size: 14px; color: #64748b; line-height: 1.6;">
If you have any questions, feel free to reply to this email or contact us directly. We look forward to hosting you!
</p>
<p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 500;">Warm regards,<br><strong style="color: #0d9488;">Helena Beach Resort Team</strong></p>
</div>
@endsection
