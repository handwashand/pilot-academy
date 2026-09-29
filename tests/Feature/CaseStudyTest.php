<?php

namespace Tests\Feature;

use App\Filament\Resources\CaseStudies\Pages\ListCaseStudies;
use App\Models\CaseStudy;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
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
