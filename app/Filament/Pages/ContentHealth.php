<?php

namespace App\Filament\Pages;

use App\Actions\FindContentProblems;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * Everything broken for students, in one place, with a count in the sidebar.
 *
 * This used to be a card across the top of the dashboard. It moved here because
 * it is work for whoever owns the content, not a figure for everyone who opens
 * the dashboard — and the red badge beside Content health is visible from every
 * screen, which the card never was. The same problems are also flagged on the
 * Courses and Lessons lists, on the edit page itself, and before publishing.
 */
class ContentHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?string $navigationLabel = 'Content health';

    protected static ?string $title = 'Content health';

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.content-health';

    /** Whoever can edit content: admins, and creators for their own products. */
    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->isCreator());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FindContentProblems::forCurrentUser()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Broken for students right now';
    }

    public function problems(): Collection
    {
        return FindContentProblems::forCurrentUser();
    }
}
