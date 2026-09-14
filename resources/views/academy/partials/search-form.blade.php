{{-- Shared by the home page and the results page, so refining a search does
     not mean going back. --}}
<form method="GET" action="{{ route('academy.search') }}" role="search"
      class="flex flex-col sm:flex-row gap-2 max-w-xl">
    <label for="{{ $inputId ?? 'q' }}" class="vh">{{ __t('academy.search.label') }}</label>
    <input type="search" name="q" id="{{ $inputId ?? 'q' }}"
           value="{{ $term ?? '' }}"
           placeholder="{{ __t('academy.search.label') }}"
           class="flex-1 h-11 rounded-lg bg-white border border-slate-300 px-4 text-slate-800">
    <button class="h-11 rounded-lg bg-navy text-white font-semibold px-5 hover:bg-slate-800">
        {{ __t('academy.search.button') }}
    </button>
</form>
