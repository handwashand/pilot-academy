<?php

namespace App\Filament\Resources\Certificates\Tables;

use App\Actions\IssueCertificate;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
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
                    ->label('Number')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Student')
                    ->description(fn (Certificate $record): ?string => $record->user?->email)
                    ->searchable(),

                TextColumn::make('user.company.name')
                    ->label('Partner')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('course.title')
                    ->label('Course')
                    ->sortable(),

                TextColumn::make('score_percent')
                    ->label('Score')
                    ->formatStateUsing(fn ($state): string => "{$state}%")
                    ->sortable(),

                TextColumn::make('attempts')
                    ->label('Attempts')
                    ->badge()
                    ->getStateUsing(fn (Certificate $record): int => QuizAttempt::where('user_id', $record->user_id)
                        ->where('course_id', $record->course_id)
                        ->whereIn('status', [QuizAttempt::STATUS_PASSED, QuizAttempt::STATUS_FAILED])
                        ->count()),

                TextColumn::make('issued_at')
                    ->label('Issued')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->getStateUsing(fn (Certificate $record): string => $record->statusLabel())
                    ->color(fn (Certificate $record): string => $record->isValid() ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('course')
                    ->relationship('course', 'title')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('company')
                    ->label('Partner')
                    ->options(fn (): array => Company::orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'],
                        fn (Builder $q, $companyId) => $q->whereHas('user', fn (Builder $u) => $u->where('company_id', $companyId)),
                    )),

                SelectFilter::make('status')
                    ->options(['valid' => 'Valid', 'revoked' => 'Revoked'])
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
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (): StreamedResponse => static::exportCsv()),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Certificate $record): bool => $record->pdf_path && Storage::disk('public')->exists($record->pdf_path))
                    ->action(fn (Certificate $record) => Storage::disk('public')->download(
                        $record->pdf_path,
                        'certificate-'.$record->number.'.pdf'
                    )),

                Action::make('resend')
                    ->label('Resend email')
                    ->icon('heroicon-o-envelope')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription(fn (Certificate $record): string => "Email the certificate to {$record->user?->email}.")
                    ->action(function (Certificate $record, IssueCertificate $issue): void {
                        if (! $record->pdf_path || ! Storage::disk('public')->exists($record->pdf_path)) {
                            $issue->renderPdf($record);
                        }
                        $issue->email($record);

                        Notification::make()->title('Certificate emailed')->success()->send();
                    }),

                Action::make('regenerate')
                    ->label('Regenerate PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function (Certificate $record, IssueCertificate $issue): void {
                        $issue->renderPdf($record);

                        Notification::make()->title('PDF regenerated')->success()->send();
                    }),

                Action::make('revoke')
                    ->label('Revoke')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('The certificate will show as revoked on the public verification page. Certificates are permanent — use this only for an incorrect issue.')
                    ->visible(fn (Certificate $record): bool => $record->isValid())
                    ->action(function (Certificate $record): void {
                        $record->update(['revoked_at' => now()]);

                        Notification::make()->title('Certificate revoked')->warning()->send();
                    }),

                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => ! $record->isValid())
                    ->action(function (Certificate $record): void {
                        $record->update(['revoked_at' => null]);

                        Notification::make()->title('Certificate restored')->success()->send();
                    }),
            ]);
    }

    /** Stream all certificates as a CSV (synchronous — no queue needed). */
    protected static function exportCsv(): StreamedResponse
    {
        $filename = 'certificates-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Number', 'Student', 'Email', 'Partner', 'Course', 'Score %', 'Attempts', 'Issued', 'Status']);

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
