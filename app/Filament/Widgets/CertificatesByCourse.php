<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CertificatesByCourse extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    /** Learner data — creators have no business seeing it. */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Certificates issued by course')
            ->query(
                Course::query()
                    ->where('final_quiz_enabled', true)
                    ->withCount([
                        // Admins get a real certificate when they preview a final
                        // quiz; those are not learner results, so they are left out.
                        'certificates as issued_count' => fn (Builder $q) => $q
                            ->whereNull('revoked_at')
                            ->whereHas('user', fn (Builder $user) => $user->learners()),
                    ])
            )
            ->defaultSort('issued_count', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Course')
                    ->weight('bold'),

                TextColumn::make('issued_count')
                    ->label('Certificates issued')
                    ->badge()
                    ->color('success'),
            ])
            ->paginated([5, 10, 25]);
    }
}
