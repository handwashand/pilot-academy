<?php

namespace Tests\Feature;

use App\Filament\Resources\CaseStudies\CaseStudyResource;
use App\Filament\Resources\CaseStudies\Pages\CreateCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\EditCaseStudy;
use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Models\ActivityEvent;
use App\Models\CaseStudy;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\Hidden;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CaseStudyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function learner(): User
    {
        return User::create([
            'name' => 'Partner Learner',
            'email' => 'learner@partner.test',
            'password' => bcrypt('password'),
            'role' => User::ROLE_LEARNER,
        ]);
    }

    private function creatorFor(Product $product): User
    {
        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CREATOR,
        ]);

        $creator->products()->attach($product);

        return $creator;
    }

    private function courseWithLesson(Product $product): array
    {
        $course = Course::create([
            'product_id' => $product->id,
            'title' => 'Pilot Monitoring',
            'slug' => 'pilot-monitoring',
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        $lesson = $course->lessons()->create([
            'title' => 'Working with History',
            'slug' => 'working-with-history',
            'summary' => 'Review tracks and events.',
            'content' => '<p>History body.</p>',
            'status' => Lesson::STATUS_PUBLISHED,
        ]);

        return [$course, $lesson];
    }

    private function completeStudy(array $overrides = []): CaseStudy
    {
        return CaseStudy::create([
            'title' => 'Delivery arrival and departure monitoring with geofences',
            'slug' => 'delivery-arrival-departure-monitoring-geofences',
            'short_problem' => 'Monitor arrivals and departures at known customer sites.',
            'industry' => 'Delivery',
            'features_used' => ['GeoZones', 'History', 'Reports'],
            'difficulty' => CaseStudy::DIFFICULTY_INTERMEDIATE,
            'implementation_time' => '2-4 hours',
            'scenario_problem' => '<p>Scenario.</p>',
            'desired_outcome' => '<p>Outcome.</p>',
            'prerequisites' => '<p>Prerequisites.</p>',
            'pilot_features' => '<p>Features.</p>',
            'configuration_steps' => '<ol><li>Configure GeoZones.</li></ol>',
            'testing_verification' => '<p>Verify in History.</p>',
            'expected_results' => '<p>Expected results.</p>',
            'troubleshooting' => '<p>Troubleshooting.</p>',
            'adaptation' => '<p>Adaptation.</p>',
            'source_note' => 'Verified against existing Academy lessons.',
            'performance_claim_note' => 'No quantified performance claim.',
            'is_anonymized' => true,
            ...$overrides,
        ]);
    }

    public function test_case_studies_are_linked_from_the_academy_and_filterable(): void
    {
        $product = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        [, $lesson] = $this->courseWithLesson($product);
        $study = $this->completeStudy([
            'product_id' => $product->id,
            'related_lesson_ids' => [$lesson->id],
            'status' => CaseStudy::STATUS_PUBLISHED,
        ]);
        $this->completeStudy([
            'title' => 'Fuel event investigation',
            'slug' => 'fuel-event-investigation',
            'industry' => 'Fuel logistics',
            'features_used' => ['Fuel sensors'],
            'difficulty' => CaseStudy::DIFFICULTY_ADVANCED,
            'status' => CaseStudy::STATUS_PUBLISHED,
        ]);

        $this->get(route('academy.home'))
            ->assertOk()
            ->assertSee('Case Studies');

        $this->get(route('academy.case-studies.index', [
            'industry' => 'Delivery',
            'feature' => 'GeoZones',
            'difficulty' => CaseStudy::DIFFICULTY_INTERMEDIATE,
        ]))
            ->assertOk()
            ->assertSee($study->title)
            ->assertDontSee('Fuel event investigation');
    }

    public function test_case_study_detail_shows_sections_and_related_lessons(): void
    {
        $product = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        [, $lesson] = $this->courseWithLesson($product);
        $study = $this->completeStudy([
            'status' => CaseStudy::STATUS_PUBLISHED,
            'related_lesson_ids' => [$lesson->id],
            'related_links' => [['title' => 'Pilot docs', 'url' => 'https://example.com/docs']],
        ]);

        $this->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Customer scenario and problem')
            ->assertSee('Step-by-step configuration')
            ->assertSee('Related Academy lessons and documentation')
            ->assertSee('Working with History')
            ->assertSee('Pilot docs');
    }

    public function test_a_signed_in_case_study_open_is_recorded_with_its_stable_id(): void
    {
        $product = Product::create(['name' => 'PTM', 'slug' => 'tracked-ptm']);
        $study = $this->completeStudy([
            'product_id' => $product->id,
            'status' => CaseStudy::STATUS_PUBLISHED,
        ]);
        $learner = $this->learner();

        $this->actingAs($learner)
            ->get(route('academy.case-studies.show', $study))
            ->assertOk();

        $this->assertDatabaseHas('activity_events', [
            'user_id' => $learner->id,
            'type' => ActivityEvent::TYPE_CASE_STUDY_OPENED,
            'subject_type' => $study->getMorphClass(),
            'subject_id' => $study->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_unpublished_studies_are_hidden_from_learners_but_previewable_by_editors(): void
    {
        $product = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        $study = $this->completeStudy(['product_id' => $product->id]);

        $this->get(route('academy.case-studies.index'))
            ->assertOk()
            ->assertDontSee($study->title);

        $this->actingAs($this->learner())
            ->get(route('academy.case-studies.show', $study))
            ->assertNotFound();

        $this->actingAs($this->creatorFor($product))
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('learners cannot see this page');
    }

    public function test_editor_can_publish_a_complete_case_study(): void
    {
        $study = $this->completeStudy();

        Livewire::actingAs($this->admin())
            ->test(ListCaseStudies::class)
            ->callAction(TestAction::make('publish')->table($study))
            ->assertHasNoActionErrors();

        $this->assertSame(CaseStudy::STATUS_PUBLISHED, $study->fresh()->status);

        $this->get(route('academy.case-studies.show', $study->fresh()))
            ->assertOk()
            ->assertSee($study->title);
    }

    public function test_editor_cannot_publish_an_incomplete_case_study(): void
    {
        $study = CaseStudy::create([
            'title' => 'Incomplete',
            'slug' => 'incomplete',
            'short_problem' => 'Missing required sections.',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ListCaseStudies::class)
            ->callAction(TestAction::make('publish')->table($study));

        $this->assertSame(CaseStudy::STATUS_DRAFT, $study->fresh()->status);
    }

    /**
     * A partner reading in Russian gets the listing, the filters and the ten
     * study headings in Russian — the studies' own text stays as it was written.
     */
    public function test_the_case_study_pages_read_in_the_partners_language(): void
    {
        $this->seed(LanguageSeeder::class);
        $study = $this->completeStudy(['status' => CaseStudy::STATUS_PUBLISHED]);

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get(route('academy.case-studies.index'))
            ->assertOk()
            ->assertSee('Практические примеры')
            ->assertSee('Отрасль')
            ->assertSee('Все функции')
            ->assertSee('Средний')
            ->assertDontSee('All industries');

        $this->withHeader('Accept-Language', 'fr')
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Situation et problème du client')
            ->assertSee('Configuration pas à pas')
            ->assertSee('Détails de l’étude', false)
            ->assertDontSee('Step-by-step configuration');
    }

    /**
     * Industry and the time estimate are short authored text, like the title —
     * they used to always show in the study's own language. Translate now
     * covers them too, so a reader sees them translated once an editor has.
     */
    public function test_industry_and_implementation_time_can_be_translated(): void
    {
        $this->seed(LanguageSeeder::class);
        $study = $this->completeStudy([
            'industry' => 'Delivery',
            'implementation_time' => '2-4 hours',
            'status' => CaseStudy::STATUS_PUBLISHED,
        ]);

        Livewire::actingAs($this->admin())
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->callAction('translateContent', data: [
                'fr' => ['industry' => 'Livraison', 'implementation_time' => '2 à 4 heures'],
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('Livraison', $study->fresh()->translated('industry', 'fr'));
        $this->assertSame('2 à 4 heures', $study->fresh()->translated('implementation_time', 'fr'));

        $this->withHeader('Accept-Language', 'fr')
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Livraison')
            ->assertSee('2 à 4 heures')
            ->assertDontSee('2-4 hours');

        // No translation yet: the reader sees the original, as before.
        $this->withHeader('Accept-Language', 'ru')
            ->get(route('academy.case-studies.index', ['industry' => 'Delivery']))
            ->assertOk()
            ->assertSee('Delivery');
    }

    /** The editor's screens follow the editor's language, like every other resource. */
    public function test_the_editor_screens_read_in_the_editors_language(): void
    {
        $this->seed(LanguageSeeder::class);
        $admin = $this->admin();
        $admin->forceFill(['locale' => 'ru'])->save();

        $this->actingAs($admin)
            ->get(CaseStudyResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Практические примеры')
            ->assertSee('Краткое описание проблемы')
            ->assertSee('Пошаговая настройка')
            ->assertDontSee('Short problem statement');
    }

    /**
     * A study written in Russian, translated into English: an English partner
     * reads the translation, a Russian partner reads the original.
     */
    public function test_a_study_can_be_written_in_one_language_and_translated_into_another(): void
    {
        $this->seed(LanguageSeeder::class);

        $study = $this->completeStudy([
            'status' => CaseStudy::STATUS_PUBLISHED,
            'language' => 'ru',
            'title' => 'Контроль прибытия и убытия',
            'short_problem' => 'Нужны доказательства прибытия на площадки.',
            'configuration_steps' => '<p>Создайте геозону.</p>',
        ]);

        $study->setTranslation('title', 'en', 'Arrival and departure monitoring');
        $study->setTranslation('configuration_steps', 'en', '<p>Create a GeoZone.</p>');

        $this->withHeader('Accept-Language', 'en')
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Arrival and departure monitoring')
            ->assertSee('Create a GeoZone.', false)
            // Nobody translated the summary, so the original stands.
            ->assertSee('Нужны доказательства прибытия на площадки.');

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Контроль прибытия и убытия')
            ->assertDontSee('Arrival and departure monitoring');

        // And the listing finds it by its translation, like a course.
        $this->withHeader('Accept-Language', 'en')
            ->get(route('academy.case-studies.index', ['q' => 'GeoZone']))
            ->assertOk()
            ->assertSee('Arrival and departure monitoring');
    }

    /** The source and performance notes are the editor's working notes. */
    public function test_only_an_editor_reads_the_source_and_performance_notes(): void
    {
        $product = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        $study = $this->completeStudy([
            'status' => CaseStudy::STATUS_PUBLISHED,
            'product_id' => $product->id,
            'source_note' => 'Checked against the staging fleet, not a customer.',
            'performance_claim_note' => 'No quantified claim.',
        ]);

        $this->actingAs($this->learner())
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee($study->title)
            ->assertDontSee('Checked against the staging fleet, not a customer.')
            ->assertDontSee('No quantified claim.');

        $this->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertDontSee('Checked against the staging fleet, not a customer.');

        $this->actingAs($this->creatorFor($product))
            ->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Checked against the staging fleet, not a customer.');
    }

    /** A screenshot saved against one step shows under that step, and no other. */
    public function test_pictures_belong_to_the_step_they_were_added_to(): void
    {
        $study = $this->completeStudy([
            'status' => CaseStudy::STATUS_PUBLISHED,
            'section_images' => [
                'configuration' => ['case-study-images/geozone.png', 'case-study-images/geozone-2.png'],
                'verification' => ['case-study-images/history.png'],
            ],
        ]);

        $response = $this->get(route('academy.case-studies.show', $study))->assertOk();

        // Each picture is a public url the partner's browser can fetch.
        $response->assertSee('/storage/case-study-images/geozone.png', false)
            ->assertSee('/storage/case-study-images/geozone-2.png', false)
            ->assertSee('/storage/case-study-images/history.png', false);

        // The configuration step carries its two, before the verification step.
        $response->assertSeeInOrder([
            'Step-by-step configuration',
            'case-study-images/geozone.png',
            'case-study-images/geozone-2.png',
            'How to test and verify the setup',
            'case-study-images/history.png',
        ], false);

        $this->assertSame([], $study->imagesFor('troubleshooting'), 'A step nobody gave a picture has none.');
    }

    /** A step with only a screenshot is still worth showing. */
    public function test_a_step_with_pictures_and_no_words_still_appears(): void
    {
        $study = $this->completeStudy([
            'status' => CaseStudy::STATUS_PUBLISHED,
            'troubleshooting' => null,
            'section_images' => ['troubleshooting' => ['case-study-images/error.png']],
        ]);

        $this->get(route('academy.case-studies.show', $study))
            ->assertOk()
            ->assertSee('Troubleshooting and common mistakes')
            ->assertSee('/storage/case-study-images/error.png', false);
    }

    /** There is no "Written in" field to fill: a new study takes the academy's default language on its own. */
    public function test_a_new_study_is_written_in_the_academys_default_language_with_no_field_to_set_it(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateCaseStudy::class)
            // The field is still there to carry the value, but hidden — not
            // a Select an editor could open and choose from.
            ->assertFormFieldExists('language', checkFieldUsing: fn ($field) => $field instanceof Hidden)
            ->fillForm([
                'title' => 'No written-in field',
                'slug' => 'no-written-in-field',
                'short_problem' => 'Short problem.',
                'scenario_problem' => '<p>Scenario.</p>',
                'desired_outcome' => '<p>Outcome.</p>',
                'configuration_steps' => '<p>Config.</p>',
                'testing_verification' => '<p>Verify.</p>',
                'source_note' => 'Note.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('en', CaseStudy::where('slug', 'no-written-in-field')->value('language'));
    }

    /**
     * "Automatic" means the editor's own panel language, not a flat site
     * default — a Russian-speaking creator with their panel set to Russian
     * gets a Russian study without having to say so anywhere.
     */
    public function test_a_new_study_takes_the_editors_own_panel_language_not_the_site_default(): void
    {
        $this->seed(LanguageSeeder::class);
        $admin = $this->admin();
        $admin->forceFill(['locale' => 'ru'])->save();

        // Livewire::test() mounts the component directly and never runs the
        // HTTP middleware stack, so SetLocale — the thing that would apply
        // the admin's own locale on a real page load — never runs either.
        // Setting it here stands in for that middleware, the same as a real
        // request to this page would have already done by the time the form
        // renders; test_the_editor_screens_read_in_the_editors_language
        // proves SetLocale itself does this on a real request.
        app()->setLocale('ru');

        Livewire::actingAs($admin)
            ->test(CreateCaseStudy::class)
            ->fillForm([
                'title' => 'Written in Russian',
                'slug' => 'written-in-russian',
                'short_problem' => 'Short problem.',
                'scenario_problem' => '<p>Scenario.</p>',
                'desired_outcome' => '<p>Outcome.</p>',
                'configuration_steps' => '<p>Config.</p>',
                'testing_verification' => '<p>Verify.</p>',
                'source_note' => 'Note.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('ru', CaseStudy::where('slug', 'written-in-russian')->value('language'));
    }

    /**
     * A new study starts with only the four required steps; the five optional
     * ones are not in the form at all until "Add step" brings one in.
     */
    public function test_a_new_study_starts_with_only_the_required_steps_shown(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateCaseStudy::class)
            ->assertFormFieldVisible('scenario_problem')
            ->assertFormFieldVisible('configuration_steps')
            ->assertFormFieldDoesNotExist('prerequisites')
            ->assertFormFieldDoesNotExist('pilot_features')
            ->assertFormFieldDoesNotExist('expected_results')
            ->assertFormFieldDoesNotExist('troubleshooting')
            ->assertFormFieldDoesNotExist('adaptation');
    }

    /** Editing a study that already has an optional step filled shows it, pre-filled, without needing Add step. */
    public function test_editing_a_study_shows_its_already_filled_optional_steps(): void
    {
        $study = $this->completeStudy();

        Livewire::actingAs($this->admin())
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->assertFormFieldVisible('prerequisites')
            ->assertFormSet(['prerequisites' => '<p>Prerequisites.</p>']);
    }

    /**
     * "Add step" reveals an optional step's box; "Hide this step" hides it
     * again without clearing whatever was written in it.
     */
    public function test_add_step_shows_an_optional_step_and_hide_step_only_hides_it(): void
    {
        $study = $this->completeStudy();

        $page = Livewire::actingAs($this->admin())
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->assertFormFieldVisible('prerequisites')
            ->set('data.active_optional_steps', ['features', 'results', 'troubleshooting', 'adaptation'])
            ->assertFormFieldDoesNotExist('prerequisites');

        // "Add step" only offers what is not already shown, and ticking one
        // brings its box back into the form.
        $page->set('data.add_step', 'prerequisites')
            ->assertFormFieldVisible('prerequisites')
            ->assertFormSet(['prerequisites' => '<p>Prerequisites.</p>'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Removing it from the form and saving never touched the column.
        $this->assertSame('<p>Prerequisites.</p>', $study->fresh()->prerequisites);
    }

    /** Hiding an optional step and saving does not clear its saved content. */
    public function test_hiding_an_optional_step_and_saving_keeps_its_content(): void
    {
        $study = $this->completeStudy();

        Livewire::actingAs($this->admin())
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->set('data.active_optional_steps', [])
            ->assertFormFieldDoesNotExist('prerequisites')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('<p>Prerequisites.</p>', $study->fresh()->prerequisites, 'Hidden, not deleted.');
    }

    /**
     * "Delete instead", offered in the same confirm dialog as "Hide this
     * step", clears the step's own column for good — unlike Hide, it is not
     * recoverable through "Add step".
     */
    public function test_deleting_a_step_clears_its_saved_content(): void
    {
        $study = $this->completeStudy();

        Livewire::actingAs($this->admin())
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->assertFormFieldVisible('prerequisites')
            // Delete sits as an extra footer button inside Hide's own
            // confirm dialog, so reaching it means mounting both.
            ->callAction(['hide_prerequisites', 'delete_prerequisites'])
            ->assertFormFieldDoesNotExist('prerequisites');

        $this->assertNull($study->fresh()->prerequisites);
    }

    /** Deleting a step with a picture gallery clears its pictures too. */
    public function test_deleting_a_step_with_pictures_clears_its_gallery(): void
    {
        $study = $this->completeStudy([
            'expected_results' => '<p>Results.</p>',
            'section_images' => ['results' => ['results-1.png']],
        ]);

        Livewire::actingAs($this->admin())
            ->test(EditCaseStudy::class, ['record' => $study->getRouteKey()])
            ->assertFormFieldVisible('expected_results')
            ->callAction(['hide_results', 'delete_results'])
            ->assertFormFieldDoesNotExist('expected_results');

        $fresh = $study->fresh();
        $this->assertNull($fresh->expected_results);
        $this->assertArrayNotHasKey('results', $fresh->section_images ?? []);
    }

    public function test_creators_only_see_and_manage_their_products_case_studies(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $theirs = Product::create(['name' => 'PTM', 'slug' => 'ptm']);
        $myStudy = $this->completeStudy(['product_id' => $mine->id]);
        $theirStudy = $this->completeStudy([
            'product_id' => $theirs->id,
            'title' => 'Fuel event investigation',
            'slug' => 'fuel-event-investigation',
        ]);

        Livewire::actingAs($this->creatorFor($mine))
            ->test(ListCaseStudies::class)
            ->assertCanSeeTableRecords([$myStudy])
            ->assertCanNotSeeTableRecords([$theirStudy]);
    }
}
