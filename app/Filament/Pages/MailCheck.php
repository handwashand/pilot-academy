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

    protected static ?string $navigationLabel = 'Mail';

    protected static ?string $title = 'Mail';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 30;

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
                ->label('Send test email')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Send a test email')
                ->modalDescription(fn (): string => 'A short test email goes to '.auth()->user()->email.'.')
                ->action(function (): void {
                    $user = auth()->user();

                    try {
                        Mail::to($user->email)->send(new MailCheckMessage($user));
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('The test email could not be sent')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }

                    if (! $this->summary()['delivers']) {
                        Notification::make()
                            ->title('Nothing was delivered')
                            ->body('The academy is set to keep emails on the server instead of sending them. Ask whoever runs the server to set up a mail server in .env.')
                            ->warning()
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Test email sent')
                        ->body("Check {$user->email}. If it has not arrived in a few minutes, look in spam, then ask whoever runs the server to check the mail settings.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
