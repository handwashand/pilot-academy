@component('mail::message')
# This is a test email

{{ $name }} sent it from **Settings → Mail** in the Pilot Academy admin panel on {{ $sentAt }}.

If you are reading this, the academy can send email: certificates and reminders will reach students.

Links in academy emails start with {{ $appUrl }}. If that is not the address people use to open the academy, the logo and links in certificate emails will be broken.
@endcomponent
