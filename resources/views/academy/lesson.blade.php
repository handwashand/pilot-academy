@extends('academy.layout')

@section('title', $lesson->title . ' — Pilot Academy')

@section('content')
    @php
        $isDone = in_array($lesson->id, $completed, true);
        $results = session('quiz_results', []);
        $passed = session('quiz_passed');
        $failed = session('quiz_failed');
    @endphp

    <div class="grid lg:grid-cols-[1fr_280px] gap-8">
        {{-- Main column --}}
        <div>
            <a href="{{ route('academy.course', $course) }}" class="text-sm text-brand font-semibold">&larr; {{ $course->title }}</a>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">{{ $lesson->title }}</h1>
            @if($lesson->summary)
                <p class="text-slate-500 mt-1">{{ $lesson->summary }}</p>
            @endif

            {{-- Video --}}
            @if($lesson->youtube_id)
                <div class="mt-6 rounded-2xl overflow-hidden border border-slate-200 shadow-sm aspect-video bg-black">
                    <iframe class="w-full h-full"
                            src="https://www.youtube.com/embed/{{ $lesson->youtube_id }}"
                            title="{{ $lesson->title }}"
                            frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                </div>
            @endif

            {{-- Lesson text --}}
            @if($lesson->content)
                <div class="prose-lesson mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    {!! $lesson->content !!}
                </div>
            @endif

            {{-- Quiz --}}
            @if($lesson->questions->isNotEmpty())
                <div class="mt-8 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-violet-600 text-xl">❓</span>
                        <h2 class="text-xl font-extrabold text-navy">Knowledge check</h2>
                    </div>
                    <p class="text-slate-500 text-sm mb-5">Answer all questions correctly to complete this lesson.</p>

                    @if($passed || $isDone)
                        <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-5 py-4 mb-5 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-ok text-white flex items-center justify-center">✓</span>
                            <div>
                                <div class="font-bold">Lesson complete!</div>
                                <div class="text-sm text-green-700">Nice work. {{ $next ? 'Continue to the next lesson.' : 'You\'ve finished the course.' }}</div>
                            </div>
                        </div>
                    @elseif($failed)
                        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 mb-5">
                            Some answers need another look — corrected items are marked below. Try again.
                        </div>
                    @endif

                    @unless($passed || $isDone)
                        <form method="POST" action="{{ route('academy.quiz', [$course, $lesson]) }}" class="space-y-6">
                            @csrf
                            @foreach($lesson->questions as $qn => $question)
                                @php($qResult = $results[$question->id] ?? null)
                                <fieldset class="border border-slate-200 rounded-xl p-5">
                                    <legend class="px-2 font-semibold text-navy">
                                        {{ $qn + 1 }}. {{ $question->prompt }}
                                        @if($qResult === true)<span class="text-ok">✓</span>@elseif($qResult === false)<span class="text-red-500">✗</span>@endif
                                    </legend>
                                    <div class="space-y-2 mt-2">
                                        @foreach($question->options as $option)
                                            <label class="flex items-center gap-3 px-3 py-2 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                                       class="text-brand"
                                                       {{ (int) old("answers.{$question->id}") === $option->id ? 'checked' : '' }} required>
                                                <span>{{ $option->text }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endforeach

                            <button class="rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                                Submit answers
                            </button>
                        </form>
                    @endunless
                </div>
            @endif

            {{-- Lesson nav --}}
            <div class="flex justify-between gap-3 mt-8">
                <div>
                    @if($prev)
                        <a href="{{ route('academy.lesson', [$course, $prev]) }}"
                           class="inline-block rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-600 hover:bg-white">
                            &larr; Previous
                        </a>
                    @endif
                </div>
                <div>
                    @if($next && ($passed || $isDone))
                        <a href="{{ route('academy.lesson', [$course, $next]) }}"
                           class="inline-block rounded-lg bg-navy text-white px-5 py-2.5 font-semibold hover:bg-slate-800">
                            Next lesson &rarr;
                        </a>
                    @elseif(! $next && ($passed || $isDone))
                        <a href="{{ route('academy.home') }}"
                           class="inline-block rounded-lg bg-ok text-white px-5 py-2.5 font-semibold">
                            Finish course ✓
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar: lesson list --}}
        <aside class="lg:sticky lg:top-20 self-start">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400 px-2 mb-2">Lessons</div>
                <ol class="space-y-1">
                    @foreach($lessons as $i => $l)
                        @php($lDone = in_array($l->id, $completed, true))
                        <li>
                            <a href="{{ route('academy.lesson', [$course, $l]) }}"
                               class="flex items-center gap-3 px-2 py-2 rounded-lg text-sm {{ $l->id === $lesson->id ? 'bg-blue-50 text-brand font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs {{ $lDone ? 'bg-ok text-white' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $lDone ? '✓' : $i + 1 }}
                                </span>
                                <span class="truncate">{{ $l->title }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </aside>
    </div>
@endsection
