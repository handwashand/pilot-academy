@component('mail::message')
{{-- PNG on an absolute URL: no mail client renders SVG. --}}
<img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy" width="180" style="width:180px;max-width:180px;height:auto;margin-bottom:16px;">

# Still with us, {{ $name }}?

You made a start on Pilot Academy@if($lessonsDone > 0) — {{ $lessonsDone }} {{ Str::plural('lesson', $lessonsDone) }} finished so far@endif, and there is not much left to pick up.

@component('mail::button', ['url' => $url])
Continue where you left off
@endcomponent

That link signs you straight in and takes you to the next lesson, so there is no
password to remember. It is personal to you — please don't forward it.

Thanks,<br>
Pilot Academy
@endcomponent
