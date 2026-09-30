@component('mail::message')
{{-- PNG on an absolute URL: no mail client renders SVG. --}}
<img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy" width="180" style="width:180px;max-width:180px;height:auto;margin-bottom:16px;">

# {{ __t('mail.password_reset.heading', ['name' => $name]) }}

{{ __t('mail.password_reset.intro') }}

@component('mail::button', ['url' => $url])
{{ __t('mail.password_reset.button') }}
@endcomponent

{{ __tc('mail.password_reset.expires', $minutes) }}

{{ __t('mail.password_reset.ignore') }}

{{ __t('mail.common.thanks') }}<br>
Pilot Academy
@endcomponent
