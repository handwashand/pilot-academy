@extends('academy.layout')

@section('title', $lesson->title . ' — Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($lesson->summary ?: $lesson->content))), 155)
        ?: $lesson->title.' — урок курса «'.$course->title.'» в Pilot Academy.';
@endphp

@section('content')
    @php
        $isDone = in_array($lesson->id, $completed, true);
        $results = session('quiz_results', []);
        $passed = session('quiz_passed');
        $failed = session('quiz_failed');
    @endphp

    <div class="grid lg:grid-cols-[1fr_280px] gap-6 lg:gap-8">
        {{-- Main column --}}
        <div>
            <a href="{{ route('academy.course', $course) }}" class="text-sm text-brand font-semibold">&larr; {{ $course->title }}</a>

            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">{{ $lesson->title }}</h1>
            @if($lesson->summary)
                <p class="text-slate-500 mt-1">{{ $lesson->summary }}</p>
            @endif

            {{-- Video: uploaded file takes priority, otherwise YouTube embed --}}
            @if($lesson->video_url)
                <div class="mt-6 rounded-2xl overflow-hidden border border-slate-200 shadow-sm aspect-video bg-black">
                    <video class="w-full h-full" controls preload="metadata">
                        <source src="{{ $lesson->video_url }}">
                        Your browser does not support the video tag.
                    </video>
                </div>
            @elseif($lesson->youtube_id)
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

            {{-- Documentation links --}}
            @if(! empty($lesson->doc_links))
                <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-brand text-xl">📖</span>
                        <h2 class="text-xl font-extrabold text-navy">Documentation</h2>
                    </div>
                    <p class="text-slate-500 text-sm mb-4">Read more in the Pilot user guide.</p>
                    <ul class="space-y-2">
                        @foreach($lesson->doc_links as $link)
                            @if(! empty($link['url']) && ! empty($link['title']))
                                <li>
                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener"
                                       class="flex items-center gap-3 px-4 py-3 rounded-lg border border-slate-200 text-slate-700 font-medium hover:bg-slate-50 hover:border-brand/40 active:bg-slate-100">
                                        <span class="text-slate-400 flex-none">↗</span>
                                        <span class="min-w-0 break-words">{{ $link['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Quiz --}}
            @if($lesson->questions->isNotEmpty())
                @php
                    $mode = $quiz['mode'] ?? 'open';
                    $timeup = session('quiz_timeup');
                    $showForm = in_array($mode, ['open', 'active'], true);
                    $secondsRemaining = $quiz['secondsRemaining'] ?? null;
                @endphp
                <div id="quiz" class="mt-8 scroll-mt-20 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <div class="flex items-center justify-between gap-2 mb-1 flex-wrap">
                        <div class="flex items-center gap-2">
                            <span class="text-violet-600 text-xl">❓</span>
                            <h2 class="text-xl font-extrabold text-navy">Knowledge check</h2>
                        </div>
                        @if($mode === 'active' && $secondsRemaining !== null)
                            <span id="quiz-timer" class="text-sm font-bold px-3 py-1.5 rounded-lg bg-slate-100 text-navy">
                                ⏱ <span id="quiz-countdown">--:--</span>
                            </span>
                        @endif
                    </div>
                    <p class="text-slate-500 text-sm mb-5">Answer all questions correctly to complete this lesson.</p>

                    {{-- Status banners --}}
                    @if($passed || $isDone)
                        <div class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-5 py-4 mb-5 flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-ok text-white flex items-center justify-center flex-none">✓</span>
                            <div>
                                <div class="font-bold">Lesson complete!</div>
                                <div class="text-sm text-green-700">Nice work. {{ $next ? 'Continue to the next lesson.' : 'You\'ve finished the course.' }}</div>
                            </div>
                        </div>
                    @elseif($timeup)
                        <div class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-5 py-4 mb-5">
                            ⏱ <strong>Time's up.</strong> This attempt was not counted as successful.
                        </div>
                    @elseif($failed)
                        <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 mb-5">
                            Attempt unsuccessful
                            @if(session('quiz_score'))
                                (score {{ session('quiz_score') }})
                            @endif
                            —
                            @if($mode === 'open')
                                corrected items are marked below. Try again.
                            @else
                                review the lesson and try again.
                            @endif
                        </div>
                    @endif

                    @unless($passed || $isDone)
                        @if($mode === 'prestart')
                            {{-- Pre-start: warn about time and attempts --}}
                            <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4 mb-5">
                                <div class="font-semibold mb-1">Before you start</div>
                                <ul class="text-sm space-y-1 list-disc pl-5">
                                    @if($quiz['timeLimit'])
                                        <li><strong>Time limit: {{ $quiz['timeLimit'] }} min.</strong> The countdown starts when you press Start and can't be paused.</li>
                                    @endif
                                    @if($quiz['attemptsRemaining'] !== null)
                                        <li>You have <strong>{{ $quiz['attemptsRemaining'] }}</strong> attempt(s) left.</li>
                                    @endif
                                    <li>Make sure you have enough time to finish. If not, it's better to come back later.</li>
                                </ul>
                            </div>
                            <form method="POST" action="{{ route('academy.quiz.start', [$course, $lesson]) }}">
                                @csrf
                                <button class="w-full sm:w-auto rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                                    Start quiz
                                </button>
                            </form>
                        @elseif($mode === 'exhausted')
                            <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-5 py-4">
                                <strong>No attempts remaining.</strong> You've used all {{ $quiz['maxAttempts'] }} attempts for this quiz.
                            </div>
                        @endif

                        @if($showForm)
                            <form method="POST" action="{{ route('academy.quiz', [$course, $lesson]) }}" id="quiz-form" class="space-y-6">
                                @csrf
                                @foreach($lesson->questions as $qn => $question)
                                    @php
                                        $qResult = $mode === 'open' ? ($results[$question->id] ?? null) : null;
                                    @endphp
                                    <fieldset class="border border-slate-200 rounded-xl p-5">
                                        <legend class="px-2 font-semibold text-navy">
                                            {{ $qn + 1 }}. {{ $question->prompt }}
                                            @if($qResult === true)<span class="text-ok">✓</span>@elseif($qResult === false)<span class="text-red-500">✗</span>@endif
                                        </legend>
                                        <div class="space-y-2 mt-2">
                                            @foreach($question->options as $option)
                                                <label class="flex items-center gap-3 px-3 py-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50 active:bg-slate-100">
                                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                                           class="text-brand w-4 h-4 flex-none"
                                                           {{ $mode === 'open' && (int) old("answers.{$question->id}") === $option->id ? 'checked' : '' }} required>
                                                    <span>{{ $option->text }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endforeach

                                <button class="w-full sm:w-auto rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                                    Submit answers
                                </button>
                            </form>

                            @if($mode === 'active' && $secondsRemaining !== null)
                                <script>
                                    (function () {
                                        var total = {{ (int) $secondsRemaining }};
                                        var out = document.getElementById('quiz-countdown');
                                        var badge = document.getElementById('quiz-timer');
                                        var form = document.getElementById('quiz-form');
                                        var done = false;
                                        function fmt(s) { var m = Math.floor(s / 60), x = s % 60; return m + ':' + (x < 10 ? '0' : '') + x; }
                                        function tick() {
                                            if (out) out.textContent = fmt(Math.max(0, total));
                                            if (badge && total <= 30) { badge.classList.remove('bg-slate-100', 'text-navy'); badge.classList.add('bg-red-100', 'text-red-700'); }
                                            if (total <= 0) { if (!done && form) { done = true; form.submit(); } return; }
                                            total--; setTimeout(tick, 1000);
                                        }
                                        tick();
                                    })();
                                </script>
                            @endif
                        @endif
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
