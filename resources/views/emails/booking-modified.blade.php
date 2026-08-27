@extends('emails.layouts.modern')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
<div style="display: inline-block; width: 60px; height: 60px; background: #e0f2fe; border-radius: 50%; line-height: 60px; font-size: 28px; color: #0284c7;">✎</div>
<h2 style="margin: 12px 0 4px; font-size: 22px; font-weight: 700; color: #0284c7; letter-spacing: -0.02em;">Booking Updated</h2>
<p style="margin: 0; font-size: 14px; color: #64748b;">Hi <strong style="color: #1e293b;">{{ $inquiry->name }}</strong>, your booking details have been changed.</p>
</div>

<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Reference</p>
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">{{ $inquiry->reference_code }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Status</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">@if($inquiry->status === 'confirmed') Confirmed @elseif($inquiry->status === 'pending') Pending @else {{ ucfirst($inquiry->status) }} @endif</p>
</td>
</tr>
</table>
</div>

@if($previous)
<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<p style="margin: 0 0 12px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">What changed</p>
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Before</p>
@if(($previous['cottage'] ?? null) !== $inquiry->cottage?->name)
<p style="margin: 0; font-size: 14px; color: #1e293b;">Cottage: {{ $previous['cottage'] ?? 'Not specified' }}</p>
@endif
@if(($previous['booking_type'] ?? null) !== ($inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight'))
<p style="margin: 0; font-size: 14px; color: #1e293b;">Type: {{ $previous['booking_type'] ?? 'Not specified' }}</p>
@endif
@if(($previous['check_in'] ?? null) !== $inquiry->check_in?->format('M d, Y'))
<p style="margin: 0; font-size: 14px; color: #1e293b;">Check-in: {{ $previous['check_in'] ?? '—' }}</p>
@endif
@if(($previous['check_out'] ?? null) !== $inquiry->check_out?->format('M d, Y'))
<p style="margin: 0; font-size: 14px; color: #1e293b;">Check-out: {{ $previous['check_out'] ?? '—' }}</p>
@endif
@if(($previous['pax'] ?? null) !== $inquiry->pax)
<p style="margin: 0; font-size: 14px; color: #1e293b;">Guests: {{ $previous['pax'] ?? '—' }}</p>
@endif
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #0d9488; text-transform: uppercase; letter-spacing: 0.05em;">After</p>
@if(($previous['cottage'] ?? null) !== $inquiry->cottage?->name)
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">Cottage: {{ $inquiry->cottage?->name ?? 'Not specified' }}</p>
@endif
@if(($previous['booking_type'] ?? null) !== ($inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight'))
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">Type: {{ $inquiry->booking_type === 'day_tour' ? 'Day Tour' : 'Overnight' }}</p>
@endif
@if(($previous['check_in'] ?? null) !== $inquiry->check_in?->format('M d, Y'))
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">Check-in: {{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</p>
@endif
@if(($previous['check_out'] ?? null) !== $inquiry->check_out?->format('M d, Y'))
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">Check-out: {{ $inquiry->check_out?->format('M d, Y') ?? '—' }}</p>
@endif
@if(($previous['pax'] ?? null) !== $inquiry->pax)
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">Guests: {{ $inquiry->pax ?? '—' }}</p>
@endif
</td>
</tr>
<tr>
<td colspan="2" style="padding: 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">New Total</p>
<p style="margin: 0; font-size: 18px; font-weight: 700; color: #0d9488;">{{ formatPrice($inquiry->total_amount) }}</p>
</td>
</tr>
</table>
</div>
@else
<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
@if($inquiry->cottage)
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Cottage</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->cottage->name }}</p>
</td>
@endif
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Check-in</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_in?->format('M d, Y') ?? '—' }}</p>
</td>
</tr>
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Check-out</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_out?->format('M d, Y') ?? 'Same day' }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Total Amount</p>
<p style="margin: 0; font-size: 18px; font-weight: 700; color: #0d9488;">{{ formatPrice($inquiry->total_amount) }}</p>
</td>
</tr>
</table>
</div>
@endif

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
<p style="margin: 0 0 8px; font-size: 14px; color: #64748b; line-height: 1.6;">
If any of these changes look wrong, or you need anything else, just reply to this email or contact us. We look forward to hosting you!
</p>
<p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 500;">Warm regards,<br><strong style="color: #0d9488;">Helena Beach Resort Team</strong></p>
</div>
@endsection