@extends('emails.layouts.modern')

@section('content')
<div style="text-align: center; margin-bottom: 28px;">
<div style="display: inline-block; width: 60px; height: 60px; background: #ecfdf5; border-radius: 50%; line-height: 60px; font-size: 28px; color: #059669;">🔑</div>
<h2 style="margin: 12px 0 4px; font-size: 22px; font-weight: 700; color: #059669; letter-spacing: -0.02em;">Reset Your Password</h2>
<p style="margin: 0; font-size: 14px; color: #64748b;">Hi <strong style="color: #1e293b;">{{ $name }}</strong>, we received a request to reset your Helena Beach Resort account password.</p>
</div>

<div style="text-align: center; margin-bottom: 24px;">
<table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
<tr>
<td style="background: #0d9488; border-radius: 8px; text-align: center;">
<a href="{{ $resetUrl }}" class="btn" style="display: inline-block; padding: 14px 32px; font-size: 14px; font-weight: 600; color: #ffffff; text-decoration: none; border-radius: 8px;">Reset Password</a>
</td>
</tr>
</table>
</div>

<div style="background: #fffbeb; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; border: 1px solid #fde68a;">
<p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.6;">
This link will expire in <strong>60 minutes</strong> and can only be used once. If you didn't request a password reset, you can safely ignore this email — your password won't be changed.
</p>
</div>

<div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
<p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
If the button above doesn't work, copy and paste this link into your browser:
</p>
<p style="margin: 8px 0 0; font-size: 12px; color: #0d9488; word-break: break-all;">{{ $resetUrl }}</p>
</div>
@endsection
