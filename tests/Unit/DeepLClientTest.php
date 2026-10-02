<?php

namespace Tests\Unit;

use App\Services\DeepL\DeepLClient;
use App\Services\DeepL\DeepLException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;
use Mockery;
use Tests\TestCase;

class DeepLClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        Sleep::fake();

        config([
            'services.deepl.enabled' => true,
            'services.deepl.key' => 'test-deepl-secret',
            'services.deepl.base_url' => 'https://api-free.deepl.com',
            'services.deepl.english_target' => 'en-US',
            'services.deepl.timeout' => 10,
            'services.deepl.reporting_tag' => 'pilot-academy-tests',
        ]);
    }

    public function test_it_is_enabled_only_with_a_key_flag_and_official_host(): void
    {
        $client = $this->client();
        $this->assertTrue($client->enabled());

        config(['services.deepl.enabled' => false]);
        $this->assertFalse($client->enabled());

        config(['services.deepl.enabled' => true, 'services.deepl.key' => '']);
        $this->assertFalse($client->enabled());

        config(['services.deepl.key' => 'secret', 'services.deepl.base_url' => 'https://example.com']);
        $this->assertFalse($client->enabled());
    }

    public function test_language_support_is_read_from_v3_and_cached(): void
    {
        Http::fake(['*/v3/languages*' => Http::response($this->languages())]);

        $this->assertTrue($this->client()->supports('en', 'fr'));
        $this->assertTrue($this->client()->supports('en', 'fr'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'v3/languages?resource=translate_text')
            && $request->hasHeader('Authorization', 'DeepL-Auth-Key test-deepl-secret')
            && $request->hasHeader('X-DeepL-Reporting-Tag', 'pilot-academy-tests'));
    }

    public function test_academy_language_codes_map_to_deepl_variants(): void
    {
        $client = $this->client();

        $this->assertSame('en', $client->sourceCode('en'));
        $this->assertSame('pt', $client->sourceCode('pt'));
        $this->assertSame('en-US', $client->targetCode('en'));
        $this->assertSame('pt-BR', $client->targetCode('pt'));
        $this->assertSame('ar', $client->targetCode('ar'));

        config(['services.deepl.english_target' => 'en-GB']);
        $this->assertSame('en-GB', $client->targetCode('en'));
    }

    public function test_named_plain_texts_are_translated_in_one_request(): void
    {
        $this->fakeSuccessfulDeepL();

        $translated = $this->client()->translate([
            'title' => 'Quick start',
            'summary' => 'Learn the basics.',
        ], 'en', 'fr');

        $this->assertSame([
            'title' => '[fr] Quick start',
            'summary' => '[fr] Learn the basics.',
        ], $translated);

        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/translate')
            && $request->data()['source_lang'] === 'en'
            && $request->data()['target_lang'] === 'fr'
            && $request->data()['text'] === ['Quick start', 'Learn the basics.']
            && ! isset($request->data()['tag_handling']));
    }

    public function test_html_uses_deepls_current_tag_handling(): void
    {
        $this->fakeSuccessfulDeepL();

        $translated = $this->client()->translate([
            'content' => '<p>Open <strong>Settings</strong>.</p>',
        ], 'en', 'ar', html: true);

        $this->assertSame('[ar] <p>Open <strong>Settings</strong>.</p>', $translated['content']);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/translate')
            && $request->data()['tag_handling'] === 'html'
            && $request->data()['tag_handling_version'] === 'v2');
    }

    public function test_large_sets_are_split_below_the_request_limit(): void
    {
        $this->fakeSuccessfulDeepL();

        $translated = $this->client()->translate([
            'first' => str_repeat('a', 70_000),
            'second' => str_repeat('b', 70_000),
        ], 'en', 'es');

        $this->assertCount(2, $translated);
        $this->assertSame(2, $this->translationRequestCount());
    }

    public function test_one_oversized_field_is_refused_before_translation(): void
    {
        Http::fake(['*/v3/languages*' => Http::response($this->languages())]);

        try {
            $this->client()->translate(['content' => str_repeat('a', 125_000)], 'en', 'fr');
            $this->fail('Expected an oversized field to be refused.');
        } catch (DeepLException $exception) {
            $this->assertSame(413, $exception->status);
            $this->assertSame('payload_too_large', $exception->errorCode);
        }

        $this->assertSame(0, $this->translationRequestCount());
    }

    public function test_unsupported_languages_are_refused_before_translation(): void
    {
        $languages = array_values(array_filter(
            $this->languages(),
            fn (array $language): bool => $language['lang'] !== 'ar',
        ));
        Http::fake(['*/v3/languages*' => Http::response($languages)]);

        $this->expectException(DeepLException::class);
        $this->client()->translate(['title' => 'Quick start'], 'en', 'ar');
    }

    public function test_temporary_failures_are_retried(): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use (&$attempts) {
            if (str_contains($request->url(), '/v3/languages')) {
                return Http::response($this->languages());
            }

            $attempts++;

            return $attempts === 1
                ? Http::response(['message' => 'Busy'], 529, ['Retry-After' => '1'])
                : Http::response(['translations' => [['text' => 'Bonjour']]]);
        });

        $this->assertSame(['title' => 'Bonjour'], $this->client()->translate(['title' => 'Hello'], 'en', 'fr'));
        $this->assertSame(2, $attempts);
    }

    public function test_quota_exhaustion_is_not_retried(): void
    {
        $attempts = 0;
        Http::fake(function (Request $request) use (&$attempts) {
            if (str_contains($request->url(), '/v3/languages')) {
                return Http::response($this->languages());
            }

            $attempts++;

            return Http::response(['message' => 'Quota exceeded', 'code' => 'quota_exceeded'], 456);
        });

        try {
            $this->client()->translate(['title' => 'Hello'], 'en', 'fr');
            $this->fail('Expected quota exhaustion.');
        } catch (DeepLException $exception) {
            $this->assertTrue($exception->isQuotaProblem());
        }

        $this->assertSame(1, $attempts);
    }

    public function test_failure_logs_identifiers_but_never_the_key_or_content(): void
    {
        Log::spy();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/v3/languages')) {
                return Http::response($this->languages());
            }

            return Http::response(
                ['message' => 'Forbidden', 'code' => 'auth_failed'],
                403,
                ['X-Trace-ID' => 'trace-123'],
            );
        });

        try {
            $this->client()->translate(['title' => 'Private course words'], 'en', 'fr');
            $this->fail('Expected an authentication failure.');
        } catch (DeepLException $exception) {
            $this->assertTrue($exception->isAuthProblem());
            $this->assertSame('trace-123', $exception->traceId);
        }

        Log::shouldHaveReceived('warning')->once()->with(
            'DeepL request failed',
            Mockery::on(fn (array $context): bool => $context['status'] === 403
                && $context['error'] === 'auth_failed'
                && $context['trace_id'] === 'trace-123'
                && ! str_contains(json_encode($context), 'test-deepl-secret')
                && ! str_contains(json_encode($context), 'Private course words')),
        );
    }

    private function client(): DeepLClient
    {
        return app(DeepLClient::class);
    }

    private function fakeSuccessfulDeepL(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/v3/languages')) {
                return Http::response($this->languages());
            }

            $target = $request->data()['target_lang'];

            return Http::response([
                'translations' => collect($request->data()['text'])
                    ->map(fn (string $text): array => ['text' => "[{$target}] {$text}"])
                    ->all(),
            ]);
        });
    }

    private function translationRequestCount(): int
    {
        return collect(Http::recorded())
            ->filter(fn (array $pair): bool => str_ends_with($pair[0]->url(), '/v2/translate'))
            ->count();
    }

    /** @return array<int, array<string, mixed>> */
    private function languages(): array
    {
        $features = ['tag_handling' => ['status' => 'stable']];

        return [
            ['lang' => 'en', 'usable_as_source' => true, 'usable_as_target' => false, 'features' => $features],
            ['lang' => 'en-US', 'usable_as_source' => false, 'usable_as_target' => true, 'features' => $features],
            ['lang' => 'en-GB', 'usable_as_source' => false, 'usable_as_target' => true, 'features' => $features],
            ['lang' => 'ru', 'usable_as_source' => true, 'usable_as_target' => true, 'features' => $features],
            ['lang' => 'es', 'usable_as_source' => true, 'usable_as_target' => true, 'features' => $features],
            ['lang' => 'fr', 'usable_as_source' => true, 'usable_as_target' => true, 'features' => $features],
            ['lang' => 'pt', 'usable_as_source' => true, 'usable_as_target' => false, 'features' => $features],
            ['lang' => 'pt-BR', 'usable_as_source' => false, 'usable_as_target' => true, 'features' => $features],
            ['lang' => 'ar', 'usable_as_source' => true, 'usable_as_target' => true, 'features' => $features],
        ];
    }
}
