@extends('academy.layout')

@section('title', $lesson->title . ' — Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($lesson->summary ?: $lesson->content))), 155)
        ?: $lesson->title.' — a lesson from '.$course->title.' on Pilot Academy.';
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

            @if(! $course->isPublished() || ! $lesson->isPublished())
                {{-- Only admins ever reach this page for unpublished content. --}}
                <div class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    <strong>{{ ! $lesson->isPublished() ? 'Lesson: '.$lesson->statusLabel() : 'Course: '.$course->statusLabel() }}</strong>
                    — students cannot see this. You are previewing it as an admin.
                </div>
            @endif

            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">{{ $lesson->title }}</h1>
            @if($lesson->summary)
                <p class="text-slate-500 mt-1">{{ $lesson->summary }}</p>
            @endif
            @if($lesson->durationLabel())
                <p class="text-sm text-slate-400 mt-1">{{ $lesson->durationLabel() }}</p>
            @endif

            {{-- Video: uploaded file takes priority, otherwise YouTube embed --}}
            @if($lesson->video_url)
                <div class="mt-6 rounded-2xl overflow-hidden border border-slate-200 shadow-sm aspect-video bg-black">
                    <video id="lesson-video" class="w-full h-full" controls playsinline preload="metadata">
                        <source src="{{ $lesson->video_url }}">
                        Your browser does not support the video tag.
                    </video>
                </div>

                {{-- An uploaded file only gets the browser's bare player, while a
                     YouTube lesson comes with speed control. People re-watch
                     training to revise, so speed is worth having on both. --}}
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-400" id="speed-label">Playback speed</span>
                    {{-- Pairs, not a keyed array: PHP casts float keys to int,
                         so 1.25 and 1.5 would collide into a single entry. --}}
                    @foreach([['1', 'Normal'], ['1.25', '1.25×'], ['1.5', '1.5×'], ['2', '2×']] as [$rate, $caption])
                        <button type="button" data-speed="{{ $rate }}" aria-describedby="speed-label"
                                class="inline-flex items-center h-11 px-3 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50 active:bg-slate-100">
                            {{ $caption }}
                        </button>
                    @endforeach
                </div>

                <script>
                    (function () {
                        var video = document.getElementById('lesson-video');
                        if (!video) return;

                        // Pick the video up where it was left. Sent back to the
                        // server every 10s of playback and on the way out, so an
                        // interrupted 25-minute video does not restart.
                        var startAt = {{ (int) ($videoPosition ?? 0) }};
                        var saveUrl = @json($lesson->video_url ? route('academy.lesson.position', [$course, $lesson]) : null);
                        var token = document.querySelector('meta[name="csrf-token"]');

                        if (startAt > 0) {
                            video.addEventListener('loadedmetadata', function () {
                                // Never resume within the last 15s: that is
                                // "finished", and reopening should start again.
                                if (isFinite(video.duration) && startAt < video.duration - 15) {
                                    video.currentTime = startAt;
                                }
                            }, { once: true });
                        }

                        if (saveUrl && token) {
                            var lastSaved = -1;
                            var save = function () {
                                var at = Math.floor(video.currentTime || 0);
                                if (at === lastSaved) return;
                                lastSaved = at;
                                // keepalive so the last write survives the page
                                // being closed mid-lesson.
                                fetch(saveUrl, {
                                    method: 'POST',
                                    keepalive: true,
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': token.content,
                                    },
                                    body: JSON.stringify({ seconds: at }),
                                }).catch(function () { /* losing a position is not worth an error */ });
                            };

                            video.addEventListener('timeupdate', function () {
                                if (Math.floor(video.currentTime) % 10 === 0) save();
                            });
                            video.addEventListener('pause', save);
                            window.addEventListener('pagehide', save);
                        }

                        // Volume and speed are remembered across lessons, so a
                        // student sets them once. Storage can throw in private
                        // windows, so every access is guarded.
                        function read(key) { try { return window.localStorage.getItem(key); } catch (e) { return null; } }
                        function write(key, value) { try { window.localStorage.setItem(key, value); } catch (e) {} }

                        var savedVolume = parseFloat(read('pa.video.volume'));
                        if (!isNaN(savedVolume) && savedVolume >= 0 && savedVolume <= 1) video.volume = savedVolume;
                        if (read('pa.video.muted') === '1') video.muted = true;

                        var savedRate = parseFloat(read('pa.video.rate'));
                        if (!isNaN(savedRate) && savedRate >= 0.5 && savedRate <= 2) video.playbackRate = savedRate;

                        video.addEventListener('volumechange', function () {
                            write('pa.video.volume', video.volume);
                            write('pa.video.muted', video.muted ? '1' : '0');
                        });

                        var buttons = document.querySelectorAll('[data-speed]');
                        function mark() {
                            buttons.forEach(function (button) {
                                var on = parseFloat(button.dataset.speed) === video.playbackRate;
                                button.setAttribute('aria-pressed', on ? 'true' : 'false');
                                button.classList.toggle('bg-slate-100', on);
                                button.classList.toggle('text-navy', on);
                            });
                        }
                        buttons.forEach(function (button) {
                            button.addEventListener('click', function () {
                                video.playbackRate = parseFloat(button.dataset.speed);
                                write('pa.video.rate', video.playbackRate);
                                mark();
                            });
                        });
                        video.addEventListener('ratechange', mark);
                        mark();
                    })();
                </script>
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

            {{-- Transcript. Collapsed so it never buries the lesson, but present
                 in the page for anyone who cannot use the audio, wants to skim
                 rather than scrub, or uses Ctrl+F. --}}
            @if($lesson->transcript)
                <details class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <summary class="font-bold text-navy cursor-pointer">
                        Transcript
                        <span class="text-sm font-medium text-slate-500">— read instead of watching</span>
                    </summary>
                    <div class="transcript mt-4 text-slate-700">{{ $lesson->transcript }}</div>
                </details>
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
                    <p class="text-slate-500 text-sm mb-2">Answer all questions correctly to complete this lesson.</p>

                    {{-- What the quiz costs, before it starts rather than after.
                         Attempts remaining is only known once someone is logged
                         in and the lesson sets limits. --}}
                    @php
                        // Block form, not @php(...): the inline form silently
                        // emitted an unterminated "<?php(" here and swallowed
                        // the rest of the template.
                        $questionCount = $lesson->questions->count();
                    @endphp
                    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 mb-5">
                        <span>{{ $questionCount }} {{ $questionCount === 1 ? 'question' : 'questions' }}</span>
                        @if($lesson->quiz_time_limit_minutes)
                            <span aria-hidden="true">·</span>
                            <span>{{ $lesson->quiz_time_limit_minutes }} min limit</span>
                        @endif
                        @if(($quiz['attemptsRemaining'] ?? null) !== null)
                            <span aria-hidden="true">·</span>
                            <span>{{ $quiz['attemptsRemaining'] }} {{ $quiz['attemptsRemaining'] === 1 ? 'attempt' : 'attempts' }} left</span>
                        @elseif($lesson->quiz_max_attempts)
                            <span aria-hidden="true">·</span>
                            <span>{{ $lesson->quiz_max_attempts }} attempts</span>
                        @endif
                    </div>

                    {{-- Status banners. role="status" so the result is announced
                         rather than left to the green tint and a ✓. --}}
                    @if($passed || $isDone)
                        <div role="status" class="rounded-xl bg-green-50 border border-green-200 text-green-800 px-5 py-4 mb-5 flex items-center gap-3">
                            <span aria-hidden="true" class="w-8 h-8 rounded-full bg-ok text-white flex items-center justify-center flex-none">✓</span>
                            <div>
                                <div class="font-bold">Lesson complete!</div>
                                <div class="text-sm text-green-700">Nice work. {{ $next ? 'Continue to the next lesson.' : 'You\'ve finished the course.' }}</div>
                            </div>
                        </div>
                    @elseif($timeup)
                        <div role="status" class="rounded-xl bg-red-50 border border-red-200 text-red-700 px-5 py-4 mb-5">
                            ⏱ <strong>Time's up.</strong> This attempt was not counted as successful.
                        </div>
                    @elseif($failed)
                        <div role="status" class="rounded-xl bg-amber-50 border border-amber-200 text-amber-800 px-5 py-4 mb-5">
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
                                    @php($multiple = $question->type === \App\Models\Question::TYPE_MULTIPLE)
                                    <fieldset class="border border-slate-200 rounded-xl p-5">
                                        <legend class="px-2 font-semibold text-navy">
                                            {{ $qn + 1 }}. {{ $question->prompt }}
                                            {{-- Correctness was carried by colour and a glyph alone. --}}
                                            @if($qResult === true)
                                                <span class="text-ok" aria-hidden="true">✓</span><span class="vh">Correct</span>
                                            @elseif($qResult === false)
                                                <span class="text-red-500" aria-hidden="true">✗</span><span class="vh">Incorrect</span>
                                            @endif
                                        </legend>
                                        @if($multiple)
                                            <p class="px-2 text-xs text-slate-400 mb-1">Select all that apply.</p>
                                        @endif
                                        <div class="space-y-2 mt-2">
                                            @foreach($question->options as $option)
                                                @php($oldAnswer = (array) old("answers.{$question->id}", []))
                                                <label class="flex items-center gap-3 px-3 py-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50 active:bg-slate-100">
                                                    @if($multiple)
                                                        <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option->id }}"
                                                               class="text-brand w-4 h-4 flex-none rounded"
                                                               {{ $mode === 'open' && in_array((string) $option->id, $oldAnswer, true) ? 'checked' : '' }}>
                                                    @else
                                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                                                               class="text-brand w-4 h-4 flex-none"
                                                               {{ $mode === 'open' && (int) old("answers.{$question->id}") === $option->id ? 'checked' : '' }} required>
                                                    @endif
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
                        @if($course->final_quiz_enabled && $certificate)
                            <a href="{{ route('certificates.index') }}"
                               class="inline-block rounded-lg bg-ok text-white px-5 py-2.5 font-semibold hover:bg-green-700">
                                View certificate ✓
                            </a>
                        @elseif($course->final_quiz_enabled && $finalUnlocked)
                            <a href="{{ route('academy.final.show', $course) }}"
                               class="inline-block rounded-lg bg-brand text-white px-5 py-2.5 font-semibold hover:bg-blue-700">
                                Take the final quiz &rarr;
                            </a>
                        @else
                            {{-- The course page, not the home page: it shows what
                                 they finished and what to take next. --}}
                            <a href="{{ route('academy.course', $course) }}"
                               class="inline-block rounded-lg bg-ok text-white px-5 py-2.5 font-semibold hover:bg-green-700">
                                Finish course ✓
                            </a>
                        @endif
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
                               @if($l->id === $lesson->id) aria-current="page" @endif
                               class="flex items-center gap-3 px-2 py-2 rounded-lg text-sm {{ $l->id === $lesson->id ? 'bg-blue-50 text-brand font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                                <span class="w-6 h-6 flex-none rounded-full flex items-center justify-center text-xs {{ $lDone ? 'bg-ok text-white' : 'bg-slate-100 text-slate-500' }}">
                                    <span aria-hidden="true">{{ $lDone ? '✓' : $i + 1 }}</span>
                                    @if($lDone)
                                        <span class="vh">Completed</span>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">{{ $l->title }}</span>
                                    @if($l->durationLabel())
                                        <span class="block text-xs text-slate-400">{{ $l->durationLabel() }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>

                {{-- Final quiz entry --}}
                @if($course->final_quiz_enabled)
                    @php($remaining = $lessons->whereNotIn('id', $completed)->count())
                    <div class="mt-2 pt-2 border-t border-slate-100">
                        @auth
                            @if($certificate)
                                <a href="{{ route('certificates.index') }}"
                                   class="flex items-center gap-3 px-2 py-2.5 rounded-lg text-sm text-ok font-semibold hover:bg-green-50">
                                    <span class="w-6 h-6 flex-none rounded-full bg-ok text-white flex items-center justify-center text-xs">🎓</span>
                                    <span class="truncate">Certificate ready</span>
                                </a>
                            @elseif($finalUnlocked)
                                <a href="{{ route('academy.final.show', $course) }}"
                                   class="flex items-center gap-3 px-2 py-2.5 rounded-lg text-sm text-brand font-semibold bg-blue-50 hover:bg-blue-100">
                                    <span class="w-6 h-6 flex-none rounded-full bg-brand text-white flex items-center justify-center text-xs">★</span>
                                    <span class="truncate">Final quiz — start</span>
                                </a>
                            @else
                                <div class="flex items-center gap-3 px-2 py-2.5 rounded-lg text-sm text-slate-400">
                                    <span class="w-6 h-6 flex-none rounded-full bg-slate-100 flex items-center justify-center text-xs">🔒</span>
                                    <span class="min-w-0">Final quiz · <span class="text-slate-500 font-medium">{{ $remaining }} lesson{{ $remaining === 1 ? '' : 's' }} left</span></span>
                                </div>
                            @endif
                        @else
                            <a href="{{ route('login') }}"
                               class="flex items-center gap-3 px-2 py-2.5 rounded-lg text-sm text-slate-500 hover:bg-slate-50">
                                <span class="w-6 h-6 flex-none rounded-full bg-slate-100 flex items-center justify-center text-xs">🔒</span>
                                <span class="truncate">Final quiz · log in to take it</span>
                            </a>
                        @endauth
                    </div>
                @endif
            </div>
        </aside>
    </div>
@endsection
