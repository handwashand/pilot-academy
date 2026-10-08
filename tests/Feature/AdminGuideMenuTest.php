<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin guide's "menu at a glance" table has to name the menu that exists.
 *
 * The guide is read by people clicking around the real panel, so a renamed or
 * new menu item that the table misses sends them looking for something that is
 * not there. This compares the table with what Filament actually registers —
 * labels exactly as the sidebar prints them, grouped items as "Group → Item".
 *
 * It will not notice a renamed button inside a screen. Check those by hand.
 */
class AdminGuideMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guide_menu_table_lists_exactly_the_sidebar_items(): void
    {
        $this->actingAs(User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]));

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);

        $sidebar = collect([...$panel->getResources(), ...$panel->getPages()])
            ->unique()
            // A clustered resource/page (Settings → Profile/Integrations/Mail/
            // Translations/Languages) never registers in the main sidebar —
            // Filament skips it in favour of the cluster's own tab strip, no
            // matter what shouldRegisterNavigation() says. The cluster itself
            // (App\Filament\Clusters\Settings) is a Page too and is in this
            // same list, carrying no $cluster of its own.
            ->filter(fn (string $class): bool => $class::shouldRegisterNavigation() && blank($class::getCluster()))
            ->map(function (string $class): string {
                $group = $class::getNavigationGroup();
                $label = $class::getNavigationLabel();

                return $group ? "{$group} → {$label}" : $label;
            })
            ->sort()
            ->values()
            ->all();

        $this->assertSame(
            $sidebar,
            $this->guideMenuTable(),
            'docs/admin-guide.md section 2 must list every sidebar item, spelled as the sidebar spells it.',
        );
    }

    /** The bold first cell of each row in the guide's "menu at a glance" table. */
    private function guideMenuTable(): array
    {
        $guide = file_get_contents(base_path('docs/admin-guide.md'));

        preg_match('/^## 2\. The menu at a glance\n(.*?)^---$/ms', $guide, $section);
        $this->assertNotEmpty($section, 'The guide has no "## 2. The menu at a glance" section.');

        preg_match_all('/^\| \*\*(.+?)\*\* \|/m', $section[1], $rows);

        return collect($rows[1])->sort()->values()->all();
    }
}
