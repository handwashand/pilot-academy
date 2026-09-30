{{-- Staff forget passwords too. The link goes to the academy's own reset
     pages, the same ones partners use, so there is one flow and one email. --}}
<p class="fi-form-actions mt-4 text-center text-sm">
    <a href="{{ route('password.request') }}" class="fi-link font-semibold text-primary-600 hover:underline dark:text-primary-400">
        {{ __t('auth.forgot.link') }}
    </a>
</p>
