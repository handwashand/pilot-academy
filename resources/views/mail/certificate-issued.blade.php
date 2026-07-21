@component('mail::message')
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
