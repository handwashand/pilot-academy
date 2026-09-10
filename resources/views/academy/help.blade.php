@extends('academy.layout')

@section('title', 'Help — Pilot Academy')

@php
    $metaDescription = 'How Pilot Academy works: lessons, knowledge checks, the final quiz and certificates.';
    $intro = collect($sections)->firstWhere('heading', '');
    $chapters = collect($sections)->where('heading', '!=', '')->values();
@endphp

@section('content')
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">Help</h1>

        @if($chapters->isEmpty())
            <p class="text-slate-500 mt-2">The help guide is not available right now.</p>
        @else
            @if($intro)
                <div class="prose-lesson mt-1 text-slate-500">{!! $intro['html'] !!}</div>
            @endif

            {{-- Contents. Full-width rows so each one is a comfortable tap target
                 on a phone; the sticky header is 64px, hence scroll-mt-20 on the
                 sections it jumps to. --}}
            <nav aria-label="Help contents" class="bg-white rounded-2xl border border-slate-200 shadow-sm mt-6 mb-8 divide-y divide-slate-100">
                @foreach($chapters as $chapter)
                    <a href="#{{ $chapter['id'] }}"
                       class="flex items-center gap-3 px-5 min-h-11 py-2.5 text-slate-700 hover:bg-slate-50 active:bg-slate-100 font-medium">
                        <span class="min-w-0 flex-1">{{ $chapter['heading'] }}</span>
                        <span aria-hidden="true" class="text-slate-400 flex-none">&darr;</span>
                    </a>
                @endforeach
            </nav>

            @foreach($chapters as $chapter)
                <section id="{{ $chapter['id'] }}" class="scroll-mt-20 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6 mb-5">
                    <h2 class="text-xl font-extrabold text-navy">{{ $chapter['heading'] }}</h2>
                    <div class="prose-lesson">{!! $chapter['html'] !!}</div>
                </section>
            @endforeach

            <p class="text-sm text-slate-500 mt-8">
                Still stuck? Contact your academy administrator.
                <a href="{{ route('academy.home') }}" class="text-brand font-semibold">Back to courses</a>
            </p>
        @endif
    </div>
@endsection
