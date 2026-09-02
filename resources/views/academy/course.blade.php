@extends('academy.layout')

@section('title', $course->title . ' — Pilot Academy')

@php
    $metaDescription = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($course->description))), 155)
        ?: $course->title.' on Pilot Academy: short lessons with videos and quizzes on the Pilot vehicle monitoring platform.';
@endphp

@section('content')
    @php
        $total = $course->publishedLessons->count();
        $done = $course->publishedLessons->whereIn('id', $completed)->count();
        $pct = $total > 0 ? round($done / $total * 100) : 0;

        // Duration is optional content: everything below is skipped when an
        // admin has not filled it in, rather than showing "0 min".
        $courseDuration = $course->durationLabel();
        $minutesLeft = (int) $course->publishedLessons->whereNotIn('id', $completed)->sum('duration_minutes');
        $leftLabel = \App\Models\Course::formatMinutes($minutesLeft);
    @endphp

    <a href="{{ route('academy.home') }}" class="text-sm text-brand font-semibold">&larr; All courses</a>

    @if(! $course->isPublished())
        {{-- Only admins ever reach this page for an unpublished course. --}}
        <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>{{ $course->statusLabel() }}</strong> — students cannot see this course. You are previewing it as an admin.
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 my-5">
        <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-semibold uppercase tracking-wide text-brand">Course</span>
            @if($course->audience_label)
                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-blue-50 text-brand">For {{ $course->audience_label }}</span>
            @endif
        </div>
        <h1 class="text-2xl font-extrabold text-navy">{{ $course->title }}</h1>
        <p class="text-slate-500 mt-1 max-w-2xl">{{ $course->description }}</p>

        {{-- What the course costs in time, before anyone commits to it. --}}
        <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <span>{{ $total }} {{ $total === 1 ? 'lesson' : 'lessons' }}</span>
            @if($courseDuration)
                <span aria-hidden="true">·</span>
                <span>{{ $courseDuration }}</span>
            @endif
            @if($course->final_quiz_enabled)
                <span aria-hidden="true">·</span>
                <span>Final quiz</span>
            @endif
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="w-44 h-2 rounded-full bg-slate-100 overflow-hidden"
                 role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}"
                 aria-label="Course progress: {{ $done }} of {{ $total }} lessons complete">
                <div class="h-full bg-ok" style="width: {{ $pct }}%"></div>
            </div>
            {{-- Keep whitespace before @if: Blade does not compile a directive
                 glued to the preceding word, and the stray @endif then breaks
                 the template. --}}
            <span class="text-sm text-slate-500">
                {{ $done }} / {{ $total }} lessons
                @if($leftLabel && $done < $total)
                    · {{ $leftLabel }} left
                @endif
            </span>
        </div>
    </div>

    {{-- Finishing a course used to end on a button back to the home page. When
         there is a final quiz still to take, that card below is the right next
         step and this would only duplicate it. --}}
    @if($total > 0 && $done === $total && (! $course->final_quiz_enabled || $certificate))
        <div class="bg-white rounded-2xl border border-green-200 shadow-sm p-6 mb-5">
            <div class="flex items-start gap-4">
                <span aria-hidden="true" class="w-11 h-11 flex-none rounded-xl bg-ok text-white flex items-center justify-center text-xl">✓</span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-xl font-extrabold text-navy">Course complete</h2>
                    <p class="text-slate-500 text-sm mt-1">
                        You finished all {{ $total }} {{ $total === 1 ? 'lesson' : 'lessons' }} in {{ $course->title }}.
                        @if($courseDuration)
                            That is {{ $courseDuration }} of training done.
                        @endif
                    </p>

                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                        @if($certificate)
                            <a href="{{ route('certificates.download', $certificate) }}"
                               class="inline-flex justify-center rounded-lg bg-ok text-white font-semibold px-6 py-3 hover:bg-green-700">
                                Download certificate
                            </a>
                        @endif
                        @if($nextCourse)
                            <a href="{{ route('academy.course', $nextCourse) }}"
                               class="inline-flex justify-center rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                                Next: {{ $nextCourse->title }} &rarr;
                            </a>
                        @else
                            <a href="{{ route('academy.home') }}"
                               class="inline-flex justify-center rounded-lg border border-slate-300 text-slate-600 font-semibold px-6 py-3 hover:bg-slate-50">
                                Back to all courses
                            </a>
                        @endif
                    </div>

                    {{-- Asked once they have finished, and seen only by staff.
                         Not public stars: this is assigned training, not a
                         marketplace where strangers pick between sellers. --}}
                    @auth
                        <div class="mt-5 pt-5 border-t border-slate-100">
                            @if(session('feedback_saved'))
                                <p role="status" class="text-sm font-semibold text-ok">Thanks — that helps us fix the course.</p>
                            @endif

                            @if($feedback)
                                <p class="text-sm text-slate-500">
                                    You said this course was
                                    <strong class="text-navy">{{ $feedback->is_positive ? 'useful' : 'not useful' }}</strong>.
                                </p>
                            @else
                                <p class="text-sm font-semibold text-navy mb-1">Was this course useful?</p>
                                <p class="text-sm text-slate-500 mb-3">Only the training team sees this — other students never do.</p>
                            @endif

                            <form method="POST" action="{{ route('academy.course.feedback', $course) }}" class="mt-2">
                                @csrf
                                <label for="feedback-comment" class="vh">Anything you would change?</label>
                                <textarea id="feedback-comment" name="comment" rows="2" maxlength="1000"
                                          placeholder="Anything you would change? (optional)"
                                          class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm">{{ $feedback->comment ?? '' }}</textarea>

                                <div class="mt-3 flex flex-wrap gap-3">
                                    <button type="submit" name="is_positive" value="1"
                                            class="inline-flex items-center gap-2 h-11 px-5 rounded-lg font-semibold {{ $feedback && $feedback->is_positive ? 'bg-ok text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                                        <span aria-hidden="true">👍</span> Useful
                                    </button>
                                    <button type="submit" name="is_positive" value="0"
                                            class="inline-flex items-center gap-2 h-11 px-5 rounded-lg font-semibold {{ $feedback && ! $feedback->is_positive ? 'bg-navy text-white' : 'border border-slate-300 text-slate-600 hover:bg-slate-50' }}">
                                        <span aria-hidden="true">👎</span> Not useful
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    @endif

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
                        <span class="absolute top-3 right-3 w-7 h-7 rounded-full bg-ok text-white flex items-center justify-center text-sm shadow">
                            <span aria-hidden="true">✓</span>
                            <span class="vh">Completed</span>
                        </span>
                    @endif
                </div>
                <div class="p-5">
                    <h3 class="font-bold text-navy group-hover:text-brand transition">{{ $lesson->title }}</h3>
                    <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $lesson->summary }}</p>
                    @if($lesson->durationLabel())
                        <p class="text-xs text-slate-400 mt-2">{{ $lesson->durationLabel() }}</p>
                    @endif
                </div>
            </a>
        @endforeach
    </div>

    {{-- Final quiz --}}
    @if($course->final_quiz_enabled)
        <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-start gap-4">
                <span class="w-11 h-11 flex-none rounded-xl bg-gradient-to-br from-brand to-navy text-white flex items-center justify-center text-xl">🎓</span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-xl font-extrabold text-navy">Final quiz &amp; certificate</h2>

                    @auth
                        @if($certificate)
                            <p class="text-slate-500 text-sm mt-1">You passed with {{ $certificate->score_percent }}%. Your certificate is ready.</p>
                            <div class="mt-4 flex flex-col sm:flex-row gap-3">
                                <a href="{{ route('certificates.download', $certificate) }}"
                                   class="inline-flex justify-center rounded-lg bg-ok text-white font-semibold px-6 py-3 hover:bg-green-700">
                                    Download certificate
                                </a>
                                <a href="{{ route('certificates.index') }}"
                                   class="inline-flex justify-center rounded-lg border border-slate-300 text-slate-600 font-semibold px-6 py-3 hover:bg-slate-50">
                                    My certificates
                                </a>
                            </div>
                        @elseif($finalUnlocked)
                            <p class="text-slate-500 text-sm mt-1">You've completed every lesson. Pass the final quiz to earn your certificate.</p>
                            <a href="{{ route('academy.final.show', $course) }}"
                               class="mt-4 inline-flex w-full sm:w-auto justify-center rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                                Go to final quiz &rarr;
                            </a>
                        @else
                            <p class="text-slate-500 text-sm mt-1">Complete all lessons to unlock the final quiz.</p>
                            <button type="button" disabled
                                    class="mt-4 inline-flex w-full sm:w-auto justify-center rounded-lg bg-slate-200 text-slate-400 font-semibold px-6 py-3 cursor-not-allowed">
                                🔒 Locked
                            </button>
                        @endif
                    @else
                        <p class="text-slate-500 text-sm mt-1">Log in to take the final quiz and earn a certificate for this course.</p>
                        <a href="{{ route('login') }}"
                           class="mt-4 inline-flex w-full sm:w-auto justify-center rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                            Log in to continue
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    @endif
@endsection
