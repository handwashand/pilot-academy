<?php

namespace App\Services\DeepL;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The Academy's server-only boundary around DeepL text translation.
 *
 * It accepts only explicit text fields, validates the current language pair,
 * and never logs the key or the text being translated.
 */
class DeepLClient
{
    private const OFFICIAL_HOSTS = [
        'https://api-free.deepl.com',
        'https://api.deepl.com',
    ];

    private const RETRYABLE = [429, 500, 503, 504, 529];

    private const MAX_RETRIES = 2;

    private const MAX_WAIT_SECONDS = 10;

    /** Leave headroom below DeepL's 128 KiB request-body limit. */
    private const MAX_PAYLOAD_BYTES = 120 * 1024;

    /** @var array<string, string> */
    private const SOURCE_CODES = [
        'en' => 'en',
        'ru' => 'ru',
        'es' => 'es',
        'fr' => 'fr',
        'pt' => 'pt',
        'ar' => 'ar',
    ];

    public function enabled(): bool
    {
        return (bool) config('services.deepl.enabled')
            && filled(config('services.deepl.key'))
            && in_array($this->baseUrl(), self::OFFICIAL_HOSTS, true);
    }

    /** @return array<int, array<string, mixed>> */
    public function languages(): array
    {
        $this->ensureConfigured();

        return Cache::remember(
            'deepl.languages.translate_text.'.sha1($this->baseUrl()),
            now()->addHour(),
            fn (): array => $this->send('get', 'v3/languages', [
                'resource' => 'translate_text',
            ])->json(),
        );
    }

    public function supports(string $sourceCode, string $targetCode, bool $html = false): bool
    {
        $source = $this->language($this->sourceCode($sourceCode));
        $target = $this->language($this->targetCode($targetCode));

        if (! ($source['usable_as_source'] ?? false) || ! ($target['usable_as_target'] ?? false)) {
            return false;
        }

        if (! $html) {
            return true;
        }

        return isset($source['features']['tag_handling'], $target['features']['tag_handling']);
    }

    /**
     * Translate named values and return them under the same keys.
     *
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function translate(array $texts, string $sourceCode, string $targetCode, bool $html = false): array
    {
        $texts = array_filter($texts, fn (string $text): bool => filled($text));

        if ($texts === []) {
            return [];
        }

        $source = $this->sourceCode($sourceCode);
        $target = $this->targetCode($targetCode);

        if (! $this->supports($sourceCode, $targetCode, $html)) {
            throw new DeepLException('DeepL does not support this language pair or content format.', 400, 'unsupported_language_pair');
        }

        $translated = [];

        foreach ($this->batches($texts, $source, $target, $html) as $batch) {
            $payload = [
                'text' => array_values($batch),
                'source_lang' => $source,
                'target_lang' => $target,
                'preserve_formatting' => true,
                'show_billed_characters' => true,
            ];

            if ($html) {
                $payload['tag_handling'] = 'html';
                $payload['tag_handling_version'] = 'v2';
            }

            $response = $this->send('post', 'v2/translate', $payload, [
                'source_language' => $source,
                'target_language' => $target,
            ])->json('translations');

            if (! is_array($response) || count($response) !== count($batch)) {
                throw new DeepLException('DeepL returned an incomplete translation response.', 502, 'invalid_response');
            }

            foreach (array_combine(array_keys($batch), $response) as $key => $item) {
                if (! is_array($item) || ! is_string($item['text'] ?? null)) {
                    throw new DeepLException('DeepL returned an invalid translation item.', 502, 'invalid_response');
                }

                $translated[$key] = $item['text'];
            }
        }

        return $translated;
    }

    public function sourceCode(string $academyCode): string
    {
        $code = strtolower($academyCode);

        return self::SOURCE_CODES[$code]
            ?? throw new DeepLException('This Academy source language is not mapped to DeepL.', 400, 'unsupported_source');
    }

    public function targetCode(string $academyCode): string
    {
        $code = strtolower($academyCode);

        return match ($code) {
            'en' => $this->englishTarget(),
            'pt' => 'pt-BR',
            'ru', 'es', 'fr', 'ar' => $code,
            default => throw new DeepLException('This Academy target language is not mapped to DeepL.', 400, 'unsupported_target'),
        };
    }

    /** @return array<string, mixed> */
    private function language(string $code): array
    {
        foreach ($this->languages() as $language) {
            if (is_array($language) && strcasecmp((string) ($language['lang'] ?? ''), $code) === 0) {
                return $language;
            }
        }

        return [];
    }

