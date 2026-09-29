@extends('academy.layout')

@section('title', __t('academy.tutorials.title').' - Pilot Academy')

@php
    $metaDescription = __t('academy.tutorials.meta');
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.tutorials.title') }}</h1>
        <p class="mt-1 max-w-3xl text-slate-500">{{ __t('academy.tutorials.intro') }}</p>
    </div>

    <p class="mb-6 text-sm text-slate-500" role="status">
        {{ __tc('academy.tutorials.videos', $videoCount) }}
    </p>

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
                    @php($isDone = in_array($lesson->id, $completed, true))
                    <a href="{{ route('academy.lesson', [$course, $lesson]) }}"
                       class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="relative flex h-28 items-center justify-center overflow-hidden bg-gradient-to-br from-brand to-navy">
                            @if($lesson->image_url)
                                <img src="{{ $lesson->image_url }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                            @endif
                            <span aria-hidden="true" class="relative flex h-11 w-11 items-center justify-center rounded-full bg-white/90 text-lg font-bold text-navy">&#9658;</span>
                            @if($isDone)
                                <span class="absolute right-3 top-3 flex h-7 w-7 items-center justify-center rounded-full bg-ok text-sm text-white shadow">
                                    <span aria-hidden="true">✓</span>
                                    <span class="vh">{{ __t('academy.common.completed') }}</span>
                                </span>
                            @endif
                        </div>

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
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="font-semibold text-slate-700">{{ __t('academy.tutorials.none') }}</p>
            <p class="mt-1 text-sm text-slate-500">{{ __t('academy.tutorials.none_hint') }}</p>
        </div>
    @endforelse
@endsection
