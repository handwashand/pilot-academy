<?php

namespace Tests\Feature;

use App\Filament\Resources\Webinars\Pages\ListWebinars;
use App\Models\ActivityEvent;
use App\Models\Product;
use App\Models\User;
use App\Models\Webinar;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Webinars are live sessions partners can join, and recordings afterwards.
 * Upcoming and past are the same record; the start time decides the list.
 */
class WebinarTest extends TestCase
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

    private function webinar(array $overrides = []): Webinar
    {
        return Webinar::create([
            'title' => 'Fuel sensors deep dive',
            'slug' => 'fuel-sensors-deep-dive',
            'summary' => 'How to read a fuel chart with confidence.',
            'presenter' => 'The Pilot team',
            'starts_at' => now()->addWeek(),
            'duration_minutes' => 60,
            'join_url' => 'https://example.com/join',
            ...$overrides,
        ]);
    }

    public function test_upcoming_sessions_and_past_recordings_are_in_their_own_lists(): void
    {
        $this->webinar(['status' => Webinar::STATUS_PUBLISHED]);
        $this->webinar([
            'title' => 'GeoZones in practice',
            'slug' => 'geozones-in-practice',
            'starts_at' => now()->subMonth(),
            'join_url' => null,
            'recording_url' => 'https://example.com/recording',
            'status' => Webinar::STATUS_PUBLISHED,
        ]);

        $this->get(route('academy.webinars'))
            ->assertOk()
            ->assertSeeInOrder([
                'Upcoming',
                'Fuel sensors deep dive',
                'Join',
                'Past sessions',
                'GeoZones in practice',
                'Watch the recording',
            ]);
    }

    public function test_a_draft_session_is_hidden_from_partners_but_previewable_by_an_editor(): void
    {
        $webinar = $this->webinar();

        $this->get(route('academy.webinars'))
            ->assertOk()
            ->assertDontSee('Fuel sensors deep dive');

        $this->actingAs($this->learner())
            ->get(route('academy.webinar', $webinar))
            ->assertNotFound();

        $this->actingAs($this->admin())
            ->get(route('academy.webinar', $webinar))
            ->assertOk()
            ->assertSee('partners cannot see this page');
    }

    public function test_a_session_without_a_date_or_a_link_cannot_be_published(): void
    {
        $admin = $this->admin();
        $incomplete = $this->webinar(['starts_at' => null, 'join_url' => null]);

        Livewire::actingAs($admin)
            ->test(ListWebinars::class)
            ->callAction(TestAction::make('publish')->table($incomplete));

        $this->assertSame(Webinar::STATUS_DRAFT, $incomplete->fresh()->status);

        $ready = $this->webinar(['title' => 'Reports for dispatchers', 'slug' => 'reports-for-dispatchers']);

        Livewire::actingAs($admin)
            ->test(ListWebinars::class)
            ->callAction(TestAction::make('publish')->table($ready))
            ->assertHasNoActionErrors();

        $this->assertSame(Webinar::STATUS_PUBLISHED, $ready->fresh()->status);
    }

    public function test_the_time_is_always_shown_with_its_zone(): void
    {
        $webinar = $this->webinar([
            'starts_at' => now()->addWeek()->setTime(14, 0),
            'status' => Webinar::STATUS_PUBLISHED,
        ]);

        $this->get(route('academy.webinars'))
            ->assertOk()
            ->assertSee('UTC');

        $this->assertStringEndsWith('UTC', $webinar->whenLabel());
    }

    public function test_signed_in_webinar_opens_joins_and_recordings_are_tracked(): void
    {
        $webinar = $this->webinar([
            'status' => Webinar::STATUS_PUBLISHED,
            'recording_url' => 'https://example.com/recording',
        ]);
        $learner = $this->learner();

        $this->actingAs($learner)
            ->get(route('academy.webinar', $webinar))
            ->assertOk();
        $this->get(route('academy.webinar.join', $webinar))
            ->assertRedirect('https://example.com/join');
        $this->get(route('academy.webinar.recording', $webinar))
            ->assertRedirect('https://example.com/recording');

        foreach ([
            ActivityEvent::TYPE_WEBINAR_OPENED,
            ActivityEvent::TYPE_WEBINAR_JOINED,
            ActivityEvent::TYPE_WEBINAR_RECORDING_OPENED,
        ] as $type) {
            $this->assertDatabaseHas('activity_events', [
                'user_id' => $learner->id,
                'type' => $type,
                'subject_type' => $webinar->getMorphClass(),
                'subject_id' => $webinar->id,
            ]);
        }
    }

    public function test_the_page_reads_in_the_partners_language(): void
    {
        $this->seed(LanguageSeeder::class);
        $this->webinar(['status' => Webinar::STATUS_PUBLISHED]);

        $this->withHeader('Accept-Language', 'ru-RU,ru;q=0.9')
            ->get(route('academy.webinars'))
            ->assertOk()
            ->assertSee('Вебинары')
            ->assertSee('Ближайшие')
            ->assertSee('Присоединиться')
            ->assertDontSee('Upcoming');
    }

    public function test_a_creator_only_sees_their_own_products_sessions(): void
    {
        $mine = Product::create(['name' => 'GARM', 'slug' => 'garm']);
        $theirs = Product::create(['name' => 'PTM', 'slug' => 'ptm']);

        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_CREATOR,
        ]);
        $creator->products()->attach($mine);

        $ours = $this->webinar(['product_id' => $mine->id]);
        $others = $this->webinar([
            'product_id' => $theirs->id,
            'title' => 'Not for this creator',
            'slug' => 'not-for-this-creator',
        ]);

        Livewire::actingAs($creator)
            ->test(ListWebinars::class)
            ->assertCanSeeTableRecords([$ours])
            ->assertCanNotSeeTableRecords([$others]);
    }
}
