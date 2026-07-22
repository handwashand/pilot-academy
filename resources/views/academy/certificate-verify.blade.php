@extends('academy.layout')

@section('title', 'Verify certificate — Pilot Academy')

@section('content')
    <div class="max-w-xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy text-center">Certificate verification</h1>

        @if(! $certificate)
            <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center">
                <div class="text-4xl">🔍</div>
                <h2 class="text-lg font-bold text-navy mt-3">No certificate found</h2>
                <p class="text-slate-500 mt-1">
                    We couldn't find a certificate with the number
                    <strong class="text-slate-700">{{ $number }}</strong>.
                    Please check the number and try again.
                </p>
            </div>
        @else
            <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 {{ $certificate->isValid() ? 'bg-green-50 border-b border-green-100' : 'bg-red-50 border-b border-red-100' }}">
                    <div class="flex items-center gap-3">
                        <span class="w-10 h-10 flex-none rounded-full flex items-center justify-center text-white {{ $certificate->isValid() ? 'bg-ok' : 'bg-red-500' }}">
                            {{ $certificate->isValid() ? '✓' : '✕' }}
                        </span>
                        <div>
                            <div class="font-bold {{ $certificate->isValid() ? 'text-green-800' : 'text-red-700' }}">
                                {{ $certificate->isValid() ? 'Valid certificate' : 'Certificate revoked' }}
                            </div>
                            <div class="text-sm {{ $certificate->isValid() ? 'text-green-700' : 'text-red-600' }}">No. {{ $certificate->number }}</div>
                        </div>
                    </div>
                </div>

                <dl class="divide-y divide-slate-100">
                    <div class="flex justify-between gap-4 px-6 py-4">
                        <dt class="text-slate-500">Issued to</dt>
                        <dd class="font-semibold text-navy text-right">{{ $certificate->name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 px-6 py-4">
                        <dt class="text-slate-500">Course</dt>
                        <dd class="font-semibold text-navy text-right">{{ $certificate->course->title }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 px-6 py-4">
                        <dt class="text-slate-500">Issued on</dt>
                        <dd class="font-semibold text-navy text-right">{{ $certificate->issued_at->format('F j, Y') }}</dd>
                    </div>
                    @unless($certificate->isValid())
                        <div class="flex justify-between gap-4 px-6 py-4">
                            <dt class="text-slate-500">Revoked on</dt>
                            <dd class="font-semibold text-red-600 text-right">{{ $certificate->revoked_at->format('F j, Y') }}</dd>
                        </div>
                    @endunless
                </dl>
            </div>
        @endif

        <p class="text-center text-xs text-slate-400 mt-6">Pilot Academy · certificate verification</p>
    </div>
@endsection
