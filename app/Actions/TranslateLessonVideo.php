<?php

namespace App\Actions;

use App\Models\Course;
use App\Models\DescriptImport;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoTranslation;
use App\Services\Descript\DescriptClient;
use App\Services\Descript\DescriptException;
use App\Services\Translator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Translates the speech in an uploaded lesson video through Descript, and keeps
 * what comes back. See docs/descript-integration.md.
 *
 * The rules that stop credits being spent twice:
 *
 *   1. A video is imported once — every language is made from that one copy.
 *   2. A language is translated once. Done is reused; in progress is left
 *      alone; only a failed one is tried again, and only when someone asks.
 *   3. What Descript returns is stored the moment it arrives.
 *   4. A transcript somebody wrote is never overwritten.
 *   5. One translation runs per video at a time, so the new composition can be
 *      told apart from the others.
 *
 * Nothing here needs a queue worker. Each advance moves a job one step and is
 * safe to repeat; the lesson page and `descript:sync` call it.
 */
class TranslateLessonVideo
{
    /** job_id while one request is publishing, so a second cannot. */
    private const PUBLISH_STARTING = 'publishing';

    public function __construct(private DescriptClient $client) {}

    public function enabled(): bool
    {
        return $this->client->enabled();
    }

    /**
     * The uploaded videos in a lesson, path => a name an editor recognises.
     * YouTube videos are left out: Descript imports a file, and a YouTube link
     * is not one.
     *
     * @return array<string, string>
     */
    public static function uploadedVideos(Lesson $lesson): array
    {
        return collect($lesson->videoEntries())
            ->filter(fn (array $entry): bool => ($entry['type'] ?? null) === 'upload' && filled($entry['video_path'] ?? null))
            ->mapWithKeys(fn (array $entry): array => [$entry['video_path'] => basename((string) $entry['video_path'])])
            ->all();
    }

    /**
     * The uploaded videos across a course's lessons that this person may send:
     * a lesson shared in from another product is theirs to translate only if the
     * product is.
     *
     * @return array<int, array{lesson: Lesson, path: string}>
     */
    public static function courseVideos(Course $course, ?User $by): array
    {
        $videos = [];

        foreach ($course->lessons()->with('course')->get() as $lesson) {
            if (! $by?->canManageCourse($lesson->course)) {
                continue;
            }

            foreach (array_keys(static::uploadedVideos($lesson)) as $path) {
                $videos[] = ['lesson' => $lesson, 'path' => $path];
            }
        }

        return $videos;
    }

    /**
     * Every language this lesson can be translated into: the active ones, less
     * the language it is written in.
     *
     * @return array<string, string> code => native name
     */
    public static function targetLanguages(Lesson $lesson): array
    {
        return app(Translator::class)->activeLanguages()
            ->reject(fn (Language $language): bool => $language->code === $lesson->contentLanguageCode())
            ->mapWithKeys(fn (Language $language): array => [$language->code => $language->native_name])
            ->all();
    }

