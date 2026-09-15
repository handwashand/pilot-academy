<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__t('admin_common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label(__t('admin_common.email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('company.name')
                    ->label(__t('admin_common.company'))
                    ->badge()
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('role')
                    ->label(__t('admin_people.users.role'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => User::roleLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_CREATOR => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('products.name')
                    ->label(__t('admin_common.products'))
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('completed_lessons_count')
                    ->label(__t('admin_people.users.lessons_done'))
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('last_login_at')
                    ->label(__t('admin_people.users.last_login'))
                    ->dateTime('d M Y, H:i')
                    ->since()
                    ->placeholder(__t('admin_common.never'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__t('admin_common.added'))
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__t('admin_common.company'))
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('role')
                    ->label(__t('admin_people.users.role'))
                    ->options(User::roleLabels()),

                SelectFilter::make('products')
                    ->label(__t('admin_people.users.product_filter'))
                    ->relationship('products', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Action::make('exportProgress')
                    ->label(__t('admin_people.users.export'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn (): StreamedResponse => static::exportProgress()),
            ])
            ->recordActions([
                Action::make('accessLink')
                    ->label(__t('admin_people.users.access_link'))
                    ->icon('heroicon-o-link')
                    ->color('gray')
                    ->visible(fn (User $record): bool => $record->isLearner())
                    ->modalHeading(__t('admin_people.users.access_link_heading'))
                    ->modalContent(fn (User $record) => view('filament.access-link', [
                        'url' => $record->accessUrl(),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__t('admin_common.close')),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Every learner and how far they have got, for a spreadsheet.
     *
     * Learners only, like every other report — a row per admin or creator would
     * be noise at best and would skew whatever the reader totals up. The header
     * row is in the language of whoever exports it.
     */
    protected static function exportProgress(): StreamedResponse
    {
        $filename = 'learner-progress-'.now()->format('Y-m-d').'.csv';

        $headers = collect(['name', 'email', 'partner', 'lessons_completed', 'certificates', 'last_activity', 'last_login', 'joined'])
            ->map(fn (string $column): string => __t("admin_people.users.csv.{$column}"))
            ->all();

        return response()->streamDownload(function () use ($headers): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);

            User::query()
                ->learners()
                ->with('company')
                ->withCount([
                    'completedLessons',
                    'certificates as valid_certificates_count' => fn ($query) => $query->whereNull('revoked_at'),
                ])
                ->addSelect(['last_completed_at' => DB::table('lesson_user')
                    ->selectRaw('max(completed_at)')
                    ->whereColumn('lesson_user.user_id', 'users.id'),
                ])
                ->orderBy('name')
                // Chunked so a large partner list never loads at once.
                ->chunk(200, function ($learners) use ($out): void {
                    foreach ($learners as $learner) {
                        fputcsv($out, [
                            $learner->name,
                            $learner->email,
                            $learner->company?->name,
                            $learner->completed_lessons_count,
                            $learner->valid_certificates_count,
                            $learner->last_completed_at,
                            $learner->last_login_at?->format('Y-m-d H:i'),
                            $learner->created_at?->format('Y-m-d'),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
