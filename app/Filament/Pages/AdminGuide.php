<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class AdminGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Guide';

    protected static ?string $title = 'Admin guide';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.admin-guide';

    /** Render the committed Markdown guide as HTML. */
    public function getGuideHtml(): string
    {
        $path = base_path('docs/admin-guide.md');

        if (! is_file($path)) {
            return '<p>The guide file <code>docs/admin-guide.md</code> was not found.</p>';
        }

        return Str::markdown(file_get_contents($path));
    }
}