    /**
     * Ask for translations of one video. Languages already done or under way
     * are not asked for again — that is the point of this class.
     *
     * @param  array<int, string>  $languages
     * @param  bool  $advance  false only for a whole course: record the requests, and let Check progress
     *                         and descript:sync start them a few at a time rather than in one web request.
     * @return array{requested: array<int, string>, done: array<int, string>, running: array<int, string>}
     */
    public function request(Lesson $lesson, string $videoPath, array $languages, ?User $by = null, bool $advance = true): array
    {
        if (! array_key_exists($videoPath, static::uploadedVideos($lesson))) {
            throw new InvalidArgumentException('That video is not an uploaded video of this lesson.');
        }

        $allowed = array_keys(static::targetLanguages($lesson));
        $languages = array_values(array_intersect(array_unique($languages), $allowed));

        $import = DescriptImport::firstOrCreate(
            ['lesson_id' => $lesson->id, 'video_path' => $videoPath],
            [
                'file_size' => Storage::disk('public')->exists($videoPath) ? Storage::disk('public')->size($videoPath) : null,
                'source_language' => $lesson->contentLanguageCode(),
                'status' => DescriptImport::STATUS_PENDING,
            ],
        );

        // A failed import is tried again on request, like a failed translation.
        if ($import->status === DescriptImport::STATUS_FAILED) {
            $import->update(['status' => DescriptImport::STATUS_PENDING, 'job_id' => null, 'error' => null]);
        }

        $outcome = ['requested' => [], 'done' => [], 'running' => []];

        foreach ($languages as $code) {
            $row = VideoTranslation::firstOrCreate(
                ['descript_import_id' => $import->id, 'language' => $code, 'kind' => VideoTranslation::KIND_TRANSCRIPT],
                ['lesson_id' => $lesson->id, 'status' => VideoTranslation::STATUS_PENDING, 'requested_by' => $by?->id],
            );

            if ($row->wasRecentlyCreated) {
                $outcome['requested'][] = $code;
            } elseif ($row->isDone()) {
                $outcome['done'][] = $code;
            } elseif ($row->status === VideoTranslation::STATUS_FAILED) {
                $row->update([
                    'status' => VideoTranslation::STATUS_PENDING,
                    'job_id' => null,
                    'composition_id' => null,
                    'compositions_before' => null,
                    'error' => null,
                    'requested_by' => $by?->id,
                ]);
                $outcome['requested'][] = $code;
            } else {
                $outcome['running'][] = $code;
            }
        }

        if ($advance && $outcome['requested'] !== []) {
            $this->advanceLesson($lesson);
        }

        return $outcome;
    }

    /** Move everything still in progress for one lesson along by a step. */
    public function advanceLesson(Lesson $lesson): void
    {
        VideoTranslation::query()
            ->where('lesson_id', $lesson->id)
            ->inFlight()
            ->orderBy('id')
            ->get()
            ->each(fn (VideoTranslation $translation) => $this->advance($translation));
    }

    /**
     * Move everything in progress, everywhere, along by a step.
     *
     * @return int how many were looked at
     */
    public function advanceAll(): int
    {
        $rows = VideoTranslation::query()->inFlight()->orderBy('id')->get();

        $rows->each(fn (VideoTranslation $translation) => $this->advance($translation));

        return $rows->count();
    }

    public function advance(VideoTranslation $translation): void
    {
        if (! $this->enabled() || ! $translation->isInFlight()) {
            return;
        }

        try {
            match ($translation->status) {
                VideoTranslation::STATUS_PENDING => $this->start($translation),
                VideoTranslation::STATUS_TRANSLATING => $this->checkTranslation($translation),
                VideoTranslation::STATUS_EXPORTING => $this->export($translation),
                default => null,
            };
        } catch (DescriptException $e) {
            $this->failed($translation, $e);
        }
    }

    public function advanceImport(DescriptImport $import): void
    {
        if (! $this->enabled()) {
            return;
        }

        try {
            match ($import->status) {
                DescriptImport::STATUS_PENDING => $this->startImport($import),
                DescriptImport::STATUS_IMPORTING => $this->checkImport($import),
                default => null,
            };
        } catch (DescriptException $e) {
            // Busy or briefly down: try again on the next advance.
            if ($this->isTransient($e)) {
                $import->update(['error' => $e->getMessage()]);

                return;
            }

            $import->update(['status' => DescriptImport::STATUS_FAILED, 'error' => $e->getMessage()]);
        }
    }

