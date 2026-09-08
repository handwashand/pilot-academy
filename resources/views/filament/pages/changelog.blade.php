{{--
    What's new — docs/CHANGELOG.md rendered as a filterable list.

    Search and category filtering run on the client over a matrix built here,
    server-side: for every release, for every section, the section's type and
    the lowercased plain text of each of its items. Visibility rolls UP from
    the items — filtering only the items leaves empty category headings and
    empty month cards standing behind the results.

    Every utility class on this page depends on the panel's custom theme
    (resources/css/filament/admin/theme.css). Filament's own stylesheet has no
    Tailwind utility layer, so without that theme this page renders unstyled
    while the source looks perfectly correct.
--}}
@php
    $types = \App\Filament\Pages\Changelog::TYPES;
    $releases = $this->releases();

    $matrix = [];
    $releaseCounts = [];
    $totals = array_fill_keys(array_keys($types), 0);
    $entries = 0;

    foreach ($releases as $release) {
        $row = [];
        $counts = array_fill_keys(array_keys($types), 0);

        foreach ($release['sections'] as $section) {
            $row[] = [
                'type' => $section['type'],
                'items' => array_column($section['items'], 'text'),
            ];

            $found = count($section['items']);
            $counts[$section['type']] += $found;
            $totals[$section['type']] += $found;
            $entries += $found;
        }

        $matrix[] = $row;
        $releaseCounts[] = array_filter($counts);
    }

    $totals = array_filter($totals);
    $months = count($releases);
@endphp

<x-filament-panels::page>
    @if ($releases === [])
        {{-- Never a blank page: name the file and say what to put in it. --}}
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-white/10 dark:bg-gray-900">
            <p class="text-sm font-medium text-gray-950 dark:text-white">
                Nothing to show yet.
            </p>
            <p class="mx-auto mt-2 max-w-md text-sm text-gray-500 dark:text-gray-400">
                This page reads
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">{{ \App\Filament\Pages\Changelog::changelogLabel() }}</code>
                and that file is missing or has no release headings in it. Add a
                heading like
                <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">## {{ now()->format('F Y') }}</code>
                followed by <code class="rounded bg-gray-100 px-1 py-0.5 text-xs dark:bg-white/10">### Added</code>
                and a bullet per change, and it will appear here.
            </p>
        </div>
    @else
        <div
            x-data="{
                q: '',
                picked: [],
                m: {{ \Illuminate\Support\Js::from($matrix) }},
                get needle() { return this.q.trim().toLowerCase() },
                typeOn(type) { return this.picked.length === 0 || this.picked.includes(type) },
                toggle(type) {
                    const at = this.picked.indexOf(type)
                    at === -1 ? this.picked.push(type) : this.picked.splice(at, 1)
                },
                itemVisible(r, s, i) {
                    return this.typeOn(this.m[r][s].type) && this.m[r][s].items[i].includes(this.needle)
                },
                sectionVisible(r, s) {
                    return this.m[r][s].items.some((text, i) => this.itemVisible(r, s, i))
                },
                releaseVisible(r) {
                    return this.m[r].some((section, s) => this.sectionVisible(r, s))
                },
                get anyVisible() { return this.m.some((sections, r) => this.releaseVisible(r)) },
                get emptyMessage() {
                    return this.needle
                        ? 'Nothing matches “' + this.q.trim() + '”'
                        : 'Nothing in the categories you picked'
                },
            }"
            class="space-y-4"
        >
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $entries }} {{ \Illuminate\Support\Str::plural('entry', $entries) }}
                across {{ $months }} {{ \Illuminate\Support\Str::plural('month', $months) }}.
            </p>

            <div class="flex flex-wrap items-center gap-2">
                <div class="grow sm:grow-0">
                    {{-- A placeholder is not a label. --}}
                    <label for="whats-new-search" class="sr-only">Search what's new</label>
                    <input
                        id="whats-new-search"
                        type="search"
                        x-model="q"
                        placeholder="Search changes…"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-950 placeholder-gray-400 shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 sm:w-64 dark:border-white/10 dark:bg-white/5 dark:text-white dark:placeholder-gray-500"
                    >
                </div>

                @foreach ($totals as $type => $total)
                    <button
                        type="button"
                        x-on:click="toggle(@js($type))"
                        x-bind:class="picked.includes(@js($type))
                            ? 'border-gray-400 bg-gray-100 text-gray-950 dark:border-white/25 dark:bg-white/10 dark:text-white'
                            : 'border-gray-200 bg-white text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400'"
                        x-bind:aria-pressed="picked.includes(@js($type)) ? 'true' : 'false'"
                        class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium"
                    >
                        <span class="size-1.5 rounded-full {{ $types[$type]['dot'] }}"></span>
                        {{ $types[$type]['label'] }}
                        <span class="tabular-nums opacity-60">{{ $total }}</span>
                    </button>
                @endforeach
            </div>

            <p
                x-cloak
                x-show="! anyVisible"
                x-text="emptyMessage"
                class="rounded-xl border border-gray-200 bg-white px-4 py-6 text-center text-sm text-gray-500 shadow-sm dark:border-white/10 dark:bg-gray-900 dark:text-gray-400"
            ></p>

            @foreach ($releases as $r => $release)
                <article
                    x-show="releaseVisible({{ $r }})"
                    class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900"
                >
                    <header class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-gray-200 px-5 py-3 dark:border-white/10">
                        <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $release['title'] }}
                        </h2>

                        @if ($r === 0)
                            <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-400/10 dark:text-primary-400">
                                Latest
                            </span>
                        @endif

                        <span class="ms-auto flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            @foreach ($releaseCounts[$r] as $type => $count)
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="size-1.5 rounded-full {{ $types[$type]['dot'] }}"></span>
                                    {{ $types[$type]['label'] }} {{ $count }}
                                </span>
                            @endforeach
                        </span>
                    </header>

                    <div class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($release['sections'] as $s => $section)
                            <section x-show="sectionVisible({{ $r }}, {{ $s }})" class="px-5 py-4">
                                <h3 class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">
                                    {{ $section['label'] }}
                                </h3>

                                <ul class="space-y-2.5">
                                    @foreach ($section['items'] as $i => $item)
                                        <li
                                            x-show="itemVisible({{ $r }}, {{ $s }}, {{ $i }})"
                                            class="flex gap-2.5"
                                        >
                                            {{-- Category colour is carried by this dot and nothing else. --}}
                                            <span
                                                class="mt-1.5 size-1.5 shrink-0 rounded-full {{ $types[$section['type']]['dot'] }}"
                                                aria-hidden="true"
                                            ></span>
                                            <div class="wn-body min-w-0 text-sm text-gray-600 dark:text-gray-400">
                                                {!! $item['html'] !!}
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
