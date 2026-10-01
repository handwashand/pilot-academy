@extends('academy.layout')

@section('title', __t('auth.login_title'))

@php
    $metaDescription = __t('auth.login_meta');
@endphp

@section('content')
    @include('academy.partials.auth-brand')

    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-7 mt-6">
        <h1 class="text-2xl font-extrabold text-navy mb-1">{{ __t('auth.login') }}</h1>
        <p class="text-slate-500 text-sm mb-6">{{ __t('auth.login_intro') }}</p>

        {{-- "Your password has been changed", after a reset. --}}
        @if(session('status'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-eed-200 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __t('field.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __t('field.password') }}</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"> {{ __t('auth.remember_me') }}
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-brand">{{ __t('auth.forgot.link') }}</a>
            </div>
            <button class="w-full rounded-lg bg-brand text-white font-semibold px-5 py-2.5 hover:bg-blue-700">
                {{ __t('auth.login') }}
            </button>
        </form>

        <p class="text-sm text-slate-500 mt-5 text-center">
            {{ __t('auth.no_account') }} <a href="{{ route('register') }}" class="text-brand font-semibold">{{ __t('auth.register') }}</a>
        </p>
    </div>
@endsection
