@extends('academy.layout')

@section('title', 'Your profile — Pilot Academy')

@php
    // Shared by every field: bg-white is load-bearing (preflight makes form
    // controls transparent) and py-3 keeps each one a comfortable tap target.
    $field = 'w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-800 focus:border-brand focus:ring-brand';
    $label = 'block text-sm font-semibold text-navy mb-1';
    $button = 'inline-flex items-center justify-center min-h-11 rounded-lg bg-brand px-5 text-sm font-semibold text-white hover:bg-blue-700';
    $saved = session('profile_saved');
@endphp

@section('content')
    <div class="max-w-2xl mx-auto">
        <a href="{{ route('academy.home') }}" class="text-sm text-brand font-semibold">&larr; All courses</a>

        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">Your profile</h1>
        <p class="text-slate-500 mt-1">Your name, the name on your certificates, and how you sign in.</p>

        {{-- Set about you by an administrator: shown, never editable here. --}}
        <section aria-labelledby="set-by-admin" class="mt-6 bg-slate-100 rounded-2xl p-5">
            <h2 id="set-by-admin" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Set by your administrator</h2>
            <dl class="mt-3">
                <dt class="text-sm text-slate-500">Partner company</dt>
                <dd class="font-semibold text-navy">{{ $user->company?->name ?? 'Not set' }}</dd>
            </dl>
            <p class="mt-3 text-sm text-slate-500">To change it, contact your academy administrator.</p>
        </section>

        {{-- Your details --}}
        <form method="POST" action="{{ route('academy.profile.update') }}"
              class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-extrabold text-navy">Your details</h2>

            @if($saved === 'details')
                <p role="status" class="mt-3 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">Your details are saved.</p>
            @endif

            <div class="mt-4">
                <label for="name" class="{{ $label }}">Name</label>
                <input type="text" id="name" name="name" required maxlength="255" autocomplete="name"
                       value="{{ old('name', $user->name) }}" class="{{ $field }}">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4">
                <label for="email" class="{{ $label }}">Email</label>
                <input type="email" id="email" name="email" required maxlength="255" autocomplete="email"
                       value="{{ old('email', $user->email) }}" class="{{ $field }}">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4">
                <label for="certificate_name" class="{{ $label }}">Name on certificates</label>
                <input type="text" id="certificate_name" name="certificate_name" maxlength="255"
                       value="{{ old('certificate_name', $user->certificate_name) }}" placeholder="{{ $user->name }}"
                       aria-describedby="certificate_name_help" class="{{ $field }}">
                <p id="certificate_name_help" class="mt-1 text-sm text-slate-500">
                    Printed on the certificates you earn from now on — leave empty to use your name.
                    Certificates you already have keep the name they were printed with; to correct one,
                    contact your academy administrator.
                </p>
                @error('certificate_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="{{ $button }} mt-5">Save details</button>
        </form>

        {{-- Password: "set" for invite-link accounts, "change" once one exists. --}}
        <form method="POST" action="{{ route('academy.profile.password') }}" id="password"
              class="mt-6 scroll-mt-20 bg-white rounded-2xl border border-slate-200 shadow-sm p-5 sm:p-6">
            @csrf
            @method('PUT')

            @if($user->hasOwnPassword())
                <h2 class="text-lg font-extrabold text-navy">Change password</h2>
            @else
                <h2 class="text-lg font-extrabold text-navy">Set a password</h2>
                <p class="mt-1 text-sm text-slate-500">
                    You sign in with your personal link, so your account has no password yet.
                    Set one to also log in with your email and password.
                </p>
            @endif

            @if($saved === 'password')
                <p role="status" class="mt-3 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm">Your password is saved.</p>
            @endif

            @if($user->hasOwnPassword())
                <div class="mt-4">
                    <label for="current_password" class="{{ $label }}">Current password</label>
                    <input type="password" id="current_password" name="current_password" required autocomplete="current-password" class="{{ $field }}">
                    @error('current_password', 'password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="mt-4">
                <label for="new_password" class="{{ $label }}">New password</label>
                <input type="password" id="new_password" name="password" required minlength="8" autocomplete="new-password"
                       aria-describedby="new_password_help" class="{{ $field }}">
                <p id="new_password_help" class="mt-1 text-sm text-slate-500">At least 8 characters.</p>
                @error('password', 'password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-4">
                <label for="password_confirmation" class="{{ $label }}">Confirm new password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="{{ $field }}">
            </div>

            <button type="submit" class="{{ $button }} mt-5">
                {{ $user->hasOwnPassword() ? 'Change password' : 'Set password' }}
            </button>
        </form>
    </div>
@endsection
