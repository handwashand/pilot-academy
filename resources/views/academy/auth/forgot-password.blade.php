@extends('academy.layout')

@section('title', __t('auth.forgot.title'))

@php
    $metaDescription = __t('auth.forgot.meta');
@endphp

@section('content')
    @include('academy.partials.auth-brand')

    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-7 mt-6">
        <h1 class="text-2xl font-extrabold text-navy mb-1">{{ __t('auth.forgot.title') }}</h1>
        <p class="text-slate-500 text-sm mb-6">{{ __t('auth.forgot.intro') }}</p>

        {{-- The same answer whether or not the address has an account, so this
             page cannot be used to find out who is a customer. --}}
        @if(session('status'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 text-sm px-4 py-3" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">{{ __t('field.email') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <button class="w-full rounded-lg bg-brand text-white font-semibold px-5 py-2.5 hover:bg-blue-700">
                {{ __t('auth.forgot.send') }}
            </button>
        </form>

        <p class="text-sm text-slate-500 mt-5 text-center">
            <a href="{{ route('login') }}" class="text-brand font-semibold">{{ __t('auth.forgot.back') }}</a>
        </p>
    </div>
@endsection
