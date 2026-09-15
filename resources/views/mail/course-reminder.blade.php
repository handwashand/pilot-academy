@component('mail::message')
{{-- PNG on an absolute URL: no mail client renders SVG. --}}
<img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy" width="180" style="width:180px;max-width:180px;height:auto;margin-bottom:16px;">

# {{ __t('mail.course_reminder.heading', ['name' => $name]) }}

{{ $lessonsDone > 0 ? __tc('mail.course_reminder.intro_progress', $lessonsDone) : __t('mail.course_reminder.intro') }}

@component('mail::button', ['url' => $url])
{{ __t('mail.course_reminder.button') }}
@endcomponent

{{ __t('mail.course_reminder.personal') }}

{{ __t('mail.common.thanks') }}<br>
Pilot Academy
@endcomponent
