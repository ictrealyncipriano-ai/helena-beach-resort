@extends('emails.layouts.modern')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
<div style="display: inline-block; width: 60px; height: 60px; background: #ecfdf5; border-radius: 50%; line-height: 60px; font-size: 28px; color: #059669;">✓</div>
<h2 style="margin: 12px 0 4px; font-size: 22px; font-weight: 700; color: #059669; letter-spacing: -0.02em;">Payment Received!</h2>
<p style="margin: 0; font-size: 14px; color: #64748b;">Hi <strong style="color: #1e293b;">{{ $inquiry->name }}</strong>, we've received your payment for booking <strong style="color: #1e293b;">{{ $inquiry->reference_code }}</strong>.</p>
</div>

<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Reference</p>
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">{{ $inquiry->reference_code }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Payment Method</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ ucfirst($inquiry->payment_method ?? 'Online') }}</p>
</td>
</tr>
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Amount Paid</p>
<p style="margin: 0; font-size: 18px; font-weight: 700; color: #0d9488;">{{ formatPrice($inquiry->amount_paid ?? $inquiry->total_amount) }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Paid On</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ ($inquiry->fully_paid_at ?? $inquiry->deposit_paid_at)?->format('M d, Y h:i A') ?? now()->format('M d, Y h:i A') }}</p>
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
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</p>
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

<div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
<p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
Your booking is confirmed and paid. If you have any questions, feel free to reply to this email or contact us directly. We look forward to hosting you!
</p>
<p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 500;">Warm regards,<br><strong style="color: #0d9488;">Helena Beach Resort Team</strong></p>
</div>
@endsection
