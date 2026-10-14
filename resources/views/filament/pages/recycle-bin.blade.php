{{-- Settings → Recycle bin: deleted content, one kind per tab, each restorable. --}}
<x-filament-panels::page>
    <x-filament::tabs>
        @foreach($this->tabs() as $key => $tab)
            <x-filament::tabs.item
                tag="a"
                :href="$tab['url']"
                :active="$this->type === $key"
                :badge="$tab['count'] > 0 ? $tab['count'] : null"
            >
                {{ $tab['label'] }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>

    {{ $this->table }}
</x-filament-panels::page>
