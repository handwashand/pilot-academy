@extends('academy.layout')

@section('title', __t('academy.tutorials.title').' - Pilot Academy')

@php
    $metaDescription = __t('academy.tutorials.meta');
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.tutorials.title') }}</h1>
        <p class="mt-1 max-w-3xl text-slate-500">{{ __t('academy.tutorials.intro') }}</p>
        @if($videoCount > 0)
            <p class="mt-2 text-sm text-slate-500">{{ __tc('academy.tutorials.videos', $videoCount) }}</p>
        @endif
    </div>

    @if($tutorials->isNotEmpty())
        <section class="mb-10">
            <h2 class="mb-4 text-xl font-extrabold text-navy">{{ __t('academy.tutorials.standalone') }}</h2>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($tutorials as $tutorial)
                    <a href="{{ route('academy.tutorial', $tutorial) }}"
                       class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="relative flex h-28 items-center justify-center bg-gradient-to-br from-navy to-brand">
                            <span aria-hidden="true" class="flex h-11 w-11 items-center justify-center rounded-full bg-white/90">
                                <svg class="h-5 w-5 text-navy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M8 5.5v13l11-6.5-11-6.5Z" />
                                </svg>
                            </span>
                        </div>

                        <div class="p-5">
                            <h3 class="font-bold text-navy transition group-hover:text-brand">{{ $tutorial->translated('title') }}</h3>
                            @if($tutorial->translated('summary'))
                                <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $tutorial->translated('summary') }}</p>
                            @endif
                            @if($tutorial->duration_minutes)
                                <p class="mt-2 text-xs text-slate-400">{{ __t('academy.webinars.minutes', ['count' => $tutorial->duration_minutes]) }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if($standaloneLessons->isNotEmpty() || $standaloneTags !== [])
        <section class="mb-10">
            <h2 class="mb-4 text-xl font-extrabold text-navy">{{ __t('academy.tutorials.standalone_lessons') }}</h2>

            @if($standaloneTags !== [])
                <nav class="mb-5 flex flex-wrap gap-2" aria-label="{{ __t('academy.lessons.tags_aria') }}">
                    @if($activeTag !== '')
                        <a href="{{ route('academy.tutorials') }}"
                           class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold bg-slate-200 text-slate-600">
                            {{ __t('academy.lessons.clear') }}
                        </a>
                    @endif
                    @foreach($standaloneTags as $tag)
                        <a href="{{ route('academy.tutorials', ['tag' => $tag]) }}"
                           class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold {{ $activeTag === $tag ? 'bg-brand text-white' : 'bg-slate-200 text-slate-600' }}">
                            {{ $tag }}
                        </a>
                    @endforeach
                </nav>
            @endif

            @if($standaloneLessons->isNotEmpty())
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($standaloneLessons as $lesson)
                        @php
                            $isDone = in_array($lesson->id, $completed, true);
                        @endphp
                        <a href="{{ route('academy.standalone.lesson', $lesson) }}"
                           class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            @include('academy.partials.lesson-cover', ['lesson' => $lesson, 'course' => null, 'number' => null, 'isDone' => $isDone])

                            <div class="p-5">
                                <h3 class="font-bold text-navy transition group-hover:text-brand">{{ $lesson->translated('title') }}</h3>
                                @if($lesson->translated('summary'))
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $lesson->translated('summary') }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-400">
                                    @if($lesson->questions_count)
                                        {{ __tc('academy.lessons.questions', $lesson->questions_count) }}
                                    @endif
                                    @if($lesson->durationLabel())
                                        @if($lesson->questions_count)
                                            <span aria-hidden="true">·</span>
                                        @endif
                                        {{ $lesson->durationLabel() }}
                                    @endif
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="font-semibold text-slate-700">{{ __t('academy.lessons.none') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __t('academy.lessons.none_hint') }}</p>
                </div>
            @endif
        </section>
    @endif

    @if($popularLessons->isNotEmpty())
        <section class="mb-10">
            <h2 class="mb-4 text-xl font-extrabold text-navy">{{ __t('academy.tutorials.popular') }}</h2>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($popularLessons as $lesson)
                    @php
                        $isDone = in_array($lesson->id, $completed, true);
                        $firstCourse = $lesson->courses->first();
                    @endphp
                    @if($firstCourse)
                        <a href="{{ route('academy.lesson', [$firstCourse, $lesson]) }}"
                           class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            @include('academy.partials.lesson-cover', ['lesson' => $lesson, 'course' => $firstCourse, 'number' => null, 'isDone' => $isDone])

                            <div class="p-5">
                                <h3 class="font-bold text-navy transition group-hover:text-brand">{{ $lesson->translated('title') }}</h3>
                                @if($lesson->translated('summary'))
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $lesson->translated('summary') }}</p>
                                @endif
                                <p class="mt-2 text-xs text-slate-400">{{ $firstCourse->translated('title') }}</p>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    @if($courses->isNotEmpty())
        @if($tutorials->isNotEmpty() || $standaloneLessons->isNotEmpty() || $popularLessons->isNotEmpty())
            <h2 class="mb-4 text-xl font-extrabold text-navy">{{ __t('academy.tutorials.from_courses') }}</h2>
        @endif
    @endif

    @forelse($courses as $course)
        <section class="mb-10">
            <div class="mb-4 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-xl font-extrabold text-navy">{{ $course->translated('title') }}</h2>
                <a href="{{ route('academy.course', $course) }}" class="text-sm font-semibold text-brand hover:underline">
                    {{ __t('academy.tutorials.open_course') }}
                </a>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($course->publishedLessons as $lesson)
                    @php
                        $isDone = in_array($lesson->id, $completed, true);
                    @endphp
                    <a href="{{ route('academy.lesson', [$course, $lesson]) }}"
                       class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        @include('academy.partials.lesson-cover', ['lesson' => $lesson, 'course' => $course, 'number' => $loop->iteration, 'isDone' => $isDone])

                        <div class="p-5">
                            <h3 class="font-bold text-navy transition group-hover:text-brand">{{ $lesson->translated('title') }}</h3>
                            <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ $lesson->translated('summary') }}</p>
                            <p class="mt-2 text-xs text-slate-400">
                                {{ __tc('academy.tutorials.videos', count($lesson->videoEntries())) }}
                                @if($lesson->durationLabel())
                                    <span aria-hidden="true">·</span> {{ $lesson->durationLabel() }}
                                @endif
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        @if($tutorials->isEmpty() && $standaloneLessons->isEmpty() && $popularLessons->isEmpty())
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="font-semibold text-slate-700">{{ __t('academy.tutorials.none') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __t('academy.tutorials.none_hint') }}</p>
            </div>
        @endif
    @endforelse
@endsection
