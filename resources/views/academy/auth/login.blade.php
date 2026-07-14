@extends('academy.layout')

@section('title', 'Log in — Pilot Academy')

@php
    $metaDescription = 'Вход в Pilot Academy — доступ к курсам обучения работе с системой мониторинга транспорта Pilot и сохранённому прогрессу.';
@endphp

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-7 mt-6">
        <h1 class="text-2xl font-extrabold text-navy mb-1">Log in</h1>
        <p class="text-slate-500 text-sm mb-6">Access your courses and saved progress.</p>

        @if($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1"> Remember me
            </label>
            <button class="w-full rounded-lg bg-brand text-white font-semibold px-5 py-2.5 hover:bg-blue-700">
                Log in
            </button>
        </form>

        <p class="text-sm text-slate-500 mt-5 text-center">
            No account? <a href="{{ route('register') }}" class="text-brand font-semibold">Register</a>
        </p>
    </div>
@endsection
