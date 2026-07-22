<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class Changelog extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Changelog';

    protected static ?string $title = "What's new";

    protected static ?int $navigationSort = 100;

    protected string $view = 'filament.pages.changelog';

    /** Render the committed Markdown changelog as HTML. */
    public function getChangelogHtml(): string
    {
        $path = base_path('docs/CHANGELOG.md');

        if (! is_file($path)) {
            return '<p>No changelog yet.</p>';
        }

        return Str::markdown(file_get_contents($path));
    }
}
