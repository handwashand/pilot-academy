# Descript transcription integration plan

> Status: **Planning only - do not implement yet.**
>
> The product owner has a Descript API token. Do not put it in this document,
> source control, a chat transcript, or a browser request. Application work starts
> only after the decisions in [Decisions required](#decisions-required) are made
> and implementation is explicitly approved.

## Recommendation at a glance

- Add an editor-initiated **Generate transcript with Descript** workflow to the
  existing lesson edit page.
- Start with lesson videos uploaded to Pilot Academy. Descript does not accept
  YouTube URLs as import sources.
- Send every uploaded clip in the lesson, in display order, to one Descript
  project and composition so the result matches the lesson's single transcript.
- Store Descript output as a review candidate. Never overwrite the live lesson
  transcript automatically.
- Use the existing lesson transcript as the only learner-facing source of truth.
- Poll Descript when an editor checks progress. The first release does not need
  a production queue worker or an unauthenticated callback endpoint.
- Keep the integration behind `DESCRIPT_ENABLED=false` until staging approval.
- Use Laravel's HTTP client; no new package is necessary.

## Goal

Reduce the manual work of producing searchable, accessible lesson transcripts
without weakening the Academy's existing publishing, permissions, translation,
or privacy rules.

The integration must:

- generate a plain-text draft from eligible lesson video uploads;
- let an authorized editor review and correct it before applying it;
- preserve an existing transcript until the editor deliberately replaces it;
- keep the API token and all Descript requests on the server;
- record enough local job history to diagnose failures after Descript's job
  history expires;
- handle unavailable service, exhausted media minutes, and rate limits without
  damaging lesson content;
- prevent duplicate paid jobs caused by repeated clicks; and
- remain optional so manual transcript entry always works.

## Non-goals for the first release

- Editing or rendering lesson videos in Descript.
- Underlord edits, captions burned into video, dubbing, clips, or publishing.
- Automatically transcribing YouTube links.
- Automatically publishing a generated transcript to learners.
- Translating a transcript. The existing Academy translation workflow remains
  separate.
- Adding transcripts to Tutorials. Tutorials do not currently have a transcript
  field or learner transcript view.
- Sending customer recordings, live vehicle data, credentials, or identifying
  media to Descript.

## What exists today

### Lesson media

- `Lesson::videoEntries()` is the compatibility boundary for lesson video
  sources. A lesson can contain up to five ordered entries.
- Each entry is either a YouTube URL or an uploaded file on the public disk.
- Uploaded lesson videos accept MP4, WebM, and QuickTime files up to 200 MB.
- Older lessons can still use the legacy `youtube_url` or `video_path`; the
  integration must read through `videoEntries()` instead of querying those
  columns directly.
- The public lesson page serves uploaded video from the public disk and embeds
  valid YouTube videos through the existing privacy-enhanced path.

### Transcript behavior

- `lessons.transcript` is nullable plain text and is already part of the lesson
  edit form.
- It is shown in a collapsed section on the lesson page.
- Student search indexes the source transcript and translated transcript fields.
- `Lesson::$translatable` includes `transcript`, so a generated source transcript
  can later use the existing Translate action.
- Draft and archived lesson visibility rules already protect transcripts from
  ordinary learners. This integration must not create a second read path.

### Permissions

- Admins and creators can use the panel; learners cannot.
- `LessonPolicy::update()` delegates to `User::canManageCourse()`.
- Creator lesson queries are scoped to products they own.
- Every generation, refresh, review, apply, retry, and open-project action must
  enforce the same update policy on the server. Hiding a button is not enough.

### Operations

- Production currently has no long-running queue worker.
- The deploy already creates the public storage link and caches configuration.
- Laravel's HTTP client is available and supports fakes for tests.
- Manual transcript entry must continue when Descript is disabled or down.

## Confirmed Descript API behavior

Checked against Descript's official API documentation on 2026-10-01:

- Base URL: `https://descriptapi.com/v1`.
- Authentication: `Authorization: Bearer <token>`. A personal token is tied to
  one Descript Drive and inherits that user's permissions.
- `POST /jobs/import/project_media` creates a project, imports media, creates a
  composition, and starts transcription. It returns a `job_id` immediately.
- Media can be supplied by a public or presigned URL. Direct upload is also a
  documented two-request flow, but is unnecessary for the first Academy release
  because lesson uploads already have public URLs.
- Multiple media files can be placed into one composition in a defined order.
- `GET /jobs/{job_id}` reports `queued`, `running`, `stopped`, or `cancelled`.
  A stopped job is successful only when `result.status` says so.
- `POST /export/transcript` returns the transcript synchronously. Plain text,
  Markdown, HTML, RTF, DOCX, and SRT are available.
- Plain text with speaker labels off, markers off, and no timecodes fits the
  Academy's existing plain-text transcript field.
- Importing media consumes Descript media minutes. An exhausted allowance can
  return HTTP 402.
- HTTP 429 includes `Retry-After`; clients must respect it.
- Descript API job history is available for at most 30 days, so the Academy
  must retain its own audit record.
- YouTube URLs are not supported as import sources.
- Descript currently transcribes Latin-alphabet languages only. Academy lesson
  languages map as follows:

| Academy language | Descript transcription | First-release behavior |
| --- | --- | --- |
| `en` | Supported | Send `en` |
| `es` | Supported | Send `es` |
| `fr` | Supported | Send `fr` |
| `pt` | Brazilian Portuguese supported | Send `pt` |
| `ru` | Not supported | Disable generation and explain why |
| `ar` | Not supported | Disable generation and explain why |

Descript supports one spoken language per file. Mixed-language lessons are not
eligible for automatic generation in the first release.

## Proposed editor workflow

1. An editor saves the lesson and opens its edit page.
2. **Generate transcript with Descript** appears only when the feature is
   enabled, the editor is authorized, and the lesson has eligible media.
3. A confirmation dialog lists the uploaded clips, selected language, existing
   transcript status, and the fact that Descript media minutes will be used.
4. The editor starts the job. A second click cannot start another active job for
   the same lesson and source version.
5. The page shows **Waiting**, **Processing**, **Ready for review**, or **Failed**
   with a last-checked time. **Check status** polls Descript on demand.
6. When import succeeds, the server exports a plain-text transcript and stores
   it as a candidate. It does not change `lessons.transcript`.
7. The editor opens a review modal containing the current transcript and the
   generated candidate in separate, clearly labelled fields. The candidate is
   editable before use.
8. **Use this transcript** requires confirmation when a transcript already
   exists or when the lesson is published.
9. The server verifies that the lesson's video fingerprint still matches the
   run, then writes the reviewed text to `lessons.transcript` and records who
   applied it and when.
10. The ordinary lesson page, search, fallback, and translation paths pick up
    the transcript without any student-side Descript dependency.

The editor can always ignore or discard a candidate and continue typing in the
existing transcript field.

## Source eligibility and ordering

The first release should use these strict rules:

- Read sources only from `Lesson::videoEntries()`.
- Require at least one uploaded video whose file exists on the public disk.
- Include all uploaded clips in their lesson display order.
- Block generation when any entry is YouTube. A partial transcript would look
  complete while silently omitting part of the lesson.
- Block Russian, Arabic, and known mixed-language material.
- Derive every media URL from the stored public-disk path. Never accept a media
  URL from form state or a request parameter.
- Require an absolute HTTPS URL outside local development. Staging must prove
  that Descript can fetch the URL before production enablement.
- Generate safe Descript display names from the lesson ID and clip order; do not
  send customer or company names in project or file names.
- Put projects in a dedicated `Pilot Academy/Transcriptions` Drive folder when
  the API supports the configured folder path.

For each run, hash the ordered stored paths plus file size and modification time.
This `source_fingerprint` makes a completed candidate stale if a clip is added,
removed, replaced, or reordered. A stale candidate remains visible for audit but
cannot be applied.

## Proposed architecture

### Configuration

Add placeholders only after implementation is approved:

```dotenv
DESCRIPT_ENABLED=false
DESCRIPT_API_TOKEN=
DESCRIPT_API_BASE_URL=https://descriptapi.com/v1
DESCRIPT_PROJECT_FOLDER="Pilot Academy/Transcriptions"
DESCRIPT_TIMEOUT_SECONDS=30
```

Map them under `services.descript` in `config/services.php`. Application code
must read `config()`, never call `env()` directly. The real token belongs only in
the server's secret-managed `.env`. Add only the empty variable name to
`.env.example`.

Use separate staging and production tokens if the Descript account permits it.
Each should be tied to the intended Drive. Rotating the token must require only
changing the environment value and rebuilding Laravel's config cache.

### HTTP client

Add one small `DescriptClient` around Laravel's HTTP client. It should own:

- the base URL, bearer token, connect timeout, and request timeout;
- `status()` for configuration checks;
- `startProjectImport()`;
- `getJob()`;
- `exportPlainTranscript()`;
- strict response-shape validation and typed application exceptions; and
- sanitized logging that records status, endpoint, job ID, and lesson/run ID,
  never the token, signed URLs, transcript body, or raw media URL.

Safe retry rules:

- Retry idempotent status and transcript-export requests for transient network
  failures and 5xx responses.
- Respect `Retry-After` on 429 responses.
- Do not automatically retry an import POST unless Descript documents an
  idempotency mechanism. A blind retry can create a second paid project.
- Cap response sizes before storing transcript text.

No third-party SDK is needed. Descript's API is beta, so keeping the boundary
small reduces the cost of contract changes.

### Local job record

Create a `lesson_transcriptions` table instead of putting temporary API state on
`lessons`:

| Column | Purpose |
| --- | --- |
| `id` | Local run identifier |
| `lesson_id` | Owning lesson; cascade on lesson deletion |
| `requested_by_user_id` | Editor who spent the media minutes; nullable on user deletion |
| `applied_by_user_id` | Editor who approved the candidate; nullable |
| `status` | `starting`, `processing`, `review_ready`, `applied`, `failed`, `cancelled`, or `stale` |
| `language` | ISO language sent to Descript |
| `source_fingerprint` | Detect changed or reordered lesson media |
| `source_count` | Audit context without storing public URLs |
| `descript_job_id` | Descript import job identifier; unique when present |
| `descript_project_id` | Project needed for transcript export |
| `descript_composition_id` | Exact composition exported |
| `descript_project_url` | Editor-only review link returned by Descript |
| `generated_transcript` | Review candidate, never rendered to learners directly |
| `media_seconds_used` | Cost/audit information when returned |
| `error_code` | Stable local/API category |
| `error_message` | Sanitized editor-facing diagnostic |
| `last_checked_at` | Last successful or failed poll |
| `completed_at` | Candidate generated or terminal failure time |
| `applied_at` | Candidate copied into the lesson |
| timestamps | Local audit history |

Use a model relation from `Lesson` and explicit status constants. Keep provider
IDs as strings because their exact format is provider-owned. Do not retain the
full Descript request or response payload.

Only one active run may exist per lesson. Reserve a `starting` run inside a
short database transaction with a locked lesson row, then commit before making
the external request. This prevents double submits without holding a database
lock during network I/O. Mark the reservation `processing` after a valid API
response, or `failed` after a known failure. A failed, cancelled, applied, or
stale run does not block a later retry. A `starting` run abandoned by a process
crash needs an expiry and manual retry warning because Descript may have accepted
the request even when the Academy never received its job ID.

### Orchestration actions

Keep provider calls and state transitions out of the Filament page:

- `StartLessonTranscription` validates authorization, feature configuration,
  language, files, and duplicate runs; builds the composition; starts import;
  and records provider IDs.
- `RefreshLessonTranscription` checks job status. On success it exports plain
  text, validates it, stores the candidate, and marks the run ready.
- `ApplyLessonTranscription` re-authorizes, compares the source fingerprint,
  normalizes line endings, rejects blank/oversized output, updates the lesson,
  and records approval.

The Filament action should call these application actions and show translated
notifications. This leaves the business rules testable without a browser.

### Polling instead of callbacks

Descript accepts a `callback_url`, but the current public documentation does not
describe a callback signature that the Academy can verify. Do not create a
state-changing public webhook based only on a job ID.

For the first release, **Check status** calls the server, which polls Descript.
An optional short-lived `wire:poll` while the editor keeps the page open is fine,
but it must stop at a terminal state and back off on 429. This works with the
current production setup and does not pretend a queue worker exists.

If automatic background completion is wanted later, first deploy and monitor a
real queue worker, then schedule status polling. Do not use request-deferred work
for media jobs.

## Security, privacy, and cost controls

- Keep the token server-only and redact authorization headers from exceptions,
  logs, debug output, and notifications.
- The token is personal and Drive-scoped. Use a dedicated least-privileged
  Descript account/Drive where practical, and revoke it immediately if exposed.
- Treat media sent to Descript as third-party processing. Confirm organizational
  approval, contract terms, retention expectations, and permitted content before
  enabling production.
- Training media must not include customer names, locations, live vehicle data,
  credentials, identifying screenshots, or other data that the Academy would
  not intentionally share with Descript.
- Do not publish Descript project/share links. Store the project URL only for
  authorized editors and open it with `noopener noreferrer`.
- Escape generated text in review UI and on the learner page. Export `txt`, not
  provider HTML.
- Validate lesson ownership and run ownership from the database; never trust a
  lesson ID, run ID, URL, language, or provider ID supplied by the browser.
- Limit one active run per lesson and add an application rate limit to the start
  action. Show that each run consumes media minutes.
- Surface 402 as **Descript media minutes unavailable**, 401/403 as a private
  configuration problem, 429 with a retry time, and provider 5xx/timeouts as a
  retryable service failure.
- Keep generated projects grouped in one Descript folder. The current API does
  not document project deletion, so the Drive owner needs a manual retention and
  cleanup routine until a supported deletion endpoint exists.

## Failure and recovery behavior

| Situation | Required behavior |
| --- | --- |
| Disabled or token missing | Hide/disable generation; manual transcript remains available |
| No upload, or any YouTube entry | Explain why the lesson is not eligible; make no API call |
| Unsupported or mixed language | Explain limitation; make no API call |
| Source file missing | Identify clip order, mark no run active, make no paid call |
| Import request times out before an ID returns | Mark `failed` with an unknown-outcome error; do not auto-retry the POST |
| HTTP 401/403 | Redacted configuration error for editor; detailed safe server log |
| HTTP 402 | Media-minute message; preserve content and allow retry after account action |
| HTTP 429 | Respect `Retry-After`; do not spin or repeatedly notify |
| Job queued/running | Keep current transcript and show last check time |
| Job stopped with failure/cancelled | Store sanitized reason and offer a deliberate retry |
| Transcript export blank or too large | Fail validation; never replace lesson transcript |
| Video changes during processing | Mark candidate stale and require a new run |
| Existing transcript changes during processing | Keep both; require confirmation before replacement |
| Provider unavailable | Manual editing continues; existing learner content is unaffected |

Because Descript job lookup expires after 30 days, a still-processing local run
older than 30 days should become `failed` with a job-expired error and a clear
explanation. It must not remain an eternal active lock.

## Files expected to change during implementation

Exact names can follow the code at implementation time, but this is the expected
surface.

### New

- `app/Models/LessonTranscription.php`
- `app/Services/DescriptClient.php`
- `app/Actions/StartLessonTranscription.php`
- `app/Actions/RefreshLessonTranscription.php`
- `app/Actions/ApplyLessonTranscription.php`
- `database/migrations/*_create_lesson_transcriptions_table.php`
- `tests/Feature/DescriptTranscriptionTest.php`
- focused client tests if they are clearer outside the feature test

### Changed

- `app/Models/Lesson.php`
- `app/Filament/Resources/Lessons/Pages/EditLesson.php`
- possibly a small Filament action/view for the review interface
- `config/services.php`
- `.env.example`
- `lang/{en,ru,es,fr,pt,ar}/admin_lessons.php`
- `docs/admin-guide.md` and all five localized admin-guide copies
- `README.md`, `DEPLOY.md`, `docs/CHANGELOG.md`, and `agents.md`

No learner controller, route, search query, or lesson Blade template should need
a Descript-specific change because the approved output uses the existing
`lessons.transcript` path.

## Implementation work packages

These packages are sized so separate agents can take them without editing the
same core file at the same time.

### Package 1 - API boundary and configuration

**Depends on:** owner approval and a non-production token for a smoke test.

- Add disabled-by-default configuration and empty environment placeholders.
- Implement the HTTP client, response validation, exceptions, retry rules, and
  redacted logs.
- Test with `Http::fake()` for success, malformed payloads, 401, 402, 403, 429,
  timeout, and 5xx behavior.
- Run one explicit staging `/status` check without printing the token.

**Verification:** focused tests, Pint, config-cache check, and secret scan.

### Package 2 - Persistence and orchestration

**Can start after Package 1 interfaces are agreed.**

- Add the migration, model, lesson relation, statuses, and source fingerprint.
- Implement start, refresh/export, apply, stale, expiry, and duplicate-run rules.
- Cover legacy uploads, multiple ordered uploads, missing files, changed sources,
  existing transcripts, and creator authorization.

**Verification:** migration up/down on SQLite tests and PostgreSQL-compatible
schema review; focused feature tests; no real network calls.

### Package 3 - Filament editor workflow and localization

**Depends on:** Package 2 action contracts.

- Add generate, status, review, apply, retry, and open-in-Descript controls to
  the existing lesson edit page.
- Reuse Filament actions, modals, notifications, and the lesson policy.
- Add every visible string to all six admin language files.
- Handle disabled, ineligible, processing, failed, stale, ready, and applied
  states without losing unsaved lesson form changes.

**Verification:** Livewire/Filament action tests, creator-scope tests, keyboard
review, and browser checks at desktop panel widths.

### Package 4 - Operations, documentation, and release

**Can prepare alongside Package 3.**

- Document token setup, Drive scope, key rotation, supported languages, media
  minutes, troubleshooting, retention, and rollback.
- Update the admin guides in every shipped language and add a plain-language
  changelog entry.
- Add staging and production enablement checks.
- Verify that no secret, transcript body, signed URL, or raw provider response is
  committed or logged.

**Verification:** clean-environment setup review, full suite, focused tests,
Pint on changed PHP files, `git diff --check`, and final browser exercise.

## Test matrix

### Eligibility and authorization

- Admin can generate for an eligible lesson.
- Creator can act only if the chosen access decision allows it and the lesson is
  in a product they own.
- Another creator and every learner are denied server-side.
- Disabled/unconfigured integration makes no HTTP request.
- YouTube-only, mixed YouTube/upload, Russian, Arabic, mixed-language, missing,
  and empty sources make no HTTP request.
- Legacy uploaded video and multiple ordered uploaded videos work.

### Job lifecycle

- Starting stores local and provider identifiers without changing transcript.
- Double submit creates one paid import job.
- Queued/running states remain active.
- Stopped/success exports the exact composition as plain text.
- Stopped/failure, cancelled, malformed responses, expired jobs, and every
  relevant HTTP error produce safe terminal or retry states.
- 429 observes `Retry-After`; import POST is not blindly retried.

### Review and apply

- Current and candidate transcripts are both visible to the authorized editor.
- Candidate edits are what get applied.
- Existing transcript is never overwritten without confirmation.
- Blank and oversized candidates are rejected.
- Changed/reordered/replaced video makes the candidate stale.
- Applying records actor/time and uses the existing transcript field.
- Published, draft, archived, translated, search, and learner fallback behavior
  remain unchanged.

### Regression and operations

- Student search still finds transcript text case-insensitively on PostgreSQL.
- Unpublished lessons remain unreachable and unsearchable to ordinary partners.
- The token is absent from rendered HTML, Livewire payloads, logs, exceptions,
  snapshots, fixtures, and Git.
- Config caching, migration rollback, manual transcript entry, and feature-flag
  rollback work.
- Full existing test suite and focused tests pass.

## Rollout and rollback

1. Merge and deploy with `DESCRIPT_ENABLED=false` and no production token.
2. Run migrations and rebuild Laravel caches.
3. Configure a staging token tied to the intended Descript Drive.
4. Use a short synthetic MP4 with no customer information to verify status,
   ordered import, polling, text export, review, and application.
5. Test English, Spanish, French, and Brazilian Portuguese samples. Confirm
   Russian and Arabic are blocked before any request.
6. Confirm usage in Descript and inspect Academy logs for redaction.
7. Enable production for the approved editor group and one synthetic lesson.
8. Monitor failures, latency, duplicate prevention, media-minute use, and editor
   corrections before wider use.

**Rollback:** set `DESCRIPT_ENABLED=false` and rebuild config cache. Existing
transcripts and learner pages continue to work because they do not call
Descript. In-progress runs remain as audit records and can be marked cancelled
or expired; do not delete lesson transcripts during rollback.

## Decisions required

Implementation must wait for these answers:

1. **Who can spend Descript media minutes?**
   Recommendation: admins only for the pilot; add scoped creators after usage is
   understood.
2. **Is sending Academy training media to Descript approved?**
   Confirm contract/privacy terms, allowed content, Drive ownership, retention,
   and who performs project cleanup.
3. **Is the strict source rule approved?**
   Recommendation: all-upload lessons only; block YouTube-only and mixed lessons
   rather than produce incomplete transcripts.
4. **Is editor review mandatory?**
   Recommendation: yes; generation creates a candidate and never edits learner
   content automatically.
5. **Should the first release support only `en`, `es`, `fr`, and `pt`?**
   Recommendation: yes; show a clear unsupported message for `ru` and `ar`.
6. **What candidate retention is wanted locally?**
   Recommendation: retain run metadata indefinitely, retain generated text for
   90 days after apply/failure, and define a later cleanup command.
7. **What Descript project retention is wanted?**
   The current API does not document deletion, so assign a Drive owner and a
   manual cleanup interval before launch.
8. **What monthly media-minute threshold should pause generation?**
   Descript returns 402 when allowance is exhausted, but the Academy should have
   an operational warning threshold rather than discover the limit mid-job.

Already decided/provided:

- A Descript API token exists.
- The token will be supplied through the environment, not committed.
- No implementation has been authorized yet.

## Acceptance checklist

- [ ] Owner decisions above are recorded.
- [ ] Feature is disabled by default and manual transcripts always work.
- [ ] Token stays server-only and redacted.
- [ ] Only authorized editors can start, inspect, apply, or retry a run.
- [ ] Source URLs come only from stored public-disk paths.
- [ ] YouTube, mixed-source, Russian, Arabic, and mixed-language lessons are
      blocked before a paid request.
- [ ] Ordered multi-video lessons generate one candidate transcript.
- [ ] One active run per lesson prevents duplicate spend.
- [ ] Generated text never overwrites the live transcript automatically.
- [ ] Existing transcript and changed-source safeguards work.
- [ ] 401, 402, 403, 429, timeout, 5xx, cancellation, malformed response, blank
      export, and expired job behavior are tested.
- [ ] No real API call occurs in automated tests.
- [ ] Search, translations, draft visibility, and creator scoping still pass.
- [ ] Staging smoke test uses synthetic media and verifies actual usage.
- [ ] Guides, changelog, deployment notes, and work log are updated at build time.
- [ ] Focused tests, full suite, Pint, diff check, and browser checks pass.

## Official references

- [Descript API overview](https://help.descript.com/api-and-mcp/api)
- [Descript API documentation](https://help.descript.com/developers)
- [OpenAPI specification](https://help.descript.com/developers/openapi.json)
- [Get job status](https://help.descript.com/developers/api-reference/jobs/get-job)
- [Call the API directly](https://help.descript.com/api-and-mcp/other-endpoints)
- [Supported transcription languages](https://help.descript.com/script-editing/supported-transcription-languages)

## Current progress

| Item | State |
| --- | --- |
| Existing lesson video, transcript, search, translation, and policy paths inspected | Done |
| Current Descript API, export, job, rate-limit, and language behavior checked | Done |
| Security, privacy, cost, failure, and rollback requirements documented | Done |
| Agent-sized implementation packages and verification matrix documented | Done |
| Descript API token | Available from owner; not added to repository |
| Product and data-processing decisions | Waiting for owner |
| Application implementation | Not started by instruction |
| Environment/configuration changes | Not started by instruction |
| Database changes | Not started by instruction |
| Live API smoke test | Not run; implementation not approved |
| Production enablement | Not approved or started |
