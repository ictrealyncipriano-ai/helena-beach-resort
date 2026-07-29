<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<title>{{ config('app.name') }}</title>
<style type="text/css">
@media only screen and (max-width: 600px) {
  .email-container { width: 100% !important; }
  .content-padding { padding: 24px 20px !important; }
  .booking-details { padding: 16px !important; }
  .booking-details td { display: block !important; width: 100% !important; padding: 4px 0 !important; }
  .header-logo { height: 36px !important; }
  .footer-text { font-size: 12px !important; }
}
@media only screen and (max-width: 400px) {
  .content-padding { padding: 16px 12px !important; }
  .btn { display: block !important; width: 100% !important; }
}
</style>
</head>
<body style="margin: 0; padding: 0; background-color: #f0fdfa; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0fdfa;">
<tr>
<td align="center" style="padding: 32px 16px;">

<table class="email-container" role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">

<tr>
<td style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); border-radius: 16px 16px 0 0; padding: 32px 40px 24px; text-align: center;">
<img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name') }}" class="header-logo" style="height: 48px; width: auto; border-radius: 8px; margin-bottom: 8px;">
<h1 style="margin: 8px 0 0; font-size: 20px; font-weight: 700; color: #ffffff; letter-spacing: -0.02em;">{{ config('app.name') }}</h1>
<p style="margin: 4px 0 0; font-size: 13px; color: #ccfbf1; opacity: 0.9;">Infanta, Quezon — Beachfront Paradise</p>
</td>
</tr>

<tr>
<td class="content-padding" style="background-color: #ffffff; padding: 32px 40px; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0;">
@yield('content')
</td>
</tr>

<tr>
<td style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 100%); border-radius: 0 0 16px 16px; padding: 24px 40px; text-align: center;">
<p class="footer-text" style="margin: 0 0 8px; font-size: 13px; color: #ccfbf1; line-height: 1.5;">
Helena Beach Resort &bull; Purok Buyan, Brgy. Dinahican, Infanta, Quezon
</p>
<p class="footer-text" style="margin: 0 0 8px; font-size: 13px; color: #ccfbf1; line-height: 1.5;">
{{ App\Models\SiteSetting::getValue('contact_email', 'ict.realyncipriano@gmail.com') }}
@if(App\Models\SiteSetting::getValue('contact_phone')) &bull; {{ App\Models\SiteSetting::getValue('contact_phone') }} @endif
</p>
<p class="footer-text" style="margin: 0; font-size: 12px; color: #99f6e4;">
&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
</p>
</td>
</tr>

</table>

</td>
</tr>
</table>
</body>
</html>
