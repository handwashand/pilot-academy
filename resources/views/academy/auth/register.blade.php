@extends('academy.layout')

@section('title', 'Register — Pilot Academy')

@section('content')
    <div class="max-w-md mx-auto bg-white rounded-2xl border border-slate-200 shadow-sm p-7 mt-6">
        <h1 class="text-2xl font-extrabold text-navy mb-1">Create your account</h1>
        <p class="text-slate-500 text-sm mb-6">Track your progress and earn certificates.</p>

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
                <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
                <p class="text-xs text-slate-400 mt-1">At least 8 characters.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-lg border border-slate-300 px-4 py-2.5 outline-none focus:border-brand">
            </div>
            <button class="w-full rounded-lg bg-brand text-white font-semibold px-5 py-2.5 hover:bg-blue-700">
                Create account
            </button>
        </form>

        <p class="text-sm text-slate-500 mt-5 text-center">
            Already have an account? <a href="{{ route('login') }}" class="text-brand font-semibold">Log in</a>
        </p>
    </div>
@endsection
