<?php

namespace App\Filament\Pages;

use App\Mail\MailCheckMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

/**
 * Is the academy actually sending email?
 *
 * Certificates and reminders go out by email, and the stock setting writes them
 * to a log file instead — which looks exactly like working from inside the
 * panel. This page says in words what the server is set to do, and sends a
 * test email to prove it.
 *
 * Read-only on purpose. Support Training Hub stores SMTP credentials in its
 * database behind a super-admin permission; here they stay in the server's
 * .env, so the panel never holds a mail password.
 */
class MailCheck extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.mail.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_nav.mail.nav');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.settings');
    }

    protected string $view = 'filament.pages.mail-check';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /**
     * What the server is set to do with email.
     *
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $mailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$mailer}.transport", $mailer);
        $appUrl = (string) config('app.url');

        return [
            'mailer' => $mailer,
            'transport' => $transport,
            // log and array keep the message on the server; nothing is delivered.
            'delivers' => ! in_array($transport, ['log', 'array'], true),
            'host' => config("mail.mailers.{$mailer}.host"),
            'port' => config("mail.mailers.{$mailer}.port"),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'app_url' => $appUrl,
            // Certificate emails build their logo and links from it.
            'app_url_is_local' => $appUrl === '' || Str::contains($appUrl, ['localhost', '127.0.0.1', 'example.com']),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTest')
                ->label(__t('admin_pages.mail.send_test'))
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading(__t('admin_pages.mail.send_test_heading'))
                ->modalDescription(fn (): string => __t('admin_pages.mail.send_test_description', ['email' => auth()->user()->email]))
                ->action(function (): void {
                    $user = auth()->user();

                    try {
                        Mail::to($user->email)->send(new MailCheckMessage($user));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title(__t('admin_pages.mail.failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (! $this->summary()['delivers']) {
                        Notification::make()
                            ->title(__t('admin_pages.mail.not_delivered'))
                            ->body(__t('admin_pages.mail.not_delivered_body'))
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__t('admin_pages.mail.sent'))
                        ->body(__t('admin_pages.mail.sent_body', ['email' => $user->email]))
                        ->success()
                        ->send();
                }),
        ];
    }
}
