@component('mail::message')
{{-- PNG on an absolute URL: no mail client renders SVG, and several will not
     load a relative path. --}}
<img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy" width="180" style="width:180px;max-width:180px;height:auto;margin-bottom:16px;">

# Congratulations, {{ $certificate->name }}!

You passed the final quiz for **{{ $certificate->course->title }}** with a score of
**{{ $certificate->score_percent }}%** and earned your certificate.

Your certificate is attached to this email as a PDF. Its unique number is
**{{ $certificate->number }}**.

@component('mail::button', ['url' => $certificate->verifyUrl()])
Verify certificate
@endcomponent

Anyone can confirm this certificate is genuine at the link above.

Thanks,<br>
Pilot Academy
@endcomponent
