{{--
    Renders nothing on its own — the Settings overlay is entirely the
    mounted "settings" action's modal. Any Livewire component implementing
    HasActions needs its own <x-filament-actions::modals /> to render the
    actions it mounts; Filament's own sidebar and topbar components do the
    same (see vendor/filament/filament/resources/views/livewire/sidebar.blade.php).
--}}
<div>
    <x-filament-actions::modals />
</div>
