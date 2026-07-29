@extends('emails.layouts.modern')

@section('content')
<div style="margin-bottom: 24px;">
<div style="display: inline-flex; align-items: center; gap: 8px; background: #ecfdf5; border-radius: 8px; padding: 6px 14px; margin-bottom: 16px;">
<span style="font-size: 14px;">📬</span>
<span style="font-size: 13px; font-weight: 600; color: #059669;">New Booking Inquiry</span>
</div>

<h2 style="margin: 0 0 20px; font-size: 20px; font-weight: 700; color: #1e293b; letter-spacing: -0.02em;">
{{ $inquiry->name }} sent an inquiry
</h2>
</div>

<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Name</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->name }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Email</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->email }}</p>
</td>
</tr>
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Phone</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->phone ?? 'N/A' }}</p>
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
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_in }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Check-out</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->check_out }}</p>
</td>
</tr>
@endif
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Cottage</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->cottage?->name ?? 'Not specified' }}</p>
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
</table>
</div>

@if($inquiry->message)
<div style="background: #ffffff; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<p style="margin: 0 0 8px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Message</p>
<p style="margin: 0; font-size: 14px; color: #475569; line-height: 1.6; white-space: pre-wrap;">{{ $inquiry->message }}</p>
</div>
@endif

<div style="text-align: center;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
<tr>
<td style="background: #0d9488; border-radius: 8px; text-align: center;">
<a href="{{ url('/admin/inquiries/' . $inquiry->id) }}" class="btn" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">View in Admin Panel</a>
</td>
</tr>
</table>
</div>
@endsection
