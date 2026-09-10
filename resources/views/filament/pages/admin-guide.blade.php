{{--
    docs/admin-guide.md with a contents list beside it and a search that hides
    the sections that do not mention the query. The section bodies keep the
    shared Markdown styling in partials/doc-styles; everything around them is
    utilities from the panel theme.
--}}
@php
    $sections = $this->sections();
@endphp

<x-filament-panels::page>
    @if ($sections === [])
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-400">
            The guide file <code>docs/admin-guide.md</code> was not found.
        </div>
    @else
        @include('filament.pages.partials.doc-styles')

        <div
            x-data="{
                q: '',
                texts: {{ \Illuminate\Support\Js::from(array_column($sections, 'text')) }},
                get needle() { return this.q.trim().toLowerCase() },
                visible(i) { return this.texts[i].includes(this.needle) },
                get anyVisible() { return this.texts.some((text, i) => this.visible(i)) },
            }"
            class="flex flex-col gap-6 lg:flex-row lg:items-start"
        >
            <aside class="lg:sticky lg:top-20 lg:w-64 lg:shrink-0">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <label for="guide-search" class="sr-only">Search the guide</label>
                    <input
                        id="guide-search"
                        type="search"
                        x-model="q"
                        placeholder="Search the guide…"
                        class="mb-4 w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-950 placeholder-gray-400 shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
                    >

                    <p class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                        Contents
                    </p>

                    <nav class="flex flex-col gap-0.5" aria-label="Guide contents">
                        @foreach ($sections as $i => $section)
                            @if ($section['heading'] !== '')
                                <a
                                    href="#{{ $section['id'] }}"
                                    x-show="visible({{ $i }})"
                                    class="rounded-md px-2 py-1.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-950 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white"
                                >
                                    {{ $section['heading'] }}
                                </a>
                            @endif
                        @endforeach
                    </nav>
                </div>
            </aside>

            <div class="min-w-0 flex-1 space-y-4">
                <p
                    x-cloak
                    x-show="! anyVisible"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-400"
                >
                    Nothing in the guide mentions “<span x-text="q.trim()"></span>”.
                </p>

                @foreach ($sections as $i => $section)
                    <section
                        id="{{ $section['id'] }}"
                        x-show="visible({{ $i }})"
                        class="scroll-mt-20 rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm dark:border-white/10 dark:bg-gray-900"
                    >
                        @if ($section['heading'] !== '')
                            <h2 class="mb-3 border-b border-gray-200 pb-3 text-lg font-semibold text-gray-950 dark:border-white/10 dark:text-white">
                                {{ $section['heading'] }}
                            </h2>
                        @endif

                        <div class="pa-guide">
                            {!! $section['html'] !!}
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
