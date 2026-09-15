{{-- Settings → Mail: what the server does with email, in words. --}}
<x-filament-panels::page>
    @php
        $mail = $this->summary();
    @endphp

    <div class="max-w-3xl space-y-4">
        @if($mail['delivers'])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ __t('admin_pages.mail.delivers') }}</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __t('admin_pages.mail.delivers_body') }}
                </p>
            </div>
        @else
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10">
                <p class="text-sm font-semibold text-warning-800 dark:text-warning-300">{{ __t('admin_pages.mail.not_delivering') }}</p>
                <p class="mt-1 text-sm text-warning-800 dark:text-warning-300">
                    {{ __t('admin_pages.mail.not_delivering_body', ['where' => $mail['transport'] === 'array' ? __t('admin_pages.mail.in_memory') : __t('admin_pages.mail.in_log')]) }}
                </p>
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <dl class="divide-y divide-gray-100 dark:divide-white/10">
                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __t('admin_pages.mail.how_sent') }}</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['mailer'] }}</dd>
                </div>

                @if($mail['host'])
                    <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __t('admin_pages.mail.server') }}</dt>
                        <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['host'] }}{{ $mail['port'] ? ':'.$mail['port'] : '' }}</dd>
                    </div>
                @endif

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __t('admin_pages.mail.from') }}</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['from_name'] }} &lt;{{ $mail['from_address'] }}&gt;</dd>
                </div>

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ __t('admin_pages.mail.links') }}</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $mail['app_url'] ?: __t('admin_pages.mail.not_set') }}
                        @if($mail['app_url_is_local'])
                            <span class="block text-xs font-normal text-warning-700 dark:text-warning-400">
                                {{ __t('admin_pages.mail.local_url') }}
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __t('admin_pages.mail.env_note') }}
        </p>
    </div>
</x-filament-panels::page>
