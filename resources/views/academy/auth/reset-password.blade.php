@extends('academy.layout')

@section('title', __t('auth.reset.title'))

@php
    $metaDescription = __t('auth.reset.meta');
@endphp

@section('content')
    @include('academy.partials.auth-brand')

    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-7 mt-6">
        <h1 class="text-2xl font-extrabold text-navy mb-1">{{ __t('auth.reset.title') }}</h1>
        <p class="text-slate-500 text-sm mb-6">{{ __t('auth.reset.intro') }}</p>

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">{{ __t('field.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">{{ __t('auth.reset.new_password') }}</label>
                <input id="password" type="password" name="password" required autofocus autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
                <p class="mt-1 text-xs text-slate-500">{{ __t('auth.reset.rule') }}</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">{{ __t('auth.reset.confirm') }}</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>

            <button class="w-full rounded-lg bg-brand text-white font-semibold px-5 py-2.5 hover:bg-blue-700">
                {{ __t('auth.reset.save') }}
            </button>
        </form>

        <p class="text-sm text-slate-500 mt-5 text-center">
            <a href="{{ route('password.request') }}" class="text-brand font-semibold">{{ __t('auth.reset.ask_again') }}</a>
        </p>
    </div>
@endsection
