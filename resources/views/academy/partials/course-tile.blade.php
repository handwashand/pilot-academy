{{--
    A course in a home page grid: cover on top (a short row on a phone), the
    audience, title, two lines of description and what to do next. The whole
    card opens the course page; the title is the one link, stretched over it.

    Expects: $course, $state ('new' | 'started' | 'finished').
--}}
@php
    $tileCount = __tc('academy.common.lessons', $course->published_lessons_count);
    $tileDuration = $course->durationLabel();
    $tileCta = match ($state) {
        'started' => __t('academy.home.continue_course'),
        'finished' => __t('academy.home.review_course'),
        default => __t('academy.home.start_course'),
    };
@endphp
<article class="course-tile">
    @include('academy.partials.course-cover', ['course' => $course, 'chip' => $tileDuration ? $tileCount.' · '.$tileDuration : $tileCount])
    <div class="course-tile__body">
        @if($course->audience)
            <span class="course-tile__tag">{{ __t('academy.common.for_audience', ['audience' => __t('academy.common.audience.'.$course->audience)]) }}</span>
        @endif
        <h3 class="course-tile__title"><a href="{{ route('academy.course', $course) }}">{{ $course->translated('title') }}</a></h3>
        <p class="course-tile__desc">{{ $course->translated('description') }}</p>
        {{-- On a phone the count is here; from 640px it is on the cover. --}}
        <span class="course-tile__meta"><span class="course-tile__meta--count">{{ $tileDuration ? $tileCount.' · ' : $tileCount }}</span>{{ $tileDuration }}</span>
        <span class="course-tile__cta" aria-hidden="true">{{ $tileCta }} <span class="inline-block rtl:rotate-180">&rarr;</span></span>
    </div>
</article>
