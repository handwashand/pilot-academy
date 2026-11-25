{{--
    A course's cover: its banner when one was uploaded, otherwise its cover
    picture and colour (the same drawing and styles as a lesson cover, without
    a number). Optional $chip is shown on it, bottom left, from 640px up.

    Expects: $course; optional $chip, $shift (0-2) and $class.
--}}
@php
    $coverTheme = $course->coverTheme();
@endphp
<div class="lesson-cover lesson-cover--shift-{{ $shift ?? 1 }} {{ $class ?? '' }}" aria-hidden="true"
     style="--cover-from: {{ $coverTheme['from'] }}; --cover-to: {{ $coverTheme['to'] }}; --cover-pattern: url('{{ $coverTheme['pattern'] }}');">
    @if($course->thumbnail_url)
        <img src="{{ $course->thumbnail_url }}" alt="" class="lesson-cover__image">
    @else
        <span class="lesson-cover__pattern"></span>
    @endif
    @if(! empty($chip))
        <span class="course-tile__count">{{ $chip }}</span>
    @endif
</div>