    /** @return array<int, array<string, string>> */
    private function batches(array $texts, string $source, string $target, bool $html): array
    {
        $batches = [];
        $batch = [];

        foreach ($texts as $key => $text) {
            $candidate = [...$batch, $key => $text];

            if ($batch !== [] && $this->payloadBytes($candidate, $source, $target, $html) > self::MAX_PAYLOAD_BYTES) {
                $batches[] = $batch;
                $batch = [$key => $text];
            } else {
                $batch = $candidate;
            }

            if ($this->payloadBytes($batch, $source, $target, $html) > self::MAX_PAYLOAD_BYTES) {
                throw new DeepLException('One translation field exceeds DeepL\'s request-size limit.', 413, 'payload_too_large');
            }
        }

        if ($batch !== []) {
            $batches[] = $batch;
        }

        return $batches;
    }

    private function payloadBytes(array $texts, string $source, string $target, bool $html): int
    {
        return strlen((string) json_encode([
            'text' => array_values($texts),
            'source_lang' => $source,
            'target_lang' => $target,
            'preserve_formatting' => true,
            'show_billed_characters' => true,
            'tag_handling' => $html ? 'html' : null,
            'tag_handling_version' => $html ? 'v2' : null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function send(string $method, string $path, array $payload = [], array $context = []): Response
    {
        $this->ensureConfigured();

        try {
            $response = $this->http()->{$method}($path, $payload);
        } catch (ConnectionException) {
            Log::warning('DeepL unreachable', ['endpoint' => $path, ...$context]);

            throw new DeepLException('DeepL could not be reached.', 0, 'connection');
        }

        if ($response->failed()) {
            $this->fail($path, $response, $context);
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withHeaders(array_filter([
                'Authorization' => 'DeepL-Auth-Key '.config('services.deepl.key'),
                'X-DeepL-Reporting-Tag' => config('services.deepl.reporting_tag'),
            ]))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.deepl.timeout') ?: 30)
            ->retry(
                self::MAX_RETRIES,
                fn (int $attempt, Throwable $exception): int => $this->waitBeforeRetry($attempt, $exception),
                fn (Throwable $exception): bool => $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && in_array($exception->response->status(), self::RETRYABLE, true)),
                throw: false,
            );
    }

    private function waitBeforeRetry(int $attempt, Throwable $exception): int
    {
        $retryAfter = $exception instanceof RequestException
            ? (int) $exception->response->header('Retry-After')
            : 0;
        $seconds = $retryAfter > 0 ? min($retryAfter, self::MAX_WAIT_SECONDS) : $attempt;

        return $seconds * 1000;
    }

    /** @return never */
    private function fail(string $endpoint, Response $response, array $context): void
    {
        $body = $response->json();
        $status = $response->status();
        $code = is_array($body) ? ($body['code'] ?? null) : null;
        $message = is_array($body)
            ? ($body['message'] ?? ($body['error']['message'] ?? null))
            : null;
        $traceId = $response->header('X-Trace-ID');

        Log::warning('DeepL request failed', [
            'endpoint' => $endpoint,
            'status' => $status,
            'error' => is_string($code) ? $code : null,
            'trace_id' => $traceId,
            ...$context,
        ]);

        throw new DeepLException(
            is_string($message) ? $message : 'DeepL rejected the translation request.',
            $status,
            is_string($code) ? $code : null,
            $traceId,
        );
    }

    private function ensureConfigured(): void
    {
        if (! (bool) config('services.deepl.enabled') || blank(config('services.deepl.key'))) {
            throw new DeepLException('DeepL translation is disabled or has no API key.', 0, 'disabled');
        }

        if (! in_array($this->baseUrl(), self::OFFICIAL_HOSTS, true)) {
            throw new DeepLException('The configured DeepL API host is not allowed.', 0, 'invalid_host');
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.deepl.base_url'), '/');
    }

    private function englishTarget(): string
    {
        $target = config('services.deepl.english_target');

        return in_array($target, ['en-US', 'en-GB'], true) ? $target : 'en-US';
    }
}
