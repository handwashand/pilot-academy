<?php

namespace App\Http\Controllers;

use App\Filament\Pages\Changelog;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * What's new as a PDF — one release, or all of them.
 *
 * Release notes get circulated to people without a panel account, and "take a
 * screenshot" is what happens when the product does not offer this.
 *
 * The releases come from the page's own parser, not a second reading of
 * CHANGELOG.md, so the file can never disagree with the screen it was printed
 * from. Rendered on the spot rather than stored: it is a few pages of text.
 */
class ChangelogPdfController extends Controller
{
    public function __invoke(Request $request, ?string $release = null): Response
    {
        // The same audience as the page: whoever may use the panel.
        abort_unless($request->user()?->canAccessPanel(Filament::getPanel('admin')) ?? false, 403);

        $releases = Changelog::releasesFrom(Changelog::changelogPath());

        if ($release !== null) {
            $releases = array_values(array_filter(
                $releases,
                fn (array $candidate): bool => $candidate['id'] === $release,
            ));
        }

        abort_if($releases === [], 404);

        $filename = $release !== null
            ? "whats-new-{$release}.pdf"
            : 'whats-new-'.now()->format('Y-m-d').'.pdf';

        return Pdf::loadView('changelog.pdf', [
            'releases' => $releases,
            'single' => $release !== null,
        ])
            ->setPaper('a4', 'portrait')
            // The Laravel wrapper turns subsetting off, which embeds all of
            // DejaVu Sans: 1.5 MB for a few pages of text. Subset, and it is
            // a size you can attach to an email.
            ->setOption('isFontSubsettingEnabled', true)
            ->download($filename);
    }
}
