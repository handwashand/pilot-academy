@extends('academy.layout')

@section('title', $term ? __t('academy.meta.search_term_title', ['term' => $term]) : __t('academy.meta.search_title'))

@php
    $metaDescription = __t('academy.meta.search_description');
@endphp

@section('content')
    <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.search.heading') }}</h1>
    <p class="text-slate-500 mt-1">{{ __t('academy.search.intro') }}</p>

    <div class="mt-4 mb-8">
        @include('academy.partials.search-form', ['term' => $term, 'inputId' => 'q'])
    </div>

    @if($term === '')
        <p class="text-slate-500">{{ __t('academy.search.type_something') }}</p>
    @else
        {{-- Block form only — see the Blade trap in agent.md. --}}
        @php
            $resultCount = $courses->count() + $lessons->count();
        @endphp

        <p class="text-sm text-slate-500 mb-6" role="status">
            {{ __tc('academy.search.results_for', $resultCount, ['term' => $term]) }}
        </p>

        @if($resultCount === 0)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-600 font-semibold">{{ __t('academy.search.nothing') }}</p>
                <p class="text-slate-500 text-sm mt-1">
                    {{ __t('academy.search.try_shorter') }}
                    <a href="{{ route('academy.home') }}" class="text-brand font-semibold">{{ __t('academy.search.browse_all') }}</a>.
                </p>
            </div>
        @endif

        @if($courses->isNotEmpty())
            <h2 class="text-xl font-extrabold text-navy mb-2">{{ __t('academy.search.courses') }}</h2>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-8 divide-y divide-slate-100">
                @foreach($courses as $course)
                    <a href="{{ route('academy.course', $course) }}"
                       class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50 active:bg-slate-100">
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-navy">{{ $course->title }}</span>
                            <span class="block text-sm text-slate-500 line-clamp-2">{{ $course->description }}</span>
                            @if($course->durationLabel())
                                <span class="block text-xs text-slate-400 mt-1">{{ $course->durationLabel() }}</span>
                            @endif
                        </span>
                        <span aria-hidden="true" class="text-slate-400 flex-none">&rarr;</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if($lessons->isNotEmpty())
            <h2 class="text-xl font-extrabold text-navy mb-2">{{ __t('academy.search.lessons') }}</h2>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                @foreach($lessons as $lesson)
                    @php
                        // A lesson in several courses opens in the first live one.
                        $lessonCourse = $lesson->courses->first();
                    @endphp
                    <a href="{{ route('academy.lesson', [$lessonCourse, $lesson]) }}"
                       class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50 active:bg-slate-100">
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-navy">{{ $lesson->title }}</span>
                            <span class="block text-sm text-slate-500">{{ $lesson->courses->pluck('title')->implode(' · ') }}</span>
                            @if($lesson->durationLabel())
                                <span class="block text-xs text-slate-400 mt-1">{{ $lesson->durationLabel() }}</span>
                            @endif
                        </span>
                        <span aria-hidden="true" class="text-slate-400 flex-none">&rarr;</span>
                    </a>
                @endforeach
            </div>
        @endif
    @endif
@endsection
