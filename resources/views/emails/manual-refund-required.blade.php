@extends('emails.layouts.modern')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
<div style="display: inline-block; width: 60px; height: 60px; background: #fffbeb; border-radius: 50%; line-height: 60px; font-size: 28px; color: #d97706;">⚠</div>
<h2 style="margin: 12px 0 4px; font-size: 22px; font-weight: 700; color: #d97706; letter-spacing: -0.02em;">Manual Refund Required</h2>
<p style="margin: 0; font-size: 14px; color: #64748b;">Booking <strong style="color: #1e293b;">{{ $inquiry->reference_code }}</strong> was cancelled by the guest with <strong style="color: #1e293b;">{{ formatPrice($inquiry->collectedAmount()) }}</strong> collected manually.</p>
</div>

<div style="background: #fffbeb; border-radius: 12px; padding: 20px 24px; margin-bottom: 24px; border: 1px solid #fde68a;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" class="booking-details">
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em;">Reference</p>
<p style="margin: 0; font-size: 14px; font-weight: 600; color: #0d9488;">{{ $inquiry->reference_code }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em;">To Refund Manually</p>
<p style="margin: 0; font-size: 18px; font-weight: 700; color: #d97706;">₱{{ $inquiry->collectedAmount() }}</p>
</td>
</tr>
<tr>
<td width="50%" style="padding: 6px 12px 6px 0; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em;">Guest</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->name }}</p>
</td>
<td width="50%" style="padding: 6px 0 6px 12px; vertical-align: top;">
<p style="margin: 0 0 2px; font-size: 11px; font-weight: 600; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em;">Payment Method</p>
<p style="margin: 0; font-size: 14px; color: #1e293b;">{{ $inquiry->paymentMethodLabel() }}</p>
</td>
</tr>
</table>
</div>

<div style="text-align: center; margin-bottom: 24px;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
<tr>
<td style="background: #0d9488; border-radius: 8px; text-align: center;">
<a href="{{ route('admin.inquiries.show', $inquiry) }}" class="btn" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">Open in Admin Panel</a>
</td>
</tr>
</table>
</div>

<div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
<p style="margin: 0 0 8px; font-size: 14px; color: #64748b; line-height: 1.6;">
This booking was settled manually (cash or bank transfer), so it cannot be refunded automatically through PayMongo. Please return ₱{{ $inquiry->collectedAmount() }} to the guest through your original collection channel and record it in the admin panel.
</p>
<p style="margin: 0; font-size: 14px; color: #1e293b; font-weight: 500;">Helena Beach Resort Booking System</p>
</div>
@endsection
