@extends('academy.layout')

@section('title', 'My certificates — Pilot Academy')

@section('content')
    <a href="{{ route('academy.home') }}" class="text-sm text-brand font-semibold">&larr; All courses</a>

    <h1 class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">My certificates</h1>
    <p class="text-slate-500 mt-1">Certificates you've earned by passing course final quizzes.</p>

    @if($certificates->isEmpty())
        <div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-8 text-center text-slate-500">
            <div class="text-4xl">🎓</div>
            <p class="mt-3">No certificates yet. Complete a course and pass its final quiz to earn one.</p>
        </div>
    @else
        <div class="mt-6 space-y-4">
            @foreach($certificates as $certificate)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                    <span class="w-12 h-12 flex-none rounded-xl bg-gradient-to-br from-brand to-navy text-white flex items-center justify-center text-2xl">🎓</span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="font-bold text-navy">{{ $certificate->course->title }}</h2>
                            @if($certificate->isValid())
                                <span class="text-[11px] font-semibold uppercase px-2 py-0.5 rounded-full bg-green-50 text-ok">Valid</span>
                            @else
                                <span class="text-[11px] font-semibold uppercase px-2 py-0.5 rounded-full bg-red-50 text-red-600">Revoked</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">
                            No. {{ $certificate->number }} · {{ $certificate->score_percent }}% · {{ $certificate->issued_at->format('M j, Y') }}
                        </p>
                    </div>
                    <div class="flex gap-2 flex-none">
                        @if($certificate->isValid() && $certificate->pdf_path)
                            <a href="{{ route('certificates.download', $certificate) }}"
                               class="inline-flex justify-center rounded-lg bg-ok text-white font-semibold px-4 py-2.5 text-sm hover:bg-green-700">
                                Download
                            </a>
                        @endif
                        <a href="{{ route('certificates.verify', $certificate->number) }}"
                           class="inline-flex justify-center rounded-lg border border-slate-300 text-slate-600 font-semibold px-4 py-2.5 text-sm hover:bg-slate-50">
                            Verify
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
