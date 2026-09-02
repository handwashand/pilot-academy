@extends('academy.layout')

@section('title', 'Pilot Academy — My Learning')

@php
    $metaDescription = 'Pilot Academy — online training for the Pilot vehicle monitoring platform. Short, focused lessons with videos and quizzes: get productive from day one.';
@endphp

@section('content')
    @auth
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">Welcome back, {{ auth()->user()->name }}</h1>
            <p class="text-slate-500">Pick up where you left off — your progress is saved to your account.</p>
        </div>
    @elseif(session('student_name'))
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">Welcome back, {{ session('student_name') }}</h1>
            <p class="text-slate-500">
                Pick up where you left off.
                <a href="{{ route('register') }}" class="text-brand font-semibold">Create an account</a> to save your progress.
            </p>
        </div>
    @else
        <div class="mb-8 rounded-2xl bg-gradient-to-br from-brand to-navy text-white p-7 sm:p-9">
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1">Welcome to Pilot Academy</h1>
            <p class="text-white mb-5">Short, focused lessons with videos and quizzes — get productive from day one.</p>
            <form method="POST" action="{{ route('academy.name') }}" class="flex flex-col sm:flex-row gap-3 max-w-md">
                @csrf
                {{-- bg-white is load-bearing: Tailwind's preflight makes form
                     controls transparent, so without it the dark text sits
                     straight on the gradient and all but vanishes at the navy
                     end. text-slate-800 must stay too — the card sets
                     text-white, so an unstyled input would be white on white.
                     Both classes are already in the committed CSS bundle, so
                     this reads correctly before CI rebuilds it. --}}
                <input type="text" name="name" required placeholder="Your name" aria-label="Your name"
                       class="flex-1 rounded-lg bg-white px-4 py-2.5 text-slate-800 focus:ring-2 focus:ring-white">
                <button class="rounded-lg bg-white text-navy font-semibold px-5 py-2.5 hover:bg-slate-100 focus:ring-2 focus:ring-white">
                    Start learning
                </button>
            </form>
            {{-- Full white, not white/70: over the brand blue that was 3.2:1.
                 The hierarchy comes from the smaller size instead. --}}
            <p class="text-white text-sm mt-3">
                Have an account? <a href="{{ route('login') }}" class="underline font-semibold">Log in</a>
                · <a href="{{ route('register') }}" class="underline font-semibold">Register</a>
            </p>
        </div>
    @endauth

    @if($resume)
        @php
            $resumePct = $resume['total'] > 0 ? round($resume['done'] / $resume['total'] * 100) : 0;
        @endphp
        <a href="{{ route('academy.lesson', [$resume['course'], $resume['lesson']]) }}"
           class="group block mb-8 rounded-2xl border border-brand/30 bg-blue-50/60 p-5 sm:p-6 hover:border-brand hover:shadow-md transition">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand">Continue where you left off</span>
                    <h2 class="mt-1 text-lg sm:text-xl font-extrabold text-navy group-hover:text-brand transition">
                        {{ $resume['lesson']->title }}
                    </h2>
                    <p class="text-sm text-slate-500 truncate">{{ $resume['course']->title }}</p>
                </div>

                <div class="shrink-0 sm:text-right">
                    <div class="text-sm text-slate-500 mb-1">{{ $resume['done'] }} / {{ $resume['total'] }} lessons</div>
                    <div class="w-full sm:w-44 h-2 rounded-full bg-white overflow-hidden">
                        <div class="h-full bg-ok" style="width: {{ $resumePct }}%"></div>
                    </div>
                    <span class="mt-3 inline-block rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white">
                        Resume
                    </span>
                </div>
            </div>
        </a>
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
                        <div class="h-28 relative flex items-center justify-center bg-gradient-to-br from-brand to-navy overflow-hidden">
                            @if($lesson->image_url)
                                <img src="{{ $lesson->image_url }}" alt="{{ $lesson->title }}" class="absolute inset-0 w-full h-full object-cover">
                            @else
                                <span class="text-white/90 text-4xl font-extrabold">{{ $i + 1 }}</span>
                            @endif
                            @if($isDone)
                                <span class="absolute top-3 right-3 w-7 h-7 rounded-full bg-ok text-white flex items-center justify-center text-sm shadow">✓</span>
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
