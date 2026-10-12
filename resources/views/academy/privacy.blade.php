@extends('academy.layout')

@section('title', __t('privacy.title'))

@php
    $metaDescription = __t('privacy.meta');
@endphp

@section('content')
    <div class="max-w-3xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy">{{ __t('privacy.heading') }}</h1>
        <p class="text-slate-500 mt-2">{{ __t('privacy.body') }}</p>

        <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            {{-- An external link, not an iframe or a copy: the policy stays
                 Pilot Telematics' own, read fresh every time, never duplicated
                 or allowed to drift out of date here. --}}
            <a href="https://pilot-telematics.com/blog/users/privacy-policy/"
               target="_blank" rel="noopener"
               class="inline-flex h-11 items-center gap-2 rounded-lg bg-brand px-5 font-semibold text-white hover:bg-blue-700">
                {{ __t('privacy.link') }}
                <svg class="w-4 h-4 flex-none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </a>
        </div>

        <a href="{{ route('academy.home') }}" class="inline-flex mt-6 text-sm font-semibold text-slate-500 hover:text-brand">
            {{ __t('privacy.back_to_home') }}
        </a>
    </div>
@endsection
