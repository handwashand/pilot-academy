@component('mail::message')
{{-- PNG on an absolute URL: no mail client renders SVG, and several will not
     load a relative path. --}}
<img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy" width="180" style="width:180px;max-width:180px;height:auto;margin-bottom:16px;">

# {{ __t('mail.certificate_issued.heading', ['name' => $certificate->name]) }}

{{ __t('mail.certificate_issued.passed', ['course' => $courseTitle, 'score' => $certificate->score_percent]) }}

{{ __t('mail.certificate_issued.attached', ['number' => $certificate->number]) }}

@component('mail::button', ['url' => $certificate->verifyUrl()])
{{ __t('mail.certificate_issued.button') }}
@endcomponent

{{ __t('mail.certificate_issued.anyone') }}

{{ __t('mail.common.thanks') }}<br>
Pilot Academy
@endcomponent
