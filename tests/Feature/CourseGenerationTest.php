<?php

namespace Tests\Feature;

use App\Actions\GenerateCourseWithAi;
use App\Filament\Pages\GenerateCourse;
use App\Filament\Pages\Integrations;
use App\Models\AiContentRevision;
use App\Models\AiGenerationSetting;
use App\Models\AiProvider;
use App\Models\AiUsage;
use App\Models\Course;
use App\Models\Product;
use App\Models\User;
use App\Services\Llm\LlmException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Course drafts through the ChatGPT and DeepSeek rows already used for
 * translation. No second token, and a draft is not visible to students.
 */
class CourseGenerationTest extends TestCase
{
    use RefreshDatabase;

    private const BRIEF = 'Teach a technician to check a fuel sensor before a site visit and record the reading.';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_chatgpt_saves_a_draft_with_the_existing_token(): void
    {
        $this->enable('chatgpt', 'sk-secret-token', 'gpt-4o-mini');
        $this->fake((array) $this->payload(), 'openai.com');

        $course = $this->generate($this->admin(), 'chatgpt', 'gpt-4o-mini');

        $this->assertDraft($course);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.openai.com/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer sk-secret-token')
            && $request->data()['model'] === 'gpt-4o-mini'
            && $request->data()['response_format'] === ['type' => 'json_object']
            && ! str_contains((string) json_encode($request->data()), 'sk-secret-token'));

        $usage = AiUsage::query()->sole();
        $this->assertSame('chatgpt', $usage->provider);
        $this->assertSame('gpt-4o-mini', $usage->model);
        $this->assertSame(AiUsage::PURPOSE_COURSE, $usage->purpose);
        $this->assertSame('0.000135', $usage->estimated_cost_usd);
        $this->assertSame('chatgpt', $course->revisions()->sole()->provider);

        $manual = Course::create(['title' => 'Written by hand', 'slug' => 'written-by-hand', 'level' => 'beginner']);
        $this->assertSame(0, AiContentRevision::query()->whereMorphedTo('subject', $manual)->count());

