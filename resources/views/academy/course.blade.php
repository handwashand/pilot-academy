@extends('academy.layout')

@section('title', $course->title . ' — Pilot Academy')

@section('content')
    @php
        $total = $course->publishedLessons->count();
        $done = $course->publishedLessons->whereIn('id', $completed)->count();
        $pct = $total > 0 ? round($done / $total * 100) : 0;
    @endphp

    <a href="{{ route('academy.home') }}" class="text-sm text-brand font-semibold">&larr; All courses</a>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 my-5">
        <span class="inline-block text-xs font-semibold uppercase tracking-wide text-brand mb-1">Course</span>
        <h1 class="text-2xl font-extrabold text-navy">{{ $course->title }}</h1>
        <p class="text-slate-500 mt-1 max-w-2xl">{{ $course->description }}</p>
        <div class="mt-4 flex items-center gap-3">
            <div class="w-44 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-ok" style="width: {{ $pct }}%"></div>
            </div>
            <span class="text-sm text-slate-500">{{ $done }} / {{ $total }} lessons</span>
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($course->publishedLessons as $i => $lesson)
            @php($isDone = in_array($lesson->id, $completed, true))
            <a href="{{ route('academy.lesson', [$course, $lesson]) }}"
               class="group bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition">
                <div class="h-28 bg-gradient-to-br from-brand to-navy flex items-center justify-center relative">
                    <span class="text-white/90 text-4xl font-extrabold">{{ $i + 1 }}</span>
                    @if($isDone)
                        <span class="absolute top-3 right-3 w-7 h-7 rounded-full bg-ok text-white flex items-center justify-center text-sm">✓</span>
                    @endif
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-navy group-hover:text-brand transition">{{ $lesson->title }}</h3>
                    <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $lesson->summary }}</p>
                </div>
            </a>
        @endforeach
    </div>
@endsection
