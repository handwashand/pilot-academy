{{--
    One cover for every lesson card.
    - A cover the creator added is shown as it is, nothing drawn over it.
    - Otherwise the cover is made automatically from the course's theme (the
      cover pattern and colour chosen on the course), with the lesson's place
      in its course and a "Lesson 3" chip. The number comes from the course
      order, never typed. A course banner, when there is one, sits faded under.
    Styles are the .lesson-cover rules in partials/head, because these
    utilities are not all in the committed CSS bundle.

    Expects: $lesson; optional $course (null for a lesson in no course),
    $number (null when there is no place to show) and $isDone.
--}}
@php
    $coverCourse = $course ?? null;
    $coverNumber = $number ?? null;
    $coverTheme = $coverCourse ? $coverCourse->coverTheme() : \App\Models\Course::coverThemeFor(null, null);
    $coverBanner = $coverCourse?->thumbnailUrl;
@endphp
<div class="lesson-cover"
     style="--cover-from: {{ $coverTheme['from'] }}; --cover-to: {{ $coverTheme['to'] }}; --cover-pattern: url('{{ $coverTheme['pattern'] }}');">
    @if($lesson->image_url)
        <img src="{{ $lesson->image_url }}" alt="" class="lesson-cover__image">
    @else
        @if($coverBanner)
            <img src="{{ $coverBanner }}" alt="" class="lesson-cover__image lesson-cover__image--banner">
        @endif
        <span class="lesson-cover__pattern" aria-hidden="true"></span>
        @if($coverNumber)
            <span class="lesson-cover__number" aria-hidden="true">{{ $coverNumber }}</span>
            <span class="lesson-cover__chip">{{ __t('academy.common.lesson_number', ['number' => $coverNumber]) }}</span>
        @else
            <span aria-hidden="true" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-white text-navy">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </span>
        @endif
    @endif

    @if($isDone ?? false)
        <span class="absolute end-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-ok text-sm text-white shadow">
            <span aria-hidden="true">✓</span>
            <span class="vh">{{ __t('academy.common.completed') }}</span>
        </span>
    @endif
</div>
