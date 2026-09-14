@extends('academy.layout')

@section('title', __t('academy.meta.home_title'))

@php
    $metaDescription = __t('academy.meta.home_description');
@endphp

@section('content')
    @auth
        <div class="mb-8">
            {{-- "Welcome back … pick up where you left off" is wrong on day one,
                 when there is nowhere to pick up from. --}}
            @if($next && $next['kind'] === 'start')
                <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.home.welcome', ['name' => auth()->user()->name]) }}</h1>
                <p class="text-slate-500">{{ __t('academy.home.welcome_start') }}</p>
            @else
                <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.home.welcome_back', ['name' => auth()->user()->name]) }}</h1>
                <p class="text-slate-500">{{ __t('academy.home.welcome_back_account') }}</p>
            @endif
        </div>
    @elseif(session('student_name'))
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.home.welcome_back', ['name' => session('student_name')]) }}</h1>
            <p class="text-slate-500">
                {{ __t('academy.home.welcome_back_guest') }}
                {{-- The whole sentence is the link: split around a link, it
                     cannot be put in the right word order in every language. --}}
                <a href="{{ route('register') }}" class="text-brand font-semibold">{{ __t('academy.home.create_account_to_save') }}</a>
            </p>
        </div>
    @else
        <div class="mb-8 rounded-2xl bg-gradient-to-br from-brand to-navy text-white p-7 sm:p-9">
            <h1 class="text-2xl sm:text-3xl font-extrabold mb-1">{{ __t('academy.home.hero_title') }}</h1>
            <p class="text-white mb-5">{{ __t('academy.home.hero_intro') }}</p>
            <form method="POST" action="{{ route('academy.name') }}" class="flex flex-col sm:flex-row gap-3 max-w-md">
                @csrf
                {{-- bg-white is load-bearing: Tailwind's preflight makes form
                     controls transparent, so without it the dark text sits
                     straight on the gradient and all but vanishes at the navy
                     end. text-slate-800 must stay too — the card sets
                     text-white, so an unstyled input would be white on white.
                     Both classes are already in the committed CSS bundle, so
                     this reads correctly before CI rebuilds it. --}}
                <input type="text" name="name" required placeholder="{{ __t('academy.home.your_name') }}" aria-label="{{ __t('academy.home.your_name') }}"
                       class="flex-1 rounded-lg bg-white px-4 py-2.5 text-slate-800 focus:ring-2 focus:ring-white">
                <button class="rounded-lg bg-white text-navy font-semibold px-5 py-2.5 hover:bg-slate-100 focus:ring-2 focus:ring-white">
                    {{ __t('academy.home.start_learning') }}
                </button>
            </form>
            {{-- Full white, not white/70: over the brand blue that was 3.2:1.
                 The hierarchy comes from the smaller size instead. --}}
            <p class="text-white text-sm mt-3">
                {{ __t('academy.home.have_account') }} <a href="{{ route('login') }}" class="underline font-semibold">{{ __t('academy.signup.log_in') }}</a>
                · <a href="{{ route('register') }}" class="underline font-semibold">{{ __t('academy.signup.register') }}</a>
            </p>
        </div>
    @endauth

    {{-- Scrolling the whole page was the only way to find a lesson. --}}
    <div class="mb-8">
        @include('academy.partials.search-form', ['inputId' => 'home-search'])
    </div>

    {{-- The next step: one card, one action. Which card depends on where the
         student is — see AcademyController::nextStep(). --}}
    @if($next)
        @php
            $nextPct = $next['total'] > 0 ? round($next['done'] / $next['total'] * 100) : 0;

            if ($next['kind'] === 'final_quiz') {
                $nextUrl = route('academy.final.show', $next['course']);
                $nextEyebrow = __t('academy.home.next_final');
                $nextTitle = $next['course']->title;
                $nextDetail = __tc('academy.home.next_final_detail', $next['total'], ['percent' => $next['course']->pass_percent])
                    .($next['attemptsLeft'] !== null ? ' · '.__tc('academy.common.attempts_left', $next['attemptsLeft']) : '');
                $nextButton = __t('academy.home.take_final');
            } elseif ($next['kind'] === 'start') {
                $nextUrl = route('academy.lesson', [$next['course'], $next['lesson']]);
                $nextEyebrow = __t('academy.home.next_start');
                $nextTitle = $next['lesson']->title;
                $nextDetail = $next['course']->title;
                $nextButton = __t('academy.home.begin');
            } else {
                $nextUrl = route('academy.lesson', [$next['course'], $next['lesson']]);
                $nextEyebrow = __t('academy.home.next_continue');
                $nextTitle = $next['lesson']->title;
                $nextDetail = $next['course']->title;
                $nextButton = __t('academy.home.resume');
            }
        @endphp
        <a href="{{ $nextUrl }}"
           class="group block mb-6 rounded-2xl border border-brand/30 bg-blue-50/60 p-5 sm:p-6 hover:border-brand hover:shadow-md transition">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand">{{ $nextEyebrow }}</span>
                    <h2 class="mt-1 text-lg sm:text-xl font-extrabold text-navy group-hover:text-brand transition">
                        {{ $nextTitle }}
                    </h2>
                    <p class="text-sm text-slate-500">{{ $nextDetail }}</p>
                </div>

                <div class="shrink-0 sm:text-right">
                    @if($next['kind'] === 'lesson')
                        <div class="text-sm text-slate-500 mb-1">{{ __t('academy.common.lessons_done', ['done' => $next['done'], 'total' => $next['total']]) }}</div>
                        <div class="w-full sm:w-44 h-2 rounded-full bg-white overflow-hidden"
                             role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $nextPct }}"
                             aria-label="{{ __t('academy.common.progress_aria', ['done' => $next['done'], 'total' => $next['total']]) }}">
                            <div class="h-full bg-ok" style="width: {{ $nextPct }}%"></div>
                        </div>
                    @endif
                    <span class="mt-3 inline-flex items-center min-h-11 rounded-lg bg-brand px-4 text-sm font-semibold text-white">
                        {{ $nextButton }}
                    </span>
                </div>
            </div>
        </a>
    @endif

    {{-- A signed-in student's own numbers. One column on a phone, three from
         sm: up. The last final quiz result lives here because it otherwise
         showed once, straight after submitting, and was never seen again. --}}
    @if($progress)
        <section aria-labelledby="my-progress" class="mb-8 bg-white rounded-2xl border border-slate-200 shadow-sm">
            <h2 id="my-progress" class="px-5 pt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __t('academy.home.your_progress') }}</h2>

            <dl class="divide-y divide-slate-100 sm:divide-y-0 sm:grid sm:grid-cols-3">
                <div class="flex items-baseline justify-between gap-3 px-5 py-3 sm:block">
                    <dt class="text-sm text-slate-500">{{ __t('academy.home.in_progress') }}</dt>
                    <dd class="text-2xl font-extrabold text-navy">{{ $progress['inProgress'] }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3 px-5 py-3 sm:block">
                    <dt class="text-sm text-slate-500">{{ __t('academy.common.completed') }}</dt>
                    <dd class="text-2xl font-extrabold text-navy">{{ $progress['finished'] }}</dd>
                </div>
                <div class="flex items-baseline justify-between gap-3 px-5 py-3 sm:block">
                    <dt class="text-sm text-slate-500">{{ __t('academy.home.certificates') }}</dt>
                    <dd class="text-2xl font-extrabold text-navy">
                        @if($progress['certificates'] > 0)
                            <a href="{{ route('certificates.index') }}" class="hover:text-brand">{{ $progress['certificates'] }}</a>
                        @else
                            0
                        @endif
                    </dd>
                </div>
            </dl>

            @if($progress['lastFinal'])
                @php
                    $last = $progress['lastFinal'];
                @endphp
                <a href="{{ route('academy.final.show', $last['course']) }}"
                   class="flex items-center gap-3 border-t border-slate-100 px-5 py-3 min-h-11 hover:bg-slate-50 active:bg-slate-100 rounded-b-2xl">
                    <span class="min-w-0 flex-1 text-sm">
                        <span class="block text-slate-500">{{ __t('academy.home.last_final', ['course' => $last['course']->title]) }}</span>
                        <span class="block font-semibold {{ $last['passed'] ? 'text-green-700' : 'text-amber-700' }}">
                            {{ $last['percent'] }}% · {{ $last['passed'] ? __t('academy.home.passed') : __t('academy.home.not_passed_yet') }}
                            @if(! $last['passed'] && $last['attemptsLeft'] !== null)
                                <span class="font-normal text-slate-500">
                                    · {{ $last['attemptsLeft'] === 0 ? __t('academy.home.no_attempts_contact') : __tc('academy.common.attempts_left', $last['attemptsLeft']) }}
                                </span>
                            @endif
                        </span>
                    </span>
                    <span aria-hidden="true" class="text-slate-400 flex-none">&rarr;</span>
                </a>
            @endif
        </section>
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
                            <span class="text-xs font-semibold uppercase tracking-wide text-brand">{{ __t('academy.common.course') }}</span>
                            @if($course->audience_label)
                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full bg-blue-50 text-brand">{{ __t('academy.common.for_audience', ['audience' => __t('academy.common.audience.'.$course->audience)]) }}</span>
                            @endif
                        </div>
                        <h2 class="text-xl font-extrabold text-navy">{{ $course->title }}</h2>
                        <p class="text-slate-500 mt-1 max-w-2xl">{{ $course->description }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                            <span>{{ __tc('academy.common.lessons', $total) }}</span>
                            @if($course->durationLabel())
                                <span aria-hidden="true">·</span>
                                <span>{{ $course->durationLabel() }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-slate-500 mb-1">{{ __t('academy.common.lessons_done', ['done' => $done, 'total' => $total]) }}</div>
                        <div class="w-44 h-2 rounded-full bg-slate-100 overflow-hidden"
                             role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}"
                             aria-label="{{ __t('academy.common.course_progress_aria', ['course' => $course->title, 'done' => $done, 'total' => $total]) }}">
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
                                <span class="absolute top-3 right-3 w-7 h-7 rounded-full bg-ok text-white flex items-center justify-center text-sm shadow">
                                    <span aria-hidden="true">✓</span>
                                    <span class="vh">{{ __t('academy.common.completed') }}</span>
                                </span>
                            @endif
                        </div>
                        <div class="p-5">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-slate-100 text-slate-500">{{ $course->level ? __t('academy.common.level.'.$course->level) : '' }}</span>
                                @if(! empty($lesson->videoEntries()))
                                    <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-blue-50 text-brand">{{ __t('academy.common.video') }}</span>
                                @endif
                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-violet-50 text-violet-600">{{ __t('academy.common.quiz') }}</span>
                            </div>
                            <h3 class="font-bold text-navy group-hover:text-brand transition">{{ $lesson->title }}</h3>
                            <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $lesson->summary }}</p>
                            @if($lesson->durationLabel())
                                <p class="text-xs text-slate-400 mt-2">{{ $lesson->durationLabel() }}</p>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="text-slate-500">{{ __t('academy.home.no_courses') }}</p>
    @endforelse
@endsection
