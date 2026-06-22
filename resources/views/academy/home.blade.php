@extends('academy.layout')

@section('title', 'Pilot Academy — My Learning')

@section('content')
    @if(! session('student_name'))
        <div class="mb-8 rounded-2xl bg-gradient-to-br from-brand to-navy text-white p-7 sm:p-9">
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1">Welcome to Pilot Academy</h1>
            <p class="text-white/80 mb-5">Short, focused lessons with videos and quizzes — get productive from day one.</p>
            <form method="POST" action="{{ route('academy.name') }}" class="flex flex-col sm:flex-row gap-3 max-w-md">
                @csrf
                <input type="text" name="name" required placeholder="Your name"
                       class="flex-1 rounded-lg px-4 py-2.5 text-slate-800 outline-none">
                <button class="rounded-lg bg-white text-navy font-semibold px-5 py-2.5 hover:bg-slate-100">
                    Start learning
                </button>
            </form>
        </div>
    @else
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">Welcome back, {{ session('student_name') }}</h1>
            <p class="text-slate-500">Pick up where you left off.</p>
        </div>
    @endif

    @forelse($courses as $course)
        @php
            $total = $course->published_lessons_count;
            $done = $course->publishedLessons->whereIn('id', $completed)->count();
            $pct = $total > 0 ? round($done / $total * 100) : 0;
        @endphp

        <section class="mb-10">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-brand">Course</span>
                            @if($course->audience_label)
                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-blue-50 text-brand">For {{ $course->audience_label }}</span>
                            @endif
                        </div>
                        <h2 class="text-xl font-extrabold text-navy">{{ $course->title }}</h2>
                        <p class="text-slate-500 mt-1 max-w-2xl">{{ $course->description }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-500 mb-1">{{ $done }} / {{ $total }} lessons</div>
                        <div class="w-44 h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full bg-ok" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
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
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-slate-100 text-slate-500">{{ ucfirst($course->level) }}</span>
                                @if($lesson->youtube_url)
                                    <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-blue-50 text-brand">Video</span>
                                @endif
                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-violet-50 text-violet-600">Quiz</span>
                            </div>
                            <h3 class="font-bold text-navy group-hover:text-brand transition">{{ $lesson->title }}</h3>
                            <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $lesson->summary }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="text-slate-500">No published courses yet.</p>
    @endforelse
@endsection
