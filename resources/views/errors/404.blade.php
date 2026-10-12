@extends('academy.layout')

@section('title', __t('academy.meta.not_found_title'))

@section('content')
    <div class="flex flex-col items-center py-10 sm:py-16 text-center">
        <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-brand to-navy text-white shadow-sm">
            {{-- Heroicons "face-frown" outline, same family and stroke-width as
                 the header's nav icons and language globe. --}}
            <svg class="h-10 w-10" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.182 16.318A4.486 4.486 0 0 0 12.016 15a4.486 4.486 0 0 0-3.198 1.318M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-5.25-4.5h.008v.008h-.008V7.5Zm-5.25 0h.008v.008h-.008V7.5Z" />
            </svg>
        </div>

        <p class="mb-2 text-sm font-bold uppercase tracking-wide text-brand">{{ __t('academy.not_found.eyebrow') }}</p>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('academy.not_found.heading') }}</h1>
        <p class="mt-3 max-w-md text-slate-500">{{ __t('academy.not_found.body') }}</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('academy.home') }}"
               class="flex h-11 items-center rounded-lg bg-brand px-5 font-semibold text-white hover:bg-blue-700">
                {{ __t('academy.not_found.home') }}
            </a>
            <a href="{{ route('academy.courses') }}"
               class="flex h-11 items-center rounded-lg border border-slate-200 px-5 font-semibold text-slate-600 hover:bg-slate-50">
                {{ __t('academy.not_found.courses') }}
            </a>
        </div>
    </div>
@endsection