    private function start(VideoTranslation $translation): void
    {
        $import = $translation->descriptImport;

        if (! $import->isReady()) {
            $this->advanceImport($import);
            $import->refresh();

            if ($import->status === DescriptImport::STATUS_FAILED) {
                $translation->update(['status' => VideoTranslation::STATUS_FAILED, 'error' => $import->error]);
            }

            if (! $import->isReady()) {
                return;
            }
        }

        // Rule 5: one at a time per video.
        $busy = VideoTranslation::query()
            ->where('descript_import_id', $import->id)
            ->whereKeyNot($translation->id)
            ->where('status', VideoTranslation::STATUS_TRANSLATING)
            ->exists();

        if ($busy) {
            return;
        }

        // Claim the row before spending anything: of two requests arriving
        // together, only one moves it out of pending, and only that one calls.
        if (! $this->claim($translation, VideoTranslation::STATUS_PENDING, VideoTranslation::STATUS_TRANSLATING)) {
            return;
        }

        $prompt = strtr((string) config('services.descript.translate_prompt'), [
            '{source}' => DescriptClient::ORIGINAL_COMPOSITION,
            '{language}' => $this->englishName($translation->language),
            '{name}' => $this->compositionName($translation->language),
        ]);

        try {
            $before = $this->compositionIds($import->project_id);
            $job = $this->client->agent($import->project_id, $prompt);
        } catch (DescriptException $e) {
            // Nothing was started, so a transient failure goes back in the queue.
            $translation->update(['status' => VideoTranslation::STATUS_PENDING]);

            throw $e;
        }

        $translation->update([
            'job_id' => $job['job_id'] ?? null,
            'compositions_before' => $before,
            'error' => null,
        ]);
    }

    /** Move a row from one status to another only if it is still in the first. */
    private function claim(VideoTranslation|DescriptImport $row, string $from, string $to): bool
    {
        $claimed = $row::query()->whereKey($row->getKey())->where('status', $from)->update(['status' => $to]) === 1;

        if ($claimed) {
            $row->status = $to;
            $row->syncOriginalAttribute('status');
        }

        return $claimed;
    }

    private function checkTranslation(VideoTranslation $translation): void
    {
        if (blank($translation->job_id)) {
            $translation->update(['status' => VideoTranslation::STATUS_FAILED, 'error' => __t('admin_descript.errors.translation_failed')]);

            return;
        }

        $job = $this->client->job((string) $translation->job_id);

        if (($job['job_state'] ?? null) === 'running') {
            return;
        }

        $result = $job['result'] ?? [];

        if (($job['job_state'] ?? null) !== 'stopped' || ($result['status'] ?? null) !== 'success') {
            $translation->update([
                'status' => VideoTranslation::STATUS_FAILED,
                'error' => $result['error_message'] ?? __t('admin_descript.errors.translation_failed'),
                'agent_response' => $result['agent_response'] ?? null,
            ]);

            return;
        }

        $compositionId = $this->findComposition($translation);

        if ($compositionId === null) {
            $translation->update([
                'status' => VideoTranslation::STATUS_FAILED,
                'error' => __t('admin_descript.errors.composition_not_found'),
                'agent_response' => $result['agent_response'] ?? null,
                'ai_credits_used' => (int) ($result['ai_credits_used'] ?? 0),
            ]);

            return;
        }

        $translation->update([
            'status' => VideoTranslation::STATUS_EXPORTING,
            'composition_id' => $compositionId,
            // The agent job is finished with; from here job_id is the publish job.
            'job_id' => null,
            'agent_response' => $result['agent_response'] ?? null,
            'ai_credits_used' => (int) ($result['ai_credits_used'] ?? 0),
        ]);

        $this->export($translation->refresh());
    }

