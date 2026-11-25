<?php

namespace Tests\Feature;

use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Filament\Resources\Tutorials\Pages\EditTutorial;
use App\Filament\Resources\Tutorials\Pages\ListTutorials;
use App\Filament\Resources\Webinars\Pages\EditWebinar;
use App\Filament\Resources\Webinars\Pages\ListWebinars;
use App\Models\CaseStudy;
use App\Models\Tutorial;
use App\Models\User;
use App\Models\Webinar;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Case studies, tutorials and webinars read in the panel language, as courses
 * and lessons do (AdminReadingLanguageTest): a Spanish reviewer sees and
 * corrects the Spanish, and a save in Spanish never writes over the English.
 */
class ContentReadingLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
    }

    private function admin(string $locale): User
    {
        app()->setLocale($locale); // Livewire::test() runs no SetLocale middleware.

        return User::create([
            'name' => 'Content Reviewer',
            'email' => "reviewer-{$locale}@pilot.test",
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'locale' => $locale,
        ]);
    }

    private function tutorial(): Tutorial
    {
        $tutorial = Tutorial::create([
            'title' => 'Adding your first object',
            'slug' => 'adding-your-first-object',
            'summary' => 'Five minutes, start to finish.',
            'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            'language' => 'en',
        ]);
        $tutorial->setTranslation('title', 'es', 'Añadir tu primer objeto');

        return $tutorial;
    }

    private function webinar(): Webinar
    {
        $webinar = Webinar::create([
            'title' => 'Fuel reports live',
            'slug' => 'fuel-reports-live',
            'summary' => 'A walk through the fuel report.',
            'description' => '<p>Bring your questions.</p>',
            'language' => 'en',
        ]);
        $webinar->setTranslation('title', 'es', 'Informes de combustible en directo');

        return $webinar;
    }

    private function study(): CaseStudy
    {
        $study = CaseStudy::create([
            'title' => 'Arrival monitoring with geofences',
            'slug' => 'arrival-monitoring-geofences',
            'short_problem' => 'Monitor arrivals at customer sites.',
            'difficulty' => CaseStudy::DIFFICULTY_INTERMEDIATE,
            'scenario_problem' => '<p>Scenario.</p>',
            'desired_outcome' => '<p>Outcome.</p>',
            'prerequisites' => '<p>Prerequisites.</p>',
            'configuration_steps' => '<ol><li>Configure GeoZones.</li></ol>',
            'testing_verification' => '<p>Verify in History.</p>',
            'source_note' => 'Verified against Academy lessons.',
            'language' => 'en',
        ]);
        $study->setTranslation('title', 'es', 'Control de llegadas con geocercas');

        return $study;
    }

    public function test_a_tutorial_is_shown_and_saved_in_spanish(): void
    {
        $tutorial = $this->tutorial();

        Livewire::actingAs($this->admin('es'))
            ->test(EditTutorial::class, ['record' => $tutorial->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Añadir tu primer objeto'])
            // No Spanish summary yet: what a Spanish student would read.
            ->assertSchemaStateSet(['summary' => 'Five minutes, start to finish.'])
            ->assertSee(__t('admin_common.reading.notice', ['language' => 'Español', 'original' => 'English']))
            ->fillForm(['title' => 'Añade tu primer objeto'])
            ->call('save')
            ->assertHasNoFormErrors();

        $tutorial = $tutorial->fresh();
        $this->assertSame('Adding your first object', $tutorial->title);
        $this->assertSame('Añade tu primer objeto', $tutorial->translated('title', 'es'));
        // Left as the English it showed: not copied in as "Spanish".
        $this->assertSame(0, $tutorial->contentTranslations()->where('field', 'summary')->count());
    }

    public function test_a_webinar_is_shown_and_saved_in_spanish(): void
    {
        $webinar = $this->webinar();

        Livewire::actingAs($this->admin('es'))
            ->test(EditWebinar::class, ['record' => $webinar->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Informes de combustible en directo'])
            ->fillForm(['title' => 'Informes de combustible, en directo'])
            ->call('save')
            ->assertHasNoFormErrors();

        $webinar = $webinar->fresh();
        $this->assertSame('Fuel reports live', $webinar->title);
        $this->assertSame('Informes de combustible, en directo', $webinar->translated('title', 'es'));
        $this->assertSame(0, $webinar->contentTranslations()->where('field', 'description')->count());
    }

    public function test_a_case_study_is_shown_and_saved_in_spanish(): void
    {
        $study = $this->study();

        Livewire::actingAs($this->admin('es'))
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Control de llegadas con geocercas'])
            ->assertFormFieldVisible('prerequisites')
            ->fillForm(['title' => 'Control de llegadas con geocercas (GeoZones)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $study = $study->fresh();
        $this->assertSame('Arrival monitoring with geofences', $study->title);
        $this->assertSame('Control de llegadas con geocercas (GeoZones)', $study->translated('title', 'es'));
        $this->assertSame(0, $study->contentTranslations()->where('field', 'scenario_problem')->count());
    }

    public function test_deleting_a_step_from_a_translation_only_hides_it(): void
    {
        // Delete clears the original's own column; from a translation the
        // dialog offers Hide only, and a forged Delete still only hides.
        $study = $this->study();

        Livewire::actingAs($this->admin('es'))
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->callAction(TestAction::make('remove_prerequisites')->schemaComponent()->arguments(['delete' => true]))
            ->assertFormFieldDoesNotExist('prerequisites');

        $this->assertSame('<p>Prerequisites.</p>', $study->fresh()->prerequisites);
    }

    public function test_an_english_reviewer_still_edits_the_original(): void
    {
        $tutorial = $this->tutorial();

        Livewire::actingAs($this->admin('en'))
            ->test(EditTutorial::class, ['record' => $tutorial->getRouteKey()])
            ->assertSchemaStateSet(['title' => 'Adding your first object'])
            ->fillForm(['title' => 'Adding your first object, step by step'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Adding your first object, step by step', $tutorial->fresh()->title);
        $this->assertSame('Añadir tu primer objeto', $tutorial->fresh()->translated('title', 'es'));
    }

    public function test_the_lists_show_spanish_titles_and_find_them(): void
    {
        $tutorial = $this->tutorial();
        $webinar = $this->webinar();
        $study = $this->study();
        $admin = $this->admin('es');

        Livewire::actingAs($admin)->test(ListTutorials::class)
            ->assertSee('Añadir tu primer objeto')
            ->assertDontSee('Adding your first object')
            ->searchTable('primer objeto')
            ->assertCanSeeTableRecords([$tutorial]);

        Livewire::actingAs($admin)->test(ListWebinars::class)
            ->assertSee('Informes de combustible en directo')
            ->searchTable('en directo')
            ->assertCanSeeTableRecords([$webinar]);

        Livewire::actingAs($admin)->test(ListCaseStudies::class)
            ->assertSee('Control de llegadas con geocercas')
            ->searchTable('geocercas')
            ->assertCanSeeTableRecords([$study]);
    }

    public function test_the_tutorial_and_webinar_forms_have_a_settings_column(): void
    {
        $admin = $this->admin('en');

        foreach ([[EditTutorial::class, $this->tutorial()], [EditWebinar::class, $this->webinar()]] as [$page, $record]) {
            Livewire::actingAs($admin)
                ->test($page, ['record' => $record->getRouteKey()])
                ->assertOk()
                ->assertSeeInOrder([__t('admin_common.title'), __t('admin_nav.groups.settings'), __t('admin_common.status')]);
        }
    }

    public function test_iphones_get_a_png_home_screen_icon(): void
    {
        // iOS ignores an SVG touch icon and shows a screenshot instead.
        $path = public_path('img/apple-touch-icon.png');
        $this->assertFileExists($path);
        $this->assertSame([180, 180], array_slice(getimagesize($path), 0, 2));

        $this->get(route('academy.home'))
            ->assertOk()
            ->assertSee('rel="apple-touch-icon" sizes="180x180" href="'.asset('img/apple-touch-icon.png'), false);
    }
}
