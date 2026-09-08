<?php

namespace Tests\Feature;

use App\Filament\Pages\Changelog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangelogPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The whole page is this one file. If it moves and nothing notices, the
     * page quietly renders its empty state in production.
     */
    public function test_the_changelog_file_exists_where_the_page_looks_for_it(): void
    {
        $this->assertFileExists(Changelog::changelogPath());
        $this->assertSame(base_path('docs/CHANGELOG.md'), Changelog::changelogPath());
    }

    public function test_the_changelog_splits_into_releases_newest_first(): void
    {
        $markdown = file_get_contents(Changelog::changelogPath());

        preg_match_all('/^## (.+)$/m', $markdown, $headings);

        $releases = Changelog::parse($markdown);

        $this->assertNotEmpty($releases, 'The changelog should parse into at least one release.');
        $this->assertSame(
            array_map('trim', $headings[1]),
            array_column($releases, 'title'),
            'Releases must come back in file order — the file is written newest first.',
        );
    }

    public function test_a_release_splits_into_categorised_items(): void
    {
        $releases = Changelog::parse(<<<'MD'
            # What's new

            ## September 2026

            ### Added
            - **A thing.** Which is explained here,
              over more than one line.
            - **Another thing.**

            ### Known issues
            - Something does not work yet.

            ---

            ## August 2026

            ### Fixed
            - An older fix.
            MD);

        $this->assertCount(2, $releases);
        $this->assertSame('September 2026', $releases[0]['title']);

        [$added, $limitations] = $releases[0]['sections'];

        $this->assertSame('added', $added['type']);
        $this->assertSame('Added', $added['label']);
        $this->assertCount(2, $added['items']);
        $this->assertStringContainsString('over more than one line', $added['items'][0]['text']);

        // "Known issues" is one of the accepted spellings of the same category.
        $this->assertSame('limitations', $limitations['type']);
        $this->assertSame('Known limitations', $limitations['label']);
    }

    /**
     * A heading that is not one of the four known categories still has to
     * render — as a generic type that keeps its own heading text. Most of this
     * project's own entries are written that way.
     */
    public function test_an_unknown_heading_keeps_its_own_text(): void
    {
        $releases = Changelog::parse(<<<'MD'
            ## September 2026

            ### Nudge students who have gone quiet

            A paragraph with no bullets in it at all.
            MD);

        $section = $releases[0]['sections'][0];

        $this->assertSame('other', $section['type']);
        $this->assertSame('Nudge students who have gone quiet', $section['label']);
        // A section the parser cannot split into bullets falls back to one
        // block rather than being dropped.
        $this->assertCount(1, $section['items']);
        $this->assertStringContainsString('no bullets in it at all', $section['items'][0]['text']);
    }

    public function test_html_comments_are_not_rendered(): void
    {
        $releases = Changelog::parse(<<<'MD'
            ## September 2026

            <!-- Authoring note: do not ship this sentence. -->

            ### Added
            - A visible change. <!-- and a trailing note -->
            MD);

        $html = collect($releases)
            ->flatMap(fn (array $release) => $release['sections'])
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('html')
            ->implode('');

        $this->assertStringContainsString('A visible change.', $html);
        $this->assertStringNotContainsString('do not ship this sentence', $html);
        $this->assertStringNotContainsString('trailing note', $html);
        $this->assertStringNotContainsString('<!--', $html);
    }

    /** The trailing rule between releases is structure, not content. */
    public function test_the_release_separator_is_not_rendered_as_a_rule(): void
    {
        $releases = Changelog::parse(<<<'MD'
            ## September 2026

            ### Added
            - A change.

            ---
            MD);

        $this->assertStringNotContainsString('<hr', $releases[0]['sections'][0]['items'][0]['html']);
    }

    public function test_a_missing_changelog_yields_no_sections_and_does_not_error(): void
    {
        $this->assertSame([], Changelog::releasesFrom(base_path('docs/no-such-changelog.md')));
        $this->assertSame([], Changelog::parse(''));
        // A file with prose but no `##` headings is the same empty case.
        $this->assertSame([], Changelog::parse("# What's new\n\nNothing here yet.\n"));
    }

    public function test_the_page_renders_for_an_admin(): void
    {
        $this->actingAs($this->user('admin@pilot.local', User::ROLE_ADMIN))
            ->get('/admin/changelog')
            ->assertStatus(200)
            ->assertSee('final quiz');
    }

    public function test_the_page_renders_for_a_creator(): void
    {
        $this->actingAs($this->user('creator@pilot.local', User::ROLE_CREATOR))
            ->get('/admin/changelog')
            ->assertStatus(200);
    }

    public function test_students_cannot_open_the_page(): void
    {
        $this->actingAs($this->user('student@example.com', User::ROLE_LEARNER))
            ->get('/admin/changelog')
            ->assertStatus(403);
    }

    /**
     * The route and class are still "changelog" so old links keep working, so
     * the label is the only thing telling anyone what this page is. Asserted
     * on the rendered sidebar, not just the property, because that is where
     * people read it.
     */
    public function test_the_navigation_entry_is_labelled_whats_new(): void
    {
        $this->assertSame("What's new", Changelog::getNavigationLabel());

        // The sidebar link itself, not just the page heading. Note there is no
        // point asserting the word "Changelog" is absent: the class is still
        // called Changelog and Livewire puts the component's name in its
        // snapshot, so it is in the markup no matter what the label says.
        $this->actingAs($this->user('nav@pilot.local', User::ROLE_ADMIN))
            ->get('/admin/changelog')
            ->assertStatus(200)
            ->assertSee('href="'.Changelog::getUrl().'"', escape: false)
            ->assertSee("What's new");
    }

    /**
     * The panel's own stylesheet carries no Tailwind utility layer, so every
     * utility class on this page depends on the custom theme. Remove any one
     * of these four and the page renders unstyled with no error at all.
     */
    public function test_the_panel_has_a_custom_theme_that_scans_our_own_views(): void
    {
        $theme = 'resources/css/filament/admin/theme.css';

        $this->assertFileExists(base_path($theme));

        $this->assertStringContainsString(
            $theme,
            file_get_contents(base_path('vite.config.js')),
            'The theme must be in the Vite input array or it is never built.',
        );

        $this->assertStringContainsString(
            "viteTheme('{$theme}')",
            file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php')),
            'The panel must register the theme or it is never loaded.',
        );

        // Filament's theme entry opens with `@import 'tailwindcss' source(none)`,
        // which switches content detection off. Without these the build still
        // succeeds and still produces a stylesheet that has never seen our markup.
        $css = file_get_contents(base_path($theme));
        $this->assertStringContainsString("@source '../../../../app/Filament/**/*.php';", $css);
        $this->assertStringContainsString("@source '../../../../resources/views/filament/**/*.blade.php';", $css);
    }

    private function user(string $email, string $role): User
    {
        return User::create([
            'name' => 'Test '.$role,
            'email' => $email,
            'password' => bcrypt('password'),
            'role' => $role,
        ]);
    }
}