    /**
     * Rule 3: store what comes back. Rule 4: never over a person's work.
     *
     * The translated words are not in Descript's transcript export (that returns
     * the original script — found on the live run). They are the subtitles of a
     * published page, so the composition is published once, privately and as
     * audio, and its WebVTT is stored. One call starts the publish, later calls
     * wait for it; the claim means two clicks cannot publish twice.
     */
    private function export(VideoTranslation $translation): void
    {
        $import = $translation->descriptImport;

        if ($translation->job_id === self::PUBLISH_STARTING) {
            return;
        }

        if ($translation->job_id === null) {
            $claimed = VideoTranslation::query()
                ->whereKey($translation->getKey())
                ->where('status', VideoTranslation::STATUS_EXPORTING)
                ->whereNull('job_id')
                ->update(['job_id' => self::PUBLISH_STARTING]) === 1;

            if (! $claimed) {
                return;
            }

            try {
                $job = $this->client->publish($import->project_id, (string) $translation->composition_id);
            } catch (DescriptException $e) {
                $translation->update(['job_id' => null]);

                throw $e;
            }

            $translation->update(['job_id' => $job['job_id'] ?? null]);

            // Publishing takes a few seconds; the next check collects it.
            return;
        }

        $job = $this->client->job($translation->job_id);

        if (($job['job_state'] ?? null) !== 'stopped') {
            return;
        }

        $result = $job['result'] ?? [];
        $slug = ($result['status'] ?? null) === 'success' && filled($result['share_url'] ?? null)
            ? basename((string) parse_url((string) $result['share_url'], PHP_URL_PATH))
            : null;

        if (! $slug) {
            $translation->update([
                'status' => VideoTranslation::STATUS_FAILED,
                'error' => $result['error_message'] ?? __t('admin_descript.errors.translation_failed'),
            ]);

            return;
        }

        $vtt = trim((string) ($this->client->publishedProject($slug)['subtitles'] ?? ''));
        $text = VideoTranslation::textFromVtt($vtt);

        if ($text === '') {
            $translation->update([
                'status' => VideoTranslation::STATUS_FAILED,
                'error' => __t('admin_descript.errors.no_subtitles'),
            ]);

            return;
        }

        $subtitlePath = sprintf('video-translations/lesson-%d/%d-%s.vtt', $translation->lesson_id, $import->id, $translation->language);
        Storage::disk('public')->put($subtitlePath, $vtt."\n");

        $translation->update([
            'status' => VideoTranslation::STATUS_DONE,
            'transcript' => $text,
            'subtitle_path' => $subtitlePath,
            'error' => null,
            'completed_at' => now(),
        ]);

        $lesson = $translation->lesson;
        $language = app(Translator::class)->activeLanguage($translation->language);

        $alreadyWritten = $language && $lesson->contentTranslations()
            ->where('field', 'transcript')
            ->where('language_id', $language->id)
            ->exists();

        if ($text !== '' && ! $alreadyWritten) {
            $lesson->setTranslation('transcript', $translation->language, $text);
        }
    }

    private function startImport(DescriptImport $import): void
    {
        $lesson = $import->lesson;
        $disk = Storage::disk('public');

        if (! $disk->exists($import->video_path)) {
            throw new DescriptException(__t('admin_descript.errors.file_missing'), 0, 'file_missing');
        }

        $mediaName = basename($import->video_path);
        $projectName = Str::limit(sprintf('Lesson %d — %s', $lesson->id, $lesson->title), 120, '');

        // Descript fetches the file itself from a public address; PHP never
        // streams it. A local machine has no public address, so it uploads.
        $media = $this->canFetchFromUrl()
            ? ['url' => $disk->url($import->video_path)]
            : ['content_type' => $disk->mimeType($import->video_path) ?: 'video/mp4', 'file_size' => $disk->size($import->video_path)];

        $media['language'] = $import->source_language;

        // Rule 1, held under concurrency: whichever request claims the row is
        // the only one that imports.
        if (! $this->claim($import, DescriptImport::STATUS_PENDING, DescriptImport::STATUS_IMPORTING)) {
            return;
        }

        try {
            $job = $this->client->createImport($projectName, $mediaName, $media);
        } catch (DescriptException $e) {
            $import->update(['status' => DescriptImport::STATUS_PENDING]);

            throw $e;
        }

        $import->update([
            'job_id' => $job['job_id'] ?? null,
            'project_id' => $job['project_id'] ?? null,
            'error' => null,
        ]);

        $uploadUrl = $job['upload_urls'][$mediaName]['upload_url'] ?? null;

        if ($uploadUrl) {
            $this->client->upload($uploadUrl, $disk->path($import->video_path));
        }
    }

