{{--
    Filament's panel CSS is precompiled: arbitrary Tailwind utilities used in
    custom views are NOT included, so classes like `w-full`/`bg-primary-600`
    silently render unstyled (the old "Copy link" looked like plain text).
    Stick to Filament blade components + inline styles here.
--}}
<div
    x-data="{
        copied: false,
        timer: null,
        copy() {
            const el = $refs.accessLink;
            el.select();
            el.setSelectionRange(0, 99999);
            try {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(el.value);
                } else {
                    document.execCommand('copy');
                }
                this.copied = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.copied = false, 2000);
            } catch (e) {}
        },
    }"
    style="display: flex; flex-direction: column; gap: 0.75rem;"
>
    <style>[x-cloak] { display: none !important; }</style>

    <x-filament::input.wrapper>
        <x-filament::input
            type="text"
            readonly
            :value="$url"
            x-ref="accessLink"
            onclick="this.select()"
        />
    </x-filament::input.wrapper>

    <div style="display: flex; align-items: center; gap: 0.5rem;">
        <x-filament::button
            type="button"
            icon="heroicon-m-clipboard"
            x-show="! copied"
            x-on:click="copy"
        >
            Copy link
        </x-filament::button>

        <x-filament::button
            type="button"
            color="success"
            icon="heroicon-m-check"
            x-cloak
            x-show="copied"
            x-transition.scale.90.duration.200ms
        >
            Copied!
        </x-filament::button>
    </div>

    <p style="font-size: 0.875rem; opacity: 0.7;">
        Send this link to the user. Opening it signs them in without a password, and their progress is saved to this account.
    </p>
</div>
