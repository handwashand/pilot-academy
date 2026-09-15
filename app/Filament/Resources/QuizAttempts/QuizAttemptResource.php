<?php

namespace App\Filament\Resources\QuizAttempts;

use App\Filament\Resources\QuizAttempts\Pages\ListQuizAttempts;
use App\Filament\Resources\QuizAttempts\Tables\QuizAttemptsTable;
use App\Models\QuizAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Every learner's quiz attempts, and the one decision they create: a student
 * who has run out of attempts at a quiz they have not passed.
 *
 * Before this, helping one such student meant raising Max attempts on the
 * course or lesson for everybody. "Grant another attempt" gives one student
 * one more try and records who granted it and why.
 */
class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.results');
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.quiz_attempts.nav');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.quiz_attempts.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.quiz_attempts.many');
    }

    /**
     * Learner records — admins only, like every learner report here. Checked on
     * the resource rather than a model policy, so the attempts tab on a user's
     * page keeps its own rules.
     */
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Learners only: staff previewing a quiz earn real attempts. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user.company', 'course', 'lesson.course'])
            ->whereHas('user', fn (Builder $user) => $user->learners());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = QuizAttempt::stuckLearners()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __t('admin_nav.quiz_attempts.badge');
    }

    public static function table(Table $table): Table
    {
        return QuizAttemptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuizAttempts::route('/'),
        ];
    }
}
