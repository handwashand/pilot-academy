@extends('academy.layout')

@section('title', __t('academy.lessons.title').' - Pilot Academy')

@php
    $metaDescription = __t('academy.lessons.meta');
@endphp

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.lessons.title') }}</h1>
        <p class="mt-1 max-w-3xl text-slate-500">{{ __t('academy.lessons.intro') }}</p>
    </div>

    <form method="GET" action="{{ route('academy.lessons') }}" role="search"
          class="mb-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
            <div>
                <label for="q" class="block text-sm font-semibold text-navy">{{ __t('academy.lessons.search') }}</label>
                <input id="q" name="q" type="search" value="{{ $term }}"
                       placeholder="{{ __t('academy.lessons.search_hint') }}"
                       class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-slate-800 focus:border-brand">
            </div>
            @if($activeTag)
                <input type="hidden" name="tag" value="{{ $activeTag }}">
            @endif
            <button class="min-h-11 rounded-lg bg-brand px-5 font-semibold text-white hover:bg-blue-700">{{ __t('academy.lessons.search_btn') }}</button>
        </div>
        @if($term || $activeTag)
            <div class="mt-3">
                <a href="{{ route('academy.lessons') }}" class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand">
                    {{ __t('academy.lessons.clear') }}
                </a>
            </div>
        @endif
    </form>

    @if(! empty($tags))
        <div class="mb-6 flex flex-wrap gap-2" role="list" aria-label="{{ __t('academy.lessons.tags_aria') }}">
            @foreach($tags as $tag)
                <a href="{{ route('academy.lessons', ['tag' => $tag] + ($term ? ['q' => $term] : [])) }}" role="listitem"
                   class="inline-flex min-h-11 items-center rounded-full border px-4 text-sm font-semibold transition {{ $activeTag === $tag ? 'border-brand bg-blue-50 text-brand' : 'border-slate-200 bg-white text-slate-600 hover:border-brand hover:text-brand' }}">
                    {{ ucfirst($tag) }}
                </a>
            @endforeach
        </div>
    @endif

    <p class="mb-4 text-sm text-slate-500" role="status">
        {{ __tc('academy.lessons.count', $lessons->count()) }}
    </p>

    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($lessons as $lesson)
            @php
                $isDone = in_array($lesson->id, $completed, true);
                $hasQuiz = $lesson->questions_count > 0;
            @endphp

            <a href="{{ route('academy.standalone.lesson', $lesson) }}"
               class="group flex flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                @if(! empty($lesson->tags))
                    <div class="mb-2 flex flex-wrap gap-1">
                        @foreach($lesson->tags as $t)
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-slate-600">{{ $t }}</span>
                        @endforeach
                    </div>
                @endif

                <h2 class="text-lg font-extrabold text-navy transition group-hover:text-brand">{{ $lesson->translated('title') }}</h2>

                @if($lesson->translated('summary'))
                    <p class="mt-1 line-clamp-3 text-sm text-slate-500">{{ $lesson->translated('summary') }}</p>
                @endif

                <div class="mt-auto pt-3 flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    @if($lesson->durationLabel())
                        <span>{{ $lesson->durationLabel() }}</span>
                    @endif
                    @if($hasQuiz)
                        <span>{{ __tc('academy.lessons.questions', $lesson->questions_count) }}</span>
                    @endif
                    @if($isDone)
                        <span class="text-ok font-semibold">{{ __t('academy.common.completed') }} ✓</span>
                    @endif
                </div>
            </a>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:col-span-2 lg:col-span-3">
                <p class="font-semibold text-slate-700">{{ __t('academy.lessons.none') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __t('academy.lessons.none_hint') }}</p>
            </div>
        @endforelse
    </div>
@endsection