        auth()->logout();
        $this->get(route('academy.course', $course))->assertNotFound();
    }

    public function test_deepseek_saves_the_same_structure_with_its_own_token(): void
    {
        $this->enable('deepseek', 'ds-token', 'deepseek-chat');
        $this->fake((array) $this->payload(), 'deepseek.com');

        $course = $this->generate($this->admin(), 'deepseek', 'deepseek-chat');

        $this->assertDraft($course);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.deepseek.com/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer ds-token')
            && $request->data()['model'] === 'deepseek-chat');
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'openai.com'));

        $usage = AiUsage::query()->sole();
        $this->assertSame('deepseek', $usage->provider);
        $this->assertSame('0.000070', $usage->estimated_cost_usd);
        $this->assertSame('deepseek', AiContentRevision::query()->where('purpose', 'lesson')->value('provider'));
    }

    public function test_a_disabled_provider_is_not_called(): void
    {
        $this->enable('chatgpt');
        AiProvider::for('chatgpt')->update(['enabled' => false]);

        $exception = $this->capture(fn () => $this->generate($this->admin(), 'chatgpt', 'gpt-4o-mini'));

        $this->assertSame('unavailable', $exception->errorCode);
        $this->assertSame(0, Course::query()->count());
        Http::assertNothingSent();
        $this->assertArrayNotHasKey('chatgpt', AiProvider::generationChoices());
    }

    public function test_a_model_without_json_cannot_be_used(): void
    {
        $this->enable('deepseek', 'ds-token', 'deepseek-reasoner');

        $exception = $this->capture(fn () => $this->generate($this->admin(), 'deepseek', 'deepseek-reasoner'));

        $this->assertSame('unsupported_capability', $exception->errorCode);
        $this->assertSame(0, Course::query()->count());
        Http::assertNothingSent();
        $this->assertArrayNotHasKey('deepseek-reasoner', AiProvider::modelsForGeneration('deepseek'));
    }

    public function test_an_invalid_token_is_reported_without_being_logged_or_saved(): void
    {
        $this->enable('chatgpt', 'sk-bad-token');
        $logged = '';
        Log::listen(function (MessageLogged $event) use (&$logged): void {
            $logged .= $event->message.' '.json_encode($event->context);
        });
        Http::fake([
            '*' => Http::response(['error' => ['code' => 'invalid_api_key', 'message' => 'bad sk-bad-token']], 401),
        ]);

        $exception = $this->capture(fn () => $this->generate($this->admin(), 'chatgpt', 'gpt-4o-mini'));

        $this->assertTrue($exception->isAuthProblem());
        $this->assertSame(0, Course::query()->count());
        $this->assertSame(0, AiUsage::query()->count());
        $this->assertStringNotContainsString('sk-bad-token', $exception->getMessage());
        $this->assertStringNotContainsString('sk-bad-token', $logged);
    }

    public function test_a_malformed_answer_is_repaired_once_and_then_refused(): void
    {
        $this->enable('chatgpt');
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            $content = $calls === 1 ? 'not json' : json_encode($this->payload());

            return Http::response([
                'choices' => [['message' => ['content' => $content]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10],
            ]);
        });

        $course = $this->generate($this->admin(), 'chatgpt', 'gpt-4o-mini');

        $this->assertSame(2, $calls);
        $this->assertSame('Fuel sensors', $course->title);
        $this->assertSame(2, AiUsage::query()->count());
    }

    public function test_an_answer_that_stays_invalid_saves_nothing(): void
    {
        $this->enable('deepseek');
        $bad = $this->payload();
        $bad['lessons'][0]['questions'][0]['options'][1]['correct'] = true;
        Http::fake([
            '*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($bad)]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10],
            ]),
        ]);

        $exception = $this->capture(fn () => $this->generate($this->admin(), 'deepseek', 'deepseek-chat'));

        $this->assertSame('invalid_response', $exception->errorCode);
        $this->assertSame(0, Course::query()->count());
        $this->assertSame(2, AiUsage::query()->where('provider', 'deepseek')->count());
    }

    public function test_markup_in_the_answer_is_stored_as_text(): void
    {
        $this->enable('chatgpt');
        $payload = $this->payload();
        $payload['title'] = '<script>alert(1)</script> Fuel';
        $payload['lessons'][0]['content'] = "Check the <script>sensor</script>.\n\nDone.";
        $this->fake($payload, 'openai.com');

        $course = $this->generate($this->admin(), 'chatgpt', 'gpt-4o-mini');

        $this->assertStringNotContainsString('<script', $course->title);
        $this->assertStringNotContainsString('<script', (string) $course->lessons()->first()->content);
        $this->assertStringContainsString('<p>', (string) $course->lessons()->first()->content);
    }

    public function test_switching_provider_leaves_the_first_course_intact(): void
    {
        $admin = $this->admin();
        $this->enable('chatgpt', 'sk-secret-token');
        $this->enable('deepseek', 'ds-token');
        Http::fake(function (Request $request) {
            return Http::response([
                'choices' => [['message' => ['content' => json_encode($this->payload())]]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 200],
            ]);
        });

        $first = $this->generate($admin, 'chatgpt', 'gpt-4o-mini');
        $second = $this->generate($admin, 'deepseek', 'deepseek-chat');

        $this->assertSame('Fuel sensors', $first->fresh()->title);
        $this->assertSame(2, $first->fresh()->lessons()->count());
        $this->assertSame(['chatgpt'], $first->revisions()->pluck('provider')->unique()->values()->all());
        $this->assertSame(['deepseek'], $second->revisions()->pluck('provider')->unique()->values()->all());
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_fallback_is_off_unless_an_admin_allows_it_and_the_request_asks(): void
    {
        $admin = $this->admin();
        $this->enable('chatgpt', 'sk-secret-token');
        $this->enable('deepseek', 'ds-token');
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), 'openai.com')) {
                return Http::response(['error' => ['code' => 'server_error']], 500);
            }

            return Http::response([
                'choices' => [['message' => ['content' => json_encode($this->payload())]]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 200],
            ]);
        });

        $blocked = $this->capture(fn () => $this->generate($admin, 'chatgpt', 'gpt-4o-mini', allowFallback: true));
        $this->assertTrue($blocked->isTemporary());
        $this->assertSame(0, Course::query()->count());

        AiGenerationSetting::current()->update(['fallback_enabled' => true]);

        $course = $this->generate($admin, 'chatgpt', 'gpt-4o-mini', allowFallback: true);

        $this->assertSame('deepseek', $course->revisions()->sole()->provider);
        $this->assertSame('Fuel sensors', $course->title);
    }

    public function test_limits_stop_a_new_generation_before_a_request(): void
    {
        $admin = $this->admin();
        $this->enable('chatgpt');
        AiGenerationSetting::current()->update([
            'daily_generation_limit' => 1,
            'monthly_budget_usd' => null,
        ]);
        AiUsage::query()->create([
            'user_id' => $admin->id,
            'provider' => 'chatgpt',
            'model' => 'gpt-4o-mini',
            'purpose' => AiUsage::PURPOSE_COURSE,
            'prompt_tokens' => 1,
            'completion_tokens' => 1,
            'estimated_cost_usd' => '0.100000',
        ]);

        $this->assertSame('limit', $this->capture(fn () => $this->generate($admin, 'chatgpt', 'gpt-4o-mini'))->errorCode);

        AiUsage::query()->delete();
        AiGenerationSetting::current()->update([
            'daily_generation_limit' => null,
            'monthly_budget_usd' => '0.05',
        ]);
        AiUsage::query()->create([
            'user_id' => $admin->id,
            'provider' => 'deepseek',
            'model' => 'deepseek-chat',
            'purpose' => AiUsage::PURPOSE_COURSE,
            'prompt_tokens' => 1,
            'completion_tokens' => 1,
            'estimated_cost_usd' => '0.050000',
        ]);

        $this->assertSame('budget', $this->capture(fn () => $this->generate($admin, 'chatgpt', 'gpt-4o-mini'))->errorCode);
        Http::assertNothingSent();
    }

    public function test_an_admin_does_not_have_course_generation_until_it_is_ticked(): void
    {
        $this->enable('chatgpt');
        $admin = User::create([
            'name' => 'Plain Admin',
            'email' => 'plain-admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);
        $this->assertFalse(GenerateCourse::canAccess());
        $this->get(GenerateCourse::getUrl())->assertForbidden();
        $this->assertSame('forbidden', $this->capture(fn () => $this->generate($admin, 'chatgpt', 'gpt-4o-mini'))->errorCode);
        $this->assertSame(0, Course::query()->count());
        Http::assertNothingSent();
    }

    public function test_a_creator_can_generate_only_for_a_product_they_own(): void
    {
        $this->enable('chatgpt');
        $this->fake((array) $this->payload(), 'openai.com');
        $own = Product::create(['name' => 'Own', 'slug' => 'own']);
        $other = Product::create(['name' => 'Other', 'slug' => 'other']);
        $creator = User::create([
            'name' => 'Creator',
            'email' => 'creator@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_CREATOR,
        ]);
        $creator->products()->attach($own);

        $this->assertSame('forbidden', $this->capture(fn () => $this->generate($creator, 'chatgpt', 'gpt-4o-mini', $own->id))->errorCode);

        $creator->permissions()->create(['permission' => User::PERMISSION_AI_GENERATE]);
        $creator->unsetRelation('permissions');

        $course = $this->generate($creator, 'chatgpt', 'gpt-4o-mini', $own->id);
        $this->assertSame($own->id, $course->product_id);

        $this->assertSame('product', $this->capture(fn () => $this->generate($creator, 'chatgpt', 'gpt-4o-mini', $other->id))->errorCode);
        $this->assertSame(1, Course::query()->count());
    }

    public function test_the_page_offers_only_a_connected_provider_and_saves(): void
    {
        $this->enable('deepseek', 'ds-token', 'deepseek-chat');
        $this->fake((array) $this->payload(), 'deepseek.com');

        Livewire::actingAs($this->admin())
            ->test(GenerateCourse::class)
            ->assertDontSee('ChatGPT')
            ->assertSee('DeepSeek')
            ->fillForm([
                'provider' => 'deepseek',
                'model' => 'deepseek-chat',
                'mode' => 'full',
                'brief' => self::BRIEF,
            ])
            ->call('generate')
            ->assertNotified(__t('admin_pages.course_generation.saved'));

        $this->assertSame('draft', Course::query()->sole()->status);
        $this->assertSame('deepseek', AiUsage::query()->sole()->provider);
    }

    public function test_integrations_saves_the_generation_defaults_without_a_second_token(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Integrations::class)
            ->fillForm([
                'generation' => [
                    'default_provider' => 'deepseek',
                    'fallback_enabled' => true,
                    'daily_generation_limit' => 4,
                    'monthly_budget_usd' => '25',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = AiGenerationSetting::current();
        $this->assertSame('deepseek', $settings->default_provider);
        $this->assertTrue($settings->fallback_enabled);
        $this->assertSame(4, $settings->daily_generation_limit);
        $this->assertSame('25.00', $settings->monthly_budget_usd);
        $this->assertSame(0, AiProvider::query()->whereNotNull('api_key')->count());
    }

    private function generate(User $user, string $provider, string $model, ?int $productId = null, bool $allowFallback = false): Course
    {
        $this->actingAs($user);

        return app(GenerateCourseWithAi::class)->handle(
            $user,
            $provider,
            $model,
            self::BRIEF,
            GenerateCourseWithAi::MODE_FULL,
            $productId,
            $allowFallback,
        );
    }

    private function capture(callable $callback): LlmException
    {
        try {
            $callback();
        } catch (LlmException $exception) {
            return $exception;
        }

        $this->fail('Expected the generation to be refused.');
    }

    private function assertDraft(Course $course): void
    {
        $course->load('lessons.questions.options');

        $this->assertSame('Fuel sensors', $course->title);
        $this->assertSame(Course::STATUS_DRAFT, $course->status);
        $this->assertFalse($course->isPublished());
        $this->assertCount(2, $course->lessons);
        $this->assertSame(['Check the sensor', 'Record the reading'], $course->lessons->pluck('title')->all());

        foreach ($course->lessons as $lesson) {
            $this->assertTrue($lesson->isPublished());
            $question = $lesson->questions->sole();
            $this->assertCount(3, $question->options);
            $this->assertCount(1, $question->options->where('is_correct', true));
        }
    }

    /** @param  array<string, mixed>  $payload */
    private function fake(array $payload, string $host): void
    {
        Http::fake(function (Request $request) use ($payload, $host) {
            if (! str_contains($request->url(), $host)) {
                return Http::response('unexpected host', 500);
            }

            return Http::response([
                'choices' => [['message' => ['content' => json_encode($payload)]]],
                'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 200],
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'title' => 'Fuel sensors',
            'description' => 'A short course for field technicians.',
            'level' => 'beginner',
            'audience' => 'technical',
            'lessons' => [
                $this->lesson('Check the sensor', 'Look at the fitting before you trust the number.'),
                $this->lesson('Record the reading', 'Write down what the display shows.'),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function lesson(string $title, string $content): array
    {
        return [
            'title' => $title,
            'summary' => $title,
            'content' => $content,
            'questions' => [[
                'prompt' => 'What do you do first?',
                'options' => [
                    ['text' => 'Check the fitting', 'correct' => true],
                    ['text' => 'Skip the visit', 'correct' => false],
                    ['text' => 'Replace the tank', 'correct' => false],
                ],
            ]],
        ];
    }

    private function enable(string $provider, string $key = 'sk-secret-token', ?string $model = null): void
    {
        AiProvider::for($provider)->fill([
            'enabled' => true,
            'api_key' => $key,
            'model' => $model,
        ])->save();
    }

    private function admin(): User
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'admin@pilot.local',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
        $admin->permissions()->create(['permission' => User::PERMISSION_AI_GENERATE]);

        return $admin;
    }
}
