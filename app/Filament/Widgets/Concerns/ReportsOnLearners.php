<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared scope for every dashboard widget that reports on student progress.
 *
 * Two rules live here rather than in each widget: the numbers only ever count
 * learners, and only admins may look at them. Repeating either in a dozen
 * places is how a stat added next month quietly starts counting staff, or
 * shows a creator data about people outside their remit.
 */
trait ReportsOnLearners
{
    /** Learner records are staff-visible data — admins only, never creators. */
    public static function canView(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    /**
     * The only entry point to learner rows. Every figure on the dashboard is
     * built from this, so "learners, not staff" is decided once.
     */
    protected function learners(): Builder
    {
        return User::query()->learners();
    }

    /**
     * Restrict a query to learners by its user_id column. Use for tables that
     * hang off users — completions, attempts, certificates — so staff activity
     * never lands in a student report.
     */
    protected function scopeToLearners(Builder|\Illuminate\Database\Query\Builder $query, string $column = 'user_id'): Builder|\Illuminate\Database\Query\Builder
    {
        return $query->whereIn($column, User::query()->learners()->select('id'));
    }
}
