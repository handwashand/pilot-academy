@extends('academy.layout')

@section('title', __t('academy.meta.register_title'))

@php
    $metaDescription = __t('academy.meta.register_description');
@endphp

@section('content')
    @include('academy.partials.auth-brand')

    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-7 mt-6">
        <h1 class="text-2xl font-extrabold text-navy mb-1">{{ __t('academy.signup.create_heading') }}</h1>
        <p class="text-slate-500 text-sm mb-6">{{ __t('academy.signup.create_intro') }}</p>

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __t('academy.signup.full_name') }}</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __t('academy.signup.email') }}</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __t('academy.signup.password') }}</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
                <p class="text-xs text-slate-400 mt-1">{{ __t('academy.signup.min_8') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">{{ __t('academy.signup.confirm_password') }}</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <button class="w-full rounded-lg bg-brand text-white font-semibold px-5 py-2.5 hover:bg-blue-700">
                {{ __t('academy.signup.create_account') }}
            </button>
        </form>

        <p class="text-sm text-slate-500 mt-5 text-center">
            {{ __t('academy.signup.have_account') }} <a href="{{ route('login') }}" class="text-brand font-semibold">{{ __t('academy.signup.log_in') }}</a>
        </p>
    </div>
@endsection
