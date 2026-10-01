<?php

namespace App\Services\Descript;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The few Descript API calls the academy makes — nothing more. Endpoints and
 * fields are documented in docs/descript-integration.md, which records what
 * was checked against the live account and what was not.
 *
 * Two things this class never does: log the token, and log anything a video
 * says. Failures are logged by status and endpoint only.
 */
class DescriptClient
{
    /** Retried: rate limiting and Descript's own outages. Nothing else. */
    private const RETRYABLE = [429, 500, 502, 503, 504];

    private const MAX_RETRIES = 2;

    /** These run inside an editor's request, so a long Retry-After is capped. */
    private const MAX_WAIT_SECONDS = 10;

    /** Switched on, and a token to switch on with. */
    public function enabled(): bool
    {
        return (bool) config('services.descript.enabled') && filled(config('services.descript.token'));
    }

    /** GET /status — which drive the token reaches. Spends nothing. */
    public function status(): array
    {
        return $this->send('get', 'status')->json();
    }

    /**
     * POST /jobs/import/project_media — a new project in the academy's folder,
     * with one video in it. $media is either ['url' => …] or, for a direct
     * upload, ['content_type' => …, 'file_size' => …]; 'language' is the
     * spoken language, for transcription.
     *
     * @param  array<string, mixed>  $media
     * @return array{job_id: string, project_id: string, upload_urls?: array<string, array{upload_url: string}>}
     */
    public function createImport(string $projectName, string $mediaName, array $media): array
    {
        return $this->send('post', 'jobs/import/project_media', array_filter([
            'project_name' => $projectName,
            'folder_name' => config('services.descript.project_folder'),
            'add_media' => [$mediaName => $media],
        ]))->json();
    }

    /**
     * PUT a file's bytes to the signed URL a direct-upload import handed back.
     *
     * Streamed, not read into memory — a lesson video can be 200 MB. And sent
     * through a plain client: the signed URL is a storage host, not Descript's
     * API, and the Descript token has no business going there.
     */
    public function upload(string $uploadUrl, string $absolutePath): void
    {
        $stream = fopen($absolutePath, 'r');

        try {
            $response = Http::timeout(max((int) config('services.descript.timeout'), 300))
                ->withBody($stream, 'application/octet-stream')
                ->put($uploadUrl);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if ($response->failed()) {
            $this->fail('upload', $response);
        }
    }

    /** GET /jobs/{id} — a running or finished job, with its result. */
    public function job(string $jobId): array
    {
        return $this->send('get', 'jobs/'.$jobId)->json();
    }

    /**
     * POST /jobs/agent — an instruction to Descript's AI editor. This is how
     * Descript translates: there is no translate endpoint.
     */
    public function agent(string $projectId, string $prompt): array
    {
        return $this->send('post', 'jobs/agent', [
            'project_id' => $projectId,
            'prompt' => $prompt,
        ])->json();
    }

    /** GET /projects/{id} — its compositions and media. */
    public function project(string $projectId): array
    {
        return $this->send('get', 'projects/'.$projectId)->json();
    }

    /**
     * POST /export/transcript — the composition's words, returned as the file
     * itself rather than a link, so it is stored the moment it arrives.
     *
     * @param  'txt'|'srt'  $format
     */
    public function exportTranscript(string $projectId, string $compositionId, string $format): string
    {
        return $this->send('post', 'export/transcript', [
            'project_id' => $projectId,
            'composition_id' => $compositionId,
            'format' => $format,
            'include_speaker_labels' => 'off',
        ])->body();
    }

    private function send(string $method, string $path, array $payload = []): Response
    {
        try {
            $response = $this->http()->{$method}($path, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Descript unreachable', ['endpoint' => $path]);

            throw new DescriptException(__t('admin_descript.errors.unreachable'), 0, 'connection');
        }

        if ($response->failed()) {
            $this->fail($path, $response);
        }

        return $response;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.descript.base_url'), '/'))
            ->withToken((string) config('services.descript.token'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.descript.timeout') ?: 30)
            ->retry(
                self::MAX_RETRIES,
                fn (int $attempt, Throwable $e): int => $this->waitBeforeRetry($attempt, $e),
                fn (Throwable $e): bool => $e instanceof ConnectionException
                    || ($e instanceof RequestException && in_array($e->response->status(), self::RETRYABLE, true)),
                throw: false,
            );
    }

    /** Descript's Retry-After when it gives one, capped; otherwise a short backoff. */
    private function waitBeforeRetry(int $attempt, Throwable $e): int
    {
        $retryAfter = $e instanceof RequestException ? (int) $e->response->header('Retry-After') : 0;

        $seconds = $retryAfter > 0 ? min($retryAfter, self::MAX_WAIT_SECONDS) : $attempt;

        return $seconds * 1000;
    }

    /** @return never */
    private function fail(string $endpoint, Response $response): void
    {
        $status = $response->status();
        $code = (string) ($response->json('error') ?? '');

        // Status and endpoint only — never the token, never what a video says.
        Log::warning('Descript request failed', ['endpoint' => $endpoint, 'status' => $status, 'error' => $code]);

        $message = match (true) {
            $status === 402 => __t('admin_descript.errors.out_of_credits'),
            in_array($status, [401, 403], true) => __t('admin_descript.errors.auth'),
            $status === 429 => __t('admin_descript.errors.busy'),
            $status >= 500 => __t('admin_descript.errors.unavailable'),
            default => (string) ($response->json('message') ?: __t('admin_descript.errors.rejected')),
        };

        throw new DescriptException($message, $status, $code !== '' ? $code : null);
    }
}
