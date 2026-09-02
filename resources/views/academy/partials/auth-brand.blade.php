{{-- The brand moment on the student sign-in, register and join pages — the
     same treatment the admin panel's sign-in page gets, at the same 48px, so
     both front doors of the academy look like the same product.

     The real lockup, not the header's mark-plus-text: there is room here, and
     it is the only thing above the form, so it should lead rather than sit
     under the heading. --}}
<a href="{{ route('academy.home') }}" class="block mb-6 text-center">
    <img src="{{ asset('img/pilot-logo.png') }}" alt="Pilot Academy"
         class="h-12 w-auto mx-auto">
</a>
