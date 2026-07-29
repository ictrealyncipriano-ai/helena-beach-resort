@extends('emails.layouts.modern')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
<div style="display: inline-block; width: 60px; height: 60px; background: #fef2f2; border-radius: 50%; line-height: 60px; font-size: 28px; color: #dc2626;">✕</div>
<h2 style="margin: 12px 0 4px; font-size: 22px; font-weight: 700; color: #dc2626; letter-spacing: -0.02em;">Booking Cancelled</h2>
<p style="margin: 0; font-size: 14px; color: #64748b;">Hi <strong style="color: #1e293b;">{{ $inquiry->name }}</strong>, your booking has been cancelled.</p>
</div>

<div style="background: #f8fafc; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #e2e8f0;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Reference</p>
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #dc2626;">{{ $inquiry->reference_code }}</p>
</td>
@if($inquiry->cottage)
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em;">Cottage</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->cottage->name }}</p>
</td>
@endif
</tr>
</table>
</div>

<div style="background: #fffbeb; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid #fde68a;">
<p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.6;">
If you have any questions or would like to make a new booking, please don't hesitate to contact us. We'd love to welcome you in the future!
</p>
</div>

<div style="text-align: center; margin-bottom: 24px;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
<tr>
<td style="background: #0d9488; border-radius: 8px; text-align: center;">
<a href="{{ route('book') }}" class="btn" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">Make a New Booking</a>
</td>
</tr>
</table>
</div>

<div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
<p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 500;">Best regards,<br><strong style="color: #0d9488;">Helena Beach Resort Team</strong></p>
</div>
@endsection
