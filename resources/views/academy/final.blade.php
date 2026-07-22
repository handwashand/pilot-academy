@extends('academy.layout')

@section('title', 'Final quiz — ' . $course->title)

@section('content')
    @php
        $mode = $state['mode'];
        $result = session('final_result');
        $needed = isset($result) ? (int) ceil($course->pass_percent / 100 * $result['total']) : null;
    @endphp

    <a href="{{ route('academy.course', $course) }}" class="text-sm text-brand font-semibold">&larr; {{ $course->title }}</a>

    <div class="max-w-2xl">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-navy mt-2">Final quiz</h1>
        <p class="text-slate-500 mt-1">{{ $course->title }}</p>

        {{-- Result banner (after a submitted attempt) --}}
        @if($result)
            <div class="mt-5 rounded-2xl border px-5 py-4 {{ $result['passed'] ? 'bg-green-50 border-green-200 text-green-800' : 'bg-amber-50 border-amber-200 text-amber-900' }}">
                <div class="font-bold text-lg">{{ $result['passed'] ? 'Passed 🎉' : 'Not passed yet' }}</div>
                <p class="text-sm mt-1">
                    You answered <strong>{{ $result['score'] }} of {{ $result['total'] }}</strong> correctly ({{ $result['percent'] }}%).
                    @unless($result['passed'])
                        To pass you need <strong>{{ $needed }} correct</strong> ({{ $course->pass_percent }}%).
                    @endunless
                </p>
            </div>
        @endif

        {{-- PASSED: certificate ready --}}
        @if($mode === 'passed')
            @php($certificate = $state['certificate'])
            <div class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-brand to-navy text-white flex items-center justify-center text-3xl">🎓</div>
                <h2 class="text-xl font-extrabold text-navy mt-4">You're certified!</h2>
                <p class="text-slate-500 text-sm mt-1">
                    Certificate No. <strong>{{ $certificate->number }}</strong> · {{ $certificate->score_percent }}%
                    · issued {{ $certificate->issued_at->format('M j, Y') }}
                </p>
                <div class="mt-5 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('certificates.download', $certificate) }}"
                       class="inline-flex justify-center rounded-lg bg-ok text-white font-semibold px-6 py-3 hover:bg-green-700">
                        Download PDF
                    </a>
                    <a href="{{ route('certificates.index') }}"
                       class="inline-flex justify-center rounded-lg border border-slate-300 text-slate-600 font-semibold px-6 py-3 hover:bg-slate-50">
                        My certificates
                    </a>
                </div>
            </div>

        {{-- ACTIVE: the questions --}}
        @elseif($mode === 'active')
            <form method="POST" action="{{ route('academy.final.submit', $course) }}" class="mt-5 space-y-6">
                @csrf
                @foreach($state['questions'] as $qn => $question)
                    @php($multiple = $question->type === \App\Models\Question::TYPE_MULTIPLE)
                    <fieldset class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5">
                        <legend class="px-2 font-semibold text-navy">{{ $qn + 1 }}. {{ $question->prompt }}</legend>
                        @if($multiple)
                            <p class="px-2 text-xs text-slate-400 mb-1">Select all that apply.</p>
                        @endif
                        <div class="space-y-2 mt-2">
                            @foreach($question->options->shuffle() as $option)
                                <label class="flex items-center gap-3 px-3 py-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-50 active:bg-slate-100">
                                    @if($multiple)
                                        <input type="checkbox" name="answers[{{ $question->id }}][]" value="{{ $option->id }}" class="text-brand w-4 h-4 flex-none rounded">
                                    @else
                                        <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="text-brand w-4 h-4 flex-none" required>
                                    @endif
                                    <span>{{ $option->text }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <button class="w-full sm:w-auto rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                    Submit final quiz
                </button>
            </form>

        {{-- EXHAUSTED --}}
        @elseif($mode === 'exhausted')
            <div class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="rounded-xl bg-slate-100 border border-slate-200 text-slate-600 px-5 py-4">
                    <strong>No attempts remaining.</strong> You've used all {{ $state['maxAttempts'] }} attempts for this final quiz.
                    Please contact your administrator if you need another attempt.
                </div>
            </div>

        {{-- LOCKED --}}
        @elseif($mode === 'locked')
            <div class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-5 py-4">
                    🔒 Complete all lessons to unlock the final quiz.
                </div>
                <a href="{{ route('academy.course', $course) }}" class="mt-4 inline-block text-brand font-semibold">&larr; Back to the course</a>
            </div>

        {{-- UNAVAILABLE --}}
        @elseif($mode === 'unavailable')
            <div class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 text-slate-500">
                The final quiz for this course isn't available yet.
            </div>

        {{-- PRESTART --}}
        @else
            <div class="mt-5 bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="rounded-xl bg-blue-50 border border-blue-100 text-navy px-5 py-4 mb-5">
                    <div class="font-semibold mb-1">Before you start</div>
                    <ul class="text-sm space-y-1 list-disc pl-5 text-slate-600">
                        <li>{{ $state['questionCount'] }} question(s), drawn at random.</li>
                        <li>You need <strong>{{ $state['passPercent'] }}%</strong> or higher to pass.</li>
                        @if($state['attemptsRemaining'] !== null)
                            <li>You have <strong>{{ $state['attemptsRemaining'] }}</strong> attempt(s) left.</li>
                        @else
                            <li>Unlimited attempts — each draws a fresh set of questions.</li>
                        @endif
                    </ul>
                </div>

                @auth
                    @if(auth()->user()->is_admin)
                        <div class="rounded-xl bg-violet-50 border border-violet-200 text-violet-800 px-5 py-3 mb-4 text-sm">
                            <strong>Admin preview.</strong> As an admin you can take this final quiz without finishing the lessons. A real certificate will be issued to your account.
                        </div>
                    @endif
                @endauth

                <form method="POST" action="{{ route('academy.final.start', $course) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="certificate_name" class="block text-sm font-semibold text-navy mb-1">Full name for your certificate</label>
                        <input type="text" id="certificate_name" name="certificate_name" required maxlength="255"
                               value="{{ old('certificate_name', $state['certificateName']) }}"
                               class="w-full rounded-lg border border-slate-300 px-4 py-3 focus:border-brand focus:ring-brand @error('certificate_name') border-red-400 @enderror">
                        <p class="text-xs text-slate-400 mt-1">This exact name will be printed on your certificate.</p>
                        @error('certificate_name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button class="w-full sm:w-auto rounded-lg bg-brand text-white font-semibold px-6 py-3 hover:bg-blue-700">
                        Start final quiz
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection
