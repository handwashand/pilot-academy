@extends('academy.layout')

@section('title', __t('help.title'))

@php
    $metaDescription = __t('help.meta');
    $intro = collect($sections)->firstWhere('heading', '');
    $chapters = collect($sections)->where('heading', '!=', '')->values();
@endphp

@section('content')
    <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('help.heading') }}</h1>

    @if($chapters->isEmpty())
        <p class="text-slate-500 mt-2">{{ __t('help.unavailable') }}</p>
    @else
        @if($intro)
            <div class="prose-lesson mt-1 text-slate-500 max-w-3xl">{!! $intro['html'] !!}</div>
        @endif

        {{-- Same two-column, sticky-sidebar shape as a lesson page
             (academy/lesson.blade.php): the nav stays in view while you read
             instead of being a list you scroll back up for, and the content
             column is no longer capped at max-w-3xl, which read as narrow
             once the page actually had a sidebar's worth of room to use. --}}
        <div class="grid lg:grid-cols-[1fr_280px] gap-6 lg:gap-8 mt-6">
            {{-- Contents, first in the markup so a phone sees it before the
                 sections (no sidebar there to hold it); reordered to the
                 right and pinned in place from lg: up. Numbered the same way
                 the home page numbers its course list. --}}
            <aside class="lg:order-2 lg:sticky lg:top-20 self-start">
                <nav aria-label="{{ __t('help.contents_aria') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400 px-2 mb-2">{{ __t('help.contents_aria') }}</div>
                    <ol class="space-y-1">
                        @foreach($chapters as $chapter)
                            <li>
                                <a href="#{{ $chapter['id'] }}"
                                   class="flex items-center gap-3 px-2 min-h-11 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-50 hover:text-brand font-medium">
                                    <span aria-hidden="true" class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-500">{{ $loop->iteration }}</span>
                                    <span class="min-w-0 flex-1 truncate">{{ $chapter['heading'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </nav>
            </aside>

            <div class="lg:order-1 min-w-0">
                @foreach($chapters as $chapter)
                    <section id="{{ $chapter['id'] }}" class="scroll-mt-20 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-5">
                        <div class="flex items-center gap-3">
                            <span aria-hidden="true" class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-gradient-to-br from-brand to-navy text-sm font-extrabold text-white">{{ $loop->iteration }}</span>
                            <h2 class="text-xl font-extrabold text-navy">{{ $chapter['heading'] }}</h2>
                        </div>
                        <div class="prose-lesson mt-3">{!! $chapter['html'] !!}</div>
                    </section>
                @endforeach

                <p class="text-sm text-slate-500 mt-3">
                    {{ __t('help.still_stuck') }}
                    <a href="{{ route('academy.home') }}" class="text-brand font-semibold">{{ __t('help.back_to_courses') }}</a>
                </p>
            </div>
        </div>
    @endif
@endsection
