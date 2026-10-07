<?php

namespace App\Services\Descript;

use App\Models\AiProvider;
use Illuminate\Database\QueryException;
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
    /** The composition the lesson video is placed in; translations are made from it. */
    public const ORIGINAL_COMPOSITION = 'Original';

    /** Retried: rate limiting and Descript's own outages. Nothing else. */
    private const RETRYABLE = [429, 500, 502, 503, 504];

    private const MAX_RETRIES = 2;

    /** These run inside an editor's request, so a long Retry-After is capped. */
    private const MAX_WAIT_SECONDS = 10;

    /** The Descript row saved under Settings → Integrations, read once per instance. */
    private bool|AiProvider|null $row = false;

    /**
     * The saved value, or the server's .env value when no token was saved on
     * the Integrations page. A saved token is authoritative, including its
     * switch: turning it off there turns Descript off.
     */
    private function saved(string $key, mixed $fallback): mixed
    {
        if ($this->row === false) {
            try {
                $this->row = AiProvider::saved(AiProvider::DESCRIPT);
            } catch (QueryException) {
                $this->row = null; // the table does not exist until the migration has run
            }
        }

        return match (true) {
            $this->row === null => $fallback,
            $key === 'enabled' => (bool) $this->row->enabled,
            default => $this->row->api_key,
        };
    }

    /** Switched on, and a token to switch on with. */
    public function enabled(): bool
    {
        return (bool) $this->saved('enabled', config('services.descript.enabled')) && filled($this->saved('token', config('services.descript.token')));
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
        $folder = config('services.descript.project_folder');
        $access = config('services.descript.team_access');

        return $this->send('post', 'jobs/import/project_media', array_filter([
            'project_name' => $projectName,
            'folder_name' => $folder,
            // Found on the first live run: a folder without this is refused.
            'team_access' => filled($folder) ? (in_array($access, ['edit', 'comment', 'view'], true) ? $access : 'view') : null,
            'add_media' => [$mediaName => $media],
            // Found on the first live run: without this the media sits in the
            // project but in no composition, so there is nothing to transcribe,
            // translate or export (duration 0, empty transcript).
            'add_compositions' => [[
                'name' => self::ORIGINAL_COMPOSITION,
                'clips' => [['media' => $mediaName]],
            ]],
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
     * POST /jobs/publish — publishes a composition as a private audio page.
     *
     * Not for the audio: Descript's transcript export returns the composition's
     * *script*, which for a translation is still the original language (found on
     * the live run). The translated captions only appear on the published page's
     * subtitles, so a private audio publish — the cheapest one — is how they are
     * reached.
     *
     * @return array{job_id: string}
     */
    public function publish(string $projectId, string $compositionId): array
    {
        return $this->send('post', 'jobs/publish', [
            'project_id' => $projectId,
            'composition_id' => $compositionId,
            'media_type' => 'Audio',
            'access_level' => 'private',
        ])->json();
    }

    /**
     * GET /published_projects/{slug} — its `subtitles` are the whole caption
     * track as WebVTT, in the language of the composition that was published.
     *
     * @return array<string, mixed>
     */
    public function publishedProject(string $slug): array
    {
        return $this->send('get', 'published_projects/'.rawurlencode($slug))->json() ?? [];
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
            ->withToken((string) $this->saved('token', config('services.descript.token')))
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
