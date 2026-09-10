{{-- Shared wrapper for a Markdown doc rendered inside the panel. Expects $html. --}}
@include('filament.pages.partials.doc-styles')

<div class="pa-guide">
    {!! $html !!}
</div>
