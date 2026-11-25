@extends('academy.layout')

@section('title', __t('academy.meta.home_title'))

@php
    $metaDescription = __t('academy.meta.home_description');
    // Visitors see one course to start with and the next few; the rest are on
    // the Courses page. Students see their courses grouped by where they are.
    $featured = $courses->first();
    $visitorGrid = $courses->slice(1, 6);
    $started = $courses->filter(fn ($c) => $states[$c->id] === 'started');
    $notStarted = $courses->filter(fn ($c) => $states[$c->id] === 'new');
    $finishedCourses = $courses->filter(fn ($c) => $states[$c->id] === 'finished');
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
        <div class="mb-8 rounded-2xl bg-gradient-to-br from-brand to-navy text-white p-7 sm:p-9 flex items-center gap-6">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl sm:text-3xl font-extrabold mb-1">{{ __t('academy.home.hero_title') }}</h1>
                <p class="text-white mb-3">{{ __t('academy.home.hero_intro') }}</p>
                @php
                    $heroLessons = $courses->flatMap(fn ($c) => $c->publishedLessons->pluck('id'))->unique()->count();
                @endphp
                {{-- What is on offer, at a glance, before anyone types a name. --}}
                <p class="hero-stats mb-5">
                    <span>{{ __tc('academy.home.hero_courses', $courses->count()) }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ __tc('academy.common.lessons', $heroLessons) }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ __t('academy.home.hero_certificates') }}</span>
                </p>
                <form method="POST" action="{{ route('academy.name') }}" class="flex flex-col sm:flex-row gap-3 max-w-xl">
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
                    <button class="rounded-lg bg-white text-navy font-semibold px-5 py-2.5 whitespace-nowrap hover:bg-slate-100 focus:ring-2 focus:ring-white">
                        {{ __t('academy.home.start_learning') }}
                    </button>
                    <a href="{{ route('academy.courses') }}" class="hero-browse">{{ __t('academy.home.browse_courses') }}</a>
                </form>
                {{-- Full white, not white/70: over the brand blue that was 3.2:1.
                     The hierarchy comes from the smaller size instead. --}}
                <p class="text-white text-sm mt-3">
                    {{ __t('academy.home.have_account') }} <a href="{{ route('login') }}" class="underline font-semibold">{{ __t('academy.signup.log_in') }}</a>
                    · <a href="{{ route('register') }}" class="underline font-semibold">{{ __t('academy.signup.register') }}</a>
                </p>
            </div>
            {{-- A glimpse of three courses, from 1024px up. Decoration: the
                 same courses are listed below. --}}
            <div class="hero-covers" aria-hidden="true">
                @foreach($visitorGrid->take(3) as $peek)
                    @include('academy.partials.course-cover', ['course' => $peek, 'chip' => $peek->translated('title'), 'shift' => $loop->index])
                @endforeach
            </div>
        </div>
    @endauth

    {{-- Scrolling the whole page was the only way to find a lesson. --}}
    <div class="mb-8">
        @include('academy.partials.search-form', ['inputId' => 'home-search'])
        @guest
            {{-- Who a course is for, straight into the filtered Courses page. --}}
            @php
                $audiences = $courses->pluck('audience')->filter()->unique()->values();
            @endphp
            @if($audiences->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('academy.courses') }}" class="inline-flex items-center min-h-11 rounded-full bg-navy px-4 text-sm font-semibold text-white">{{ __t('academy.common.all_courses') }}</a>
                    @foreach($audiences as $audience)
                        <a href="{{ route('academy.courses', ['audience' => $audience]) }}" class="inline-flex items-center min-h-11 rounded-full border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:border-brand hover:text-brand">
                            {{ __t('academy.common.for_audience', ['audience' => __t('academy.common.audience.'.$audience)]) }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endguest
    </div>

        {{-- The next step: one card, one action. Which card depends on where the
             student is — see AcademyController::nextStep(). A visitor with
             progress in this browser gets it too, never the final quiz. --}}
        @if($next)
            @php
                $nextPct = $next['total'] > 0 ? round($next['done'] / $next['total'] * 100) : 0;
                $nextCourse = $next['course'];

                if ($next['kind'] === 'final_quiz') {
                    $nextUrl = route('academy.final.show', $nextCourse);
                    $nextEyebrow = __t('academy.home.next_final');
                    $nextTitle = $nextCourse->translated('title');
                    $nextDetail = __tc('academy.home.next_final_detail', $next['total'], ['percent' => $nextCourse->pass_percent])
                        .($next['attemptsLeft'] !== null ? ' · '.__tc('academy.common.attempts_left', $next['attemptsLeft']) : '');
                    $nextButton = __t('academy.home.take_final');
                } else {
                    $nextUrl = route('academy.lesson', [$nextCourse, $next['lesson']]);
                    $nextEyebrow = $next['kind'] === 'start' ? __t('academy.home.next_start') : __t('academy.home.next_continue');
                    $nextTitle = $next['lesson']->translated('title');
                    $nextDetail = $nextCourse->translated('title');
                    $nextButton = $next['kind'] === 'start' ? __t('academy.home.begin') : __t('academy.home.resume');
                    $nextNumber = $nextCourse->publishedLessons->search(fn ($l) => $l->id === $next['lesson']->id) + 1;
                }
            @endphp
            <section class="home-section">
                <span class="home-eyebrow">{{ $nextEyebrow }}</span>
                <a href="{{ $nextUrl }}" class="course-feature course-feature--next group">
                    @if($next['kind'] === 'final_quiz')
                        @include('academy.partials.course-cover', ['course' => $nextCourse])
                    @else
                        @include('academy.partials.lesson-cover', ['lesson' => $next['lesson'], 'course' => $nextCourse, 'number' => $nextNumber])
                    @endif
                    <div class="course-feature__body">
                        <span class="text-sm text-slate-500">{{ $nextDetail }}</span>
                        <h2 class="course-feature__title group-hover:text-brand transition">{{ $nextTitle }}</h2>
                        @if($next['kind'] === 'lesson')
                            <div class="flex items-center gap-3">
                                <div class="w-44 h-2 rounded-full bg-slate-100 overflow-hidden"
                                     role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $nextPct }}"
                                     aria-label="{{ __t('academy.common.progress_aria', ['done' => $next['done'], 'total' => $next['total']]) }}">
                                    <div class="h-full bg-brand" style="width: {{ $nextPct }}%"></div>
                                </div>
                                <span class="text-sm text-slate-500">{{ __t('academy.common.lessons_done', ['done' => $next['done'], 'total' => $next['total']]) }}</span>
                            </div>
                        @endif
                        <span class="course-feature__cta">{{ $nextButton }} <span aria-hidden="true" class="inline-block rtl:rotate-180">&rarr;</span></span>
                    </div>
                </a>
            </section>
        @endif

    @auth
        {{-- A signed-in student's own numbers. One column on a phone, three from
             sm: up. The last final quiz result lives here because it otherwise
             showed once, straight after submitting, and was never seen again. --}}
        @if($progress)
            <section aria-labelledby="my-progress" class="mb-10 bg-white rounded-2xl border border-slate-200 shadow-sm">
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
                            <span class="block text-slate-500">{{ __t('academy.home.last_final', ['course' => $last['course']->translated('title')]) }}</span>
                            <span class="block font-semibold {{ $last['passed'] ? 'text-green-700' : 'text-amber-700' }}">
                                {{ $last['percent'] }}% · {{ $last['passed'] ? __t('academy.home.passed') : __t('academy.home.not_passed_yet') }}
                                @if(! $last['passed'] && $last['attemptsLeft'] !== null)
                                    <span class="font-normal text-slate-500">
                                        · {{ $last['attemptsLeft'] === 0 ? __t('academy.home.no_attempts_contact') : __tc('academy.common.attempts_left', $last['attemptsLeft']) }}
                                    </span>
                                @endif
                            </span>
                        </span>
                        <span aria-hidden="true" class="text-slate-400 flex-none"><span class="inline-block rtl:rotate-180">&rarr;</span></span>
                    </a>
                @endif
            </section>
        @endif

        {{-- Courses the student has started: three lesson cards each, from
             their first unfinished lesson (the last three once all are done),
             so the page stays short however many lessons a course has. Courses
             not started are compact tiles below; finished ones a short list. --}}
        @if($started->isNotEmpty())
            <section class="home-section" aria-labelledby="your-courses">
                <div class="home-heading"><h2 id="your-courses">{{ __t('academy.home.your_courses') }}</h2></div>
                @foreach($started as $course)
                    @php
                        $lessons = $course->publishedLessons->values();
                        $total = $lessons->count();
                        $done = $lessons->whereIn('id', $completed)->count();
                        $pct = $total > 0 ? round($done / $total * 100) : 0;
                        $firstOpen = $lessons->search(fn ($l) => ! in_array($l->id, $completed, true));
                        $start = $firstOpen === false ? max(0, $total - 3) : min($firstOpen, max(0, $total - 3));
                        $shown = $lessons->slice($start, 3);
                        // Every lesson done but the final quiz still ahead: the card above points there.
                        [$ctaLabel, $ctaUrl] = $firstOpen === false
                            ? [__t('academy.home.review_course'), route('academy.course', $course)]
                            : [__t('academy.home.continue_course'), route('academy.lesson', [$course, $lessons[$firstOpen]])];
                    @endphp
                    <div class="mb-8">
                        <div class="course-progress">
                            @include('academy.partials.course-cover', ['course' => $course])
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-navy"><a href="{{ route('academy.course', $course) }}" class="hover:text-brand">{{ $course->translated('title') }}</a></h3>
                                <div class="mt-1 flex items-center gap-3">
                                    <div class="course-progress__bar h-2 rounded-full bg-slate-100 overflow-hidden"
                                         role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}"
                                         aria-label="{{ __t('academy.common.course_progress_aria', ['course' => $course->translated('title'), 'done' => $done, 'total' => $total]) }}">
                                        <div class="h-full bg-ok" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-sm text-slate-500 whitespace-nowrap">{{ __t('academy.common.lessons_done', ['done' => $done, 'total' => $total]) }}</span>
                                </div>
                            </div>
                            <a href="{{ $ctaUrl }}" class="course-continue inline-flex items-center min-h-11 font-semibold text-brand hover:underline">{{ $ctaLabel }} <span aria-hidden="true" class="ms-1 inline-block rtl:rotate-180">&rarr;</span></a>
                        </div>
                        <div class="lesson-row">
                            @foreach($shown as $i => $lesson)
                                @php
                                    $isDone = in_array($lesson->id, $completed, true);
                                @endphp
                                <a href="{{ route('academy.lesson', [$course, $lesson]) }}"
                                   class="group bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md hover:-translate-y-0.5 transition">
                                    @include('academy.partials.lesson-cover', ['lesson' => $lesson, 'course' => $course, 'number' => $i + 1, 'isDone' => $isDone])
                                    <div class="p-5">
                                        <div class="flex items-center gap-2 mb-2">
                                            @if($course->level)
                                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-slate-100 text-slate-500">{{ __t('academy.common.level.'.$course->level) }}</span>
                                            @endif
                                            @if(! empty($lesson->videoEntries()))
                                                <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-blue-50 text-brand">{{ __t('academy.common.video') }}</span>
                                            @endif
                                            <span class="text-[11px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded bg-violet-50 text-violet-600">{{ __t('academy.common.quiz') }}</span>
                                        </div>
                                        <h3 class="font-bold text-navy group-hover:text-brand transition">{{ $lesson->translated('title') }}</h3>
                                        <p class="text-sm text-slate-500 mt-1 line-clamp-2">{{ $lesson->translated('summary') }}</p>
                                        @if($lesson->durationLabel())
                                            <p class="text-xs text-slate-400 mt-2">{{ $lesson->durationLabel() }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                        @if($total > 3)
                            <a href="{{ route('academy.course', $course) }}" class="mt-3 inline-flex items-center min-h-11 font-semibold text-brand hover:underline">
                                {{ __t('academy.home.see_all_lessons', ['count' => $total]) }} <span aria-hidden="true" class="ms-1 inline-block rtl:rotate-180">&rarr;</span>
                            </a>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif

        @if($notStarted->isNotEmpty())
            <section class="home-section" aria-labelledby="explore-courses">
                <div class="home-heading"><h2 id="explore-courses">{{ __t('academy.home.explore_courses') }}</h2></div>
                <div class="home-grid">
                    @foreach($notStarted as $course)
                        @include('academy.partials.course-tile', ['course' => $course, 'state' => 'new'])
                    @endforeach
                </div>
            </section>
        @endif

        @if($finishedCourses->isNotEmpty())
            <section class="home-section" aria-labelledby="finished-courses">
                <div class="home-heading"><h2 id="finished-courses">{{ __t('academy.common.completed') }}</h2></div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($finishedCourses as $course)
                        <div class="course-done">
                            @include('academy.partials.course-cover', ['course' => $course])
                            <div class="min-w-0 flex-1">
                                <h3 class="font-bold text-navy">{{ $course->translated('title') }}</h3>
                                <div class="flex flex-wrap gap-x-3">
                                    <a href="{{ route('academy.course', $course) }}" class="inline-flex items-center min-h-11 text-sm font-semibold text-brand hover:underline">{{ __t('academy.home.review_course') }}</a>
                                    @if(in_array($course->id, $certified, true))
                                        <a href="{{ route('certificates.index') }}" class="inline-flex items-center min-h-11 text-sm font-semibold text-brand hover:underline">{{ __t('academy.common.my_certificates') }}</a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    @else
        {{-- Visitors: one course to begin with, then the next six. The whole
             catalogue, with its filters, is the Courses page. --}}
        @if($featured && ! $next)
            <section class="home-section">
                <span class="home-eyebrow">{{ __t('academy.home.next_start') }}</span>
                <article class="course-feature">
                    @include('academy.partials.course-cover', ['course' => $featured, 'chip' => __tc('academy.common.lessons', $featured->published_lessons_count)])
                    <div class="course-feature__body">
                        @if($featured->audience)
                            <span class="course-tile__tag" style="display: block">{{ __t('academy.common.for_audience', ['audience' => __t('academy.common.audience.'.$featured->audience)]) }}</span>
                        @endif
                        <h2 class="course-feature__title"><a href="{{ route('academy.course', $featured) }}" class="hover:text-brand">{{ $featured->translated('title') }}</a></h2>
                        <p class="text-slate-500 line-clamp-3">{{ $featured->translated('description') }}</p>
                        <a href="{{ route('academy.course', $featured) }}" class="course-feature__cta">{{ __t('academy.home.start_course') }} <span aria-hidden="true" class="inline-block rtl:rotate-180">&rarr;</span></a>
                    </div>
                </article>
            </section>
        @endif

        @if($visitorGrid->isNotEmpty())
            <section class="home-section" aria-labelledby="home-courses">
                <div class="home-heading">
                    <h2 id="home-courses">{{ __t('nav.courses') }}</h2>
                    @if($courses->count() > $visitorGrid->count() + 1)
                        <a href="{{ route('academy.courses') }}" class="inline-flex items-center min-h-11 text-sm font-semibold text-brand hover:underline">{{ __t('academy.home.see_all_courses', ['count' => $courses->count()]) }} <span aria-hidden="true" class="ms-1 inline-block rtl:rotate-180">&rarr;</span></a>
                    @endif
                </div>
                <div class="home-grid">
                    @foreach($visitorGrid as $course)
                        @include('academy.partials.course-tile', ['course' => $course, 'state' => $states[$course->id]])
                    @endforeach
                </div>
            </section>
        @endif
    @endauth

    @if($courses->isEmpty())
        <p class="mb-10 text-slate-500">{{ __t('academy.home.no_courses') }}</p>
    @endif

    {{-- The other ways in, for everyone: they replace the single Case Studies banner. --}}
    <section class="home-section" aria-labelledby="more-ways">
        <div class="home-heading"><h2 id="more-ways">{{ __t('academy.home.more_ways') }}</h2></div>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach([
                ['case_studies', route('academy.case-studies.index'), __t('academy.case_studies.title'), __t('academy.case_studies.home_intro')],
                ['tutorials', route('academy.tutorials'), __t('nav.tutorials'), __t('academy.tutorials.intro')],
                ['webinars', route('academy.webinars'), __t('nav.webinars'), __t('academy.webinars.intro')],
            ] as [$icon, $url, $label, $intro])
                <a href="{{ $url }}" class="more-tile">
                    <span class="more-tile__icon">@include('academy.partials.nav-icon', ['name' => $icon])</span>
                    <span class="min-w-0">
                        <span class="block font-bold text-navy">{{ $label }}</span>
                        <span class="block mt-1 text-sm text-slate-500 line-clamp-2">{{ $intro }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endsection
