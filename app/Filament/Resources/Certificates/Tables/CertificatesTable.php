<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Actions\IssueCertificate;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('number')
                    ->label(__t('admin_common.number'))
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label(__t('admin_common.student'))
                    ->description(fn (Certificate $record): ?string => $record->user?->email)
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label(__t('admin_common.partner'))
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('course.title')
                    ->label(__t('admin_common.course'))
                    ->sortable(),

                TextColumn::make('score_percent')
                    ->label(__t('admin_common.score'))
                    ->formatStateUsing(fn ($state): string => "{$state}%")
                    ->sortable(),

                TextColumn::make('attempts')
                    ->label(__t('admin_common.attempts'))
                    ->badge()
                    ->getStateUsing(fn (Certificate $record): int => QuizAttempt::where('user_id', $record->user_id)
                        ->where('course_id', $record->course_id)
                        ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED])
                        ->count()),

                TextColumn::make('issued_at')
                    ->label(__t('admin_common.issued'))
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__t('admin_common.status'))
                    ->badge()
                    ->getStateUsing(fn (Certificate $record): string => $record->statusLabel())
                    ->color(fn (Certificate $record): string => $record->isValid() ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->label(__t('admin_common.course'))
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('company')
                    ->label(__t('admin_common.partner'))
                    ->options(fn (): array => Company::orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $q, $companyId) => $q->whereHas('user', fn (Builder $u) => $u->where('company_id', $companyId)),
                    )),

                SelectFilter::make('status')
                    ->label(__t('admin_common.status'))
                    ->options(['valid' => __t('labels.certificate.valid'), 'revoked' => __t('labels.certificate.revoked')])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] === 'valid',
                        fn (Builder $q) => $q->whereNull('revoked_at'),
                    )->when(
                        $data['value'] === 'revoked',
                        fn (Builder $q) => $q->whereNotNull('revoked_at'),
                    )),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label(__t('admin_results.certificates.export'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (): StreamedResponse => static::exportCsv()),
            ])
            ->recordActions([
                Action::make('download')
                    ->label(__t('admin_common.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Certificate $record): bool => $record->pdf_path && Storage::disk('public')->exists($record->pdf_path))
                    ->action(fn (Certificate $record) => Storage::disk('public')->download(
                        $record->pdf_path,
                        'certificate-'.$record->number.'.pdf'
                    )),

                Action::make('resend')
                    ->label(__t('admin_results.certificates.resend'))
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Certificate $record): string => __t('admin_results.certificates.resend_description', ['email' => (string) $record->user?->email]))
                    ->action(function (Certificate $record, IssueCertificate $issue): void {
                        if (! $record->pdf_path || ! Storage::disk('public')->exists($record->pdf_path)) {
                            $issue->renderPdf($record);
                        }
                        $issue->email($record);

                        Notification::make()->title(__t('admin_results.certificates.emailed'))->success()->send();
                    }),

                Action::make('regenerate')
                    ->label(__t('admin_results.certificates.regenerate'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Certificate $record, IssueCertificate $issue): void {
                        $issue->renderPdf($record);

                        Notification::make()->title(__t('admin_results.certificates.regenerated'))->success()->send();
                    }),

                // A certificate stores the name it was printed with, and
                // Regenerate PDF reprints that same name — so a misspelling used
                // to need a developer. The number, date and score never change.
                Action::make('editName')
                    ->label(__t('admin_results.certificates.edit_name'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('gray')
                    ->modalHeading(__t('admin_results.certificates.edit_name_heading'))
                    ->modalDescription(__t('admin_results.certificates.edit_name_description'))
                    ->fillForm(fn (Certificate $record): array => [
                        'name' => $record->name,
                        'update_profile' => true,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label(__t('admin_results.certificates.name_on_certificate'))
                            ->required()
                            ->maxLength(255),
                        Toggle::make('update_profile')
                            ->label(__t('admin_results.certificates.update_profile'))
                            ->helperText(__t('admin_results.certificates.update_profile_help')),
                    ])
                    ->action(function (Certificate $record, array $data, IssueCertificate $issue): void {
                        $name = trim($data['name']);

                        $record->forceFill(['name' => $name])->save();

                        if (($data['update_profile'] ?? false) && $record->user) {
                            $record->user->forceFill(['certificate_name' => $name])->save();
                        }

                        $issue->renderPdf($record);

                        Notification::make()->title(__t('admin_results.certificates.name_corrected'))->success()->send();
                    }),

                Action::make('revoke')
                    ->label(__t('admin_results.certificates.revoke'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__t('admin_results.certificates.revoke_description'))
                    ->visible(fn (Certificate $record): bool => $record->isValid())
                    ->action(function (Certificate $record): void {
                        $record->update(['revoked_at' => now()]);

                        Notification::make()->title(__t('admin_results.certificates.revoked'))->warning()->send();
                    }),

                Action::make('restore')
                    ->label(__t('admin_results.certificates.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => ! $record->isValid())
                    ->action(function (Certificate $record): void {
                        $record->update(['revoked_at' => null]);

                        Notification::make()->title(__t('admin_results.certificates.restored'))->success()->send();
                    }),
            ]);
    }

    /** Stream all certificates as a CSV (synchronous — no queue needed), headed in the exporter's language. */
    protected static function exportCsv(): StreamedResponse
    {
        $filename = 'certificates-'.now()->format('Y-m-d').'.csv';

        $headers = [
            __t('admin_common.number'),
            __t('admin_common.student'),
            __t('admin_common.email'),
            __t('admin_common.partner'),
            __t('admin_common.course'),
            __t('admin_results.certificates.score_percent'),
            __t('admin_common.attempts'),
            __t('admin_common.issued'),
            __t('admin_common.status'),
        ];

        return response()->streamDownload(function () use ($headers): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            Certificate::with('user.company', 'course')
                ->orderByDesc('issued_at')
                ->chunk(200, function ($certificates) use ($out): void {
                    foreach ($certificates as $certificate) {
                        $attempts = QuizAttempt::where('user_id', $certificate->user_id)
                            ->where('course_id', $certificate->course_id)
                            ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED])
                            ->count();

                        fputcsv($out, [
                            $certificate->number,
                            $certificate->name,
                            $certificate->user?->email,
                            $certificate->user?->company?->name,
                            $certificate->course?->title,
                            $certificate->score_percent,
                            $attempts,
                            $certificate->issued_at?->format('Y-m-d'),
                            $certificate->statusLabel(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
