@component('mail::message')
# {{ __t('mail.mail_check.heading') }}

{{ __t('mail.mail_check.sent_by', ['name' => $name, 'date' => $sentAt]) }}

{{ __t('mail.mail_check.works') }}

{{ __t('mail.mail_check.links', ['url' => $appUrl]) }}
@endcomponent
