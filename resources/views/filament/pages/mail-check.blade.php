{{-- Settings → Mail: what the server does with email, in words. --}}
<x-filament-panels::page>
    @php
        $mail = $this->summary();
    @endphp

    <div class="max-w-3xl space-y-4">
        @if($mail['delivers'])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <p class="text-sm font-semibold text-gray-950 dark:text-white">The academy is set to send email.</p>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Certificates and reminders go out through the mail server below.
                    Use <strong>Send test email</strong> to be sure it really arrives.
                </p>
            </div>
        @else
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10">
                <p class="text-sm font-semibold text-warning-800 dark:text-warning-300">Emails are not being delivered.</p>
                <p class="mt-1 text-sm text-warning-800 dark:text-warning-300">
                    The academy keeps emails on the server ({{ $mail['transport'] === 'array' ? 'in memory' : 'in its log file' }})
                    instead of sending them, so students do not receive certificates or reminders by email.
                    Whoever runs the server needs to set <code>MAIL_MAILER</code> and the mail server details in <code>.env</code>.
                </p>
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
            <dl class="divide-y divide-gray-100 dark:divide-white/10">
                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">How email is sent</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['mailer'] }}</dd>
                </div>

                @if($mail['host'])
                    <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Mail server</dt>
                        <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['host'] }}{{ $mail['port'] ? ':'.$mail['port'] : '' }}</dd>
                    </div>
                @endif

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Sent from</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">{{ $mail['from_name'] }} &lt;{{ $mail['from_address'] }}&gt;</dd>
                </div>

                <div class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:gap-4">
                    <dt class="text-sm text-gray-500 dark:text-gray-400">Links in emails start with</dt>
                    <dd class="text-sm font-medium text-gray-950 dark:text-white">
                        {{ $mail['app_url'] ?: 'Not set' }}
                        @if($mail['app_url_is_local'])
                            <span class="block text-xs font-normal text-warning-700 dark:text-warning-400">
                                Not a public address — the logo and links in certificate emails will be broken. Set <code>APP_URL</code> to the real address.
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            These come from the server's <code>.env</code> file and cannot be changed here, which keeps mail passwords out of the admin panel.
        </p>
    </div>
</x-filament-panels::page>
