<div class="space-y-3" x-data="{ copied: false }">
    <textarea readonly rows="2" x-ref="accessLink" onclick="this.select()"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 break-all resize-none">{{ $url }}</textarea>

    <button type="button"
            x-on:click="
                $refs.accessLink.select();
                $refs.accessLink.setSelectionRange(0, 99999);
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText($refs.accessLink.value);
                    } else {
                        document.execCommand('copy');
                    }
                    copied = true;
                    setTimeout(() => copied = false, 1500);
                } catch (e) {}
            "
            class="inline-flex items-center gap-2 rounded-lg bg-primary-600 hover:bg-primary-500 px-4 py-2 text-sm font-semibold text-white">
        <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
    </button>

    <p class="text-sm text-gray-500 dark:text-gray-400">
        Send this link to the user. Opening it signs them in without a password, and their progress is saved to this account.
    </p>
</div>