    private function checkImport(DescriptImport $import): void
    {
        if (blank($import->job_id)) {
            $import->update(['status' => DescriptImport::STATUS_FAILED, 'error' => __t('admin_descript.errors.import_failed')]);

            return;
        }

        $job = $this->client->job((string) $import->job_id);

        if (($job['job_state'] ?? null) === 'running') {
            return;
        }

        $result = $job['result'] ?? [];
        $mediaOk = collect($result['media_status'] ?? [])->contains(fn ($media): bool => ($media['status'] ?? null) === 'success');

        if (($job['job_state'] ?? null) === 'stopped' && in_array($result['status'] ?? null, ['success', 'partial'], true) && $mediaOk) {
            $import->update([
                'status' => DescriptImport::STATUS_READY,
                'media_seconds_used' => (int) ($result['media_seconds_used'] ?? 0),
                'error' => null,
            ]);

            return;
        }

        $import->update([
            'status' => DescriptImport::STATUS_FAILED,
            'error' => $result['error_message'] ?? __t('admin_descript.errors.import_failed'),
        ]);
    }

    /**
     * The composition the translation made: by the name it was asked to use,
     * and failing that, the only one that was not there before.
     */
    private function findComposition(VideoTranslation $translation): ?string
    {
        $compositions = $this->compositions($translation->descriptImport->project_id);
        $wanted = Str::lower($this->compositionName($translation->language));

        $named = $compositions->filter(fn (array $c): bool => Str::lower(trim((string) ($c['name'] ?? ''))) === $wanted);

        if ($named->count() === 1) {
            return (string) $named->first()['id'];
        }

        $new = $compositions->reject(fn (array $c): bool => in_array($c['id'] ?? null, $translation->compositions_before ?? [], true));

        return $new->count() === 1 ? (string) $new->first()['id'] : null;
    }

    /** @return Collection<int, array{id: string, name?: string}> */
    private function compositions(string $projectId)
    {
        $project = $this->client->project($projectId);

        // A single project's shape is not yet confirmed on a live account;
        // the projects list wraps its rows in "data", so accept both.
        return collect($project['compositions'] ?? $project['data']['compositions'] ?? [])
            ->filter(fn ($c): bool => is_array($c) && filled($c['id'] ?? null))
            ->values();
    }

    /** @return array<int, string> */
    private function compositionIds(string $projectId): array
    {
        return $this->compositions($projectId)->pluck('id')->map(fn ($id): string => (string) $id)->all();
    }

    private function compositionName(string $code): string
    {
        return 'Pilot Academy — '.$code;
    }

    /** Descript is told the language in English, e.g. "Portuguese (Brazil)". */
    private function englishName(string $code): string
    {
        return app(Translator::class)->activeLanguage($code)?->name ?? strtoupper($code);
    }

    private function canFetchFromUrl(): bool
    {
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        return $host !== ''
            && ! in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true)
            && ! Str::endsWith($host, ['.test', '.local', '.localhost']);
    }

    private function failed(VideoTranslation $translation, DescriptException $e): void
    {
        // Busy or briefly down: leave it where it is for the next advance.
        if ($this->isTransient($e)) {
            $translation->update(['error' => $e->getMessage()]);

            return;
        }

        $translation->update(['status' => VideoTranslation::STATUS_FAILED, 'error' => $e->getMessage()]);
    }

    private function isTransient(DescriptException $e): bool
    {
        return $e->status === 429 || $e->status >= 500 || $e->errorCode === 'connection';
    }
}
