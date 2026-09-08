@extends('academy.layout')

@section('title', $term ? 'Search: '.$term.' — Pilot Academy' : 'Search — Pilot Academy')

@php
    $metaDescription = 'Search Pilot Academy courses and lessons on the Pilot vehicle monitoring platform.';
@endphp

@section('content')
    <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">Search</h1>
    <p class="text-slate-500 mt-1">Find a course or a lesson by name.</p>

    <div class="mt-4 mb-8">
        @include('academy.partials.search-form', ['term' => $term, 'inputId' => 'q'])
    </div>

    @if($term === '')
        <p class="text-slate-500">Type something above to search.</p>
    @else
        @php($resultCount = $courses->count() + $lessons->count())

        <p class="text-sm text-slate-500 mb-6" role="status">
            {{ $resultCount }} {{ $resultCount === 1 ? 'result' : 'results' }} for &ldquo;{{ $term }}&rdquo;
        </p>

        @if($resultCount === 0)
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <p class="text-slate-600 font-semibold">Nothing matched that.</p>
                <p class="text-slate-500 text-sm mt-1">
                    Try a shorter word, or
                    <a href="{{ route('academy.home') }}" class="text-brand font-semibold">browse all courses</a>.
                </p>
            </div>
        @endif

        @if($courses->isNotEmpty())
            <h2 class="text-xl font-extrabold text-navy mb-2">Courses</h2>
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
            <h2 class="text-xl font-extrabold text-navy mb-2">Lessons</h2>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">
                @foreach($lessons as $lesson)
                    <a href="{{ route('academy.lesson', [$lesson->course, $lesson]) }}"
                       class="flex items-center gap-3 px-5 py-4 hover:bg-slate-50 active:bg-slate-100">
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-navy">{{ $lesson->title }}</span>
                            <span class="block text-sm text-slate-500">{{ $lesson->course->title }}</span>
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
