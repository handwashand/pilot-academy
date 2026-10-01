# Descript integration plan

> **Status: in progress — foundation built, API client and UI not started.**
>
> Branch: `feature/descript-integration`, cut from `laravel` at `49e15f25`.
> Read [Before starting](#before-starting--what-the-owner-must-do-first) and
> [Progress](#progress) before writing any code, then take the next unchecked
> step in [To do](#to-do--in-order). Every step lists its files and how to prove
> it works.
>
> Read `agents.md` first, as for any work in this repo: no commits or pushes,
> run the suite in Docker, and never report something as verified unless you
> ran it.

## Goal

In the owner's words: **"make sure anything translated is stored in the app,
not call API to translate over and over again."**

Descript translates the speech in a lesson video. Every Descript call spends
AI credits, and what it hands back is temporary — a published video's download
link expires. So:

- whatever Descript produces is stored in the app the moment it arrives;
- a translation that exists is never requested again;
- a video is sent to Descript **once**, and every language is made from that
  one copy.

## Decisions made

Taken by the product owner on 2026-10-01. Do not reopen without a new reason.

| Question | Decision |
| --- | --- |
| What Descript produces | **Both** — translated transcripts first, dubbed video after. Transcripts ship while dubbing is proven against a real account. |
| When it runs | **A button on the lesson.** An editor picks languages; nothing runs automatically on upload. |
| Storage | Every result is kept in the app; a done translation is never re-requested. |

Still open — see [Open questions](#open-questions).

## Findings — what Descript's API actually does

Checked against Descript's own documentation on 2026-10-01. **None of this has
been exercised against a live account yet** — that is step 0.6 below.

### Basics

- Base URL `https://descriptapi.com/v1`.
- Auth: `Authorization: Bearer <token>`. Tokens are created in Descript under
  **Settings**, are personal, scoped to **one Drive**, and inherit the user's
  permissions.
- Import, agent and publish calls are **asynchronous**: they return `201` with a
  `job_id` at once. Poll `GET /jobs/{job_id}` or pass a `callback_url` webhook.
  Job `job_state` is `running`, `stopped` or `cancelled`; when `stopped`, look at
  `result.status`.
- Errors are `{ "error": "...", "message": "..." }` with `400`, `401`, `402`
  (**out of media minutes or AI credits**), `403`, `404`, `429`.
- Rate limiting: `429` with `Retry-After` (seconds), plus
  `X-RateLimit-Remaining` and `X-RateLimit-Consumed`.

### Endpoints we use

| Method and path | Purpose here |
| --- | --- |
| `POST /jobs/import/project_media` | Send a lesson video once; creates the project. |
| `POST /jobs/agent` | Ask Descript's AI editor ("Underlord") to translate — a natural-language `prompt`. |
| `GET /jobs/{job_id}` | Poll any job. |
| `GET /projects/{project_id}` | List compositions, to find the one a translation created. |
| `POST /export/transcript` | Pull a composition's transcript out as text or subtitles. |
| `POST /jobs/publish` | Render a composition to a video file (dubbing, later). |

Also available, not needed yet: `GET /status`, `GET /jobs`,
`DELETE /jobs/{job_id}`, `GET /projects`, `GET /search`, `GET /agent/models`.

### Import — `POST /jobs/import/project_media`

Request: `project_name` (new project) or `project_id`; `add_media` is a map of
display name → item; optional `team_access`, `folder_name`, `workspace_name`,
`callback_url`. Each media item is **one** of:

- `{ "url": "https://…/video.mp4", "language": "en" }` — Descript downloads it
  from a public or presigned URL;
- `{ "content_type": "video/mp4", "file_size": 52428800, "language": "en" }` —
  direct upload.

`language` is ISO 639-1 for **transcription**; omitted, Descript detects it.

Response `201`: `job_id`, `drive_id`, `project_id`, `project_url`, and for direct
uploads `upload_urls` keyed by the display name:
`{ "<name>": { "upload_url", "asset_id", "artifact_id" } }`. **PUT the raw bytes
to `upload_url` with `Content-Type: application/octet-stream`; valid 3 hours.**

Completed result: `status` `success|partial|error`, `media_status` per media
name (`status`, `duration_seconds`, `error_message`), `media_seconds_used`,
`created_compositions` `[{ id, name }]`.

### Translate — `POST /jobs/agent`

There is **no translate endpoint**. Translation is a prompt to the AI editor.

Request: `project_id` (or `project_name`), optional `composition_id` (UUID,
5-character short id, or full URL), `model` (default `auto`), **`prompt`**
(required), `team_access`, `callback_url`.

Response `201`: `job_id`, `drive_id`, `project_id`, `project_url`,
`conversation_id`, `resolved_model`.

Completed result: `status` `success|error`, `agent_response` (free text
summary), `project_changed`, `media_seconds_used`, **`ai_credits_used`**,
`resolved_model`, `error_message`.

**The result does not say which composition it created.** See
[Finding the translated composition](#finding-the-translated-composition).

From Descript's help pages on translating in the app: translation creates **a
new composition per language** and leaves the original untouched; the
translated text becomes that composition's transcript; 90+ languages including
Arabic; it uses AI credits; re-translating **overwrites manual edits**; and a
translation does not update when the original changes.

### Project — `GET /projects/{project_id}`

`compositions`: `[{ id, name, duration_seconds, created_at, updated_at }]`.
`media`: `[{ id, name, duration_seconds, language }]`.

### Export — `POST /export/transcript`

Request: `project_id`, `composition_id` (defaults to the first), **`format`**:
`txt`, `markdown`, `html`, `rtf`, `docx` or `srt` — **no `vtt` through the
API**, although the app offers it. Optional `include_speaker_labels`
(`off|changes|every_paragraph`), `include_markers`, `timecodes`.

Response `200` is **the file itself**, inline — no download link — with an
`X-Composition-Id` header. Not a job; it returns straight away.

### Publish — `POST /jobs/publish` (dubbing, later)

Request: `project_id`, `composition_id`, `media_type` (`Video|Audio`),
`resolution` (`480p`…`4K`), `access_level` (`public|unlisted|drive|private`),
`callback_url`. Result: `share_url`, **`download_url` (signed, expires)** with
`download_url_expires_at`. **Download and store the file at once.**

### Dubbing

Descript's help describes dubbing in the app as: choose languages, tick **Dub
speech**, **assign each speaker a stock voice**, apply; it creates a new
composition per language, 30 languages, uses AI credits, optional lip sync.
**Whether the agent can do the voice assignment from a prompt is unknown.**
This is why dubbing waits for a live account.

## Constraints found in this codebase

1. **YouTube lessons cannot be sent.** Descript imports a media file; a YouTube
   watch link is not one, and downloading YouTube content is not an option. Only
   `type: upload` entries from `Lesson::videoEntries()` qualify. The button must
   not appear for a lesson with no uploaded video.
2. **No queue worker in production** (see `agents.md` and
   `docs/deepL-integration.md`). Jobs take minutes. Progress is advanced **on
   demand** — the editor's button, a "Check progress" action, and an artisan
   command that can be run by hand or by cron. Nothing may depend on a
   background worker. A `callback_url` webhook is an optional extra later.
3. **URL import needs a public address.** Uploaded videos live on the `public`
   disk and are served from `APP_URL/storage/…`. Production
   (`academy.pilot-gps.com`) can use URL import — Descript fetches the file and
   PHP never streams it. Localhost cannot; there, use direct upload (PUT the
   bytes from the server). Choose by `APP_URL`: a localhost / `.test` /
   `127.0.0.1` host means upload, anything else means URL.
4. **Videos can be 200 MB** (`admin_lessons.form.video_file_help`). Direct
   upload must stream the file (`fopen`), never read it into memory.
5. **A lesson has one `transcript` field but up to five videos.** The
   translated transcript is always kept on its `video_translations` row. It is
   written into the lesson's translated `transcript` **only when that
   language's transcript is empty** — see rule 4 below.
6. **Human work must never be overwritten.** `content_translations` may hold a
   transcript a person wrote or corrected. Descript output goes in only where
   that field is empty for that language.
7. **Every new UI string ships in all six languages.**
   `StudentSiteTranslationTest` fails on any missing key. Put this feature's
   strings in a **new** group, `lang/{code}/admin_descript.php`, and add
   `'admin_descript'` to `Translator::SHIPPED_GROUPS` — a new file cannot
   conflict with pending Arabic work on `feature/arabic-language`.
8. **The suite runs on SQLite; production is PostgreSQL** (since 2026-10-01).
   See the `distinct lessons.*` trap in `agents.md`. Check queries against the
   Postgres container, not only the suite.

## Design

### Storage — built

Migration `2026_10_01_000001_create_descript_tables.php`:

- **`descript_imports`** — one row per uploaded lesson video: `lesson_id`,
  `video_path`, `file_size`, `source_language`, `status`
  (`pending|importing|ready|failed`), `project_id`, `job_id`,
  `media_seconds_used`, `error`. Unique on `(lesson_id, video_path)`. A replaced
  video is stored under a new path, so it is a new row.
- **`video_translations`** — one row per video × language × kind:
  `descript_import_id`, `lesson_id`, `language`, `kind` (`transcript|dub`),
  `status` (`pending|translating|exporting|done|failed`), `job_id`,
  `composition_id`, `agent_response`, `transcript`, `subtitle_path`, `dub_path`,
  `ai_credits_used`, `error`, `requested_by`, `completed_at`. **Unique on
  `(descript_import_id, language, kind)`** — the database itself enforces "one
  of each, ever".

Models: `App\Models\DescriptImport`, `App\Models\VideoTranslation` (constants
for every status and kind, `IN_FLIGHT`, `isDone()`, `isInFlight()`,
`scopeInFlight()`).

### The rules that deliver the goal

1. **Import once.** Before importing, look up `descript_imports` by
   `(lesson_id, video_path)`. If a row exists, use its `project_id`.
2. **Translate once.** Requesting a language looks up `video_translations` by
   `(import, language, kind)`. `done` → reuse, no call. In flight → leave it,
   no call. Only `failed` may be retried, and only when an editor asks.
3. **Store on arrival.** The moment an export returns, save the text to the row
   and the `.srt` to the `public` disk. The moment a publish returns, download
   the file. Never keep only a Descript URL.
4. **Never overwrite people.** Write into the lesson's translated `transcript`
   only when that field is empty for that language.
5. **One agent job per project at a time.** Start the next language only when
   no other translation of the same import is `translating`. This keeps
   composition detection unambiguous.

### State machines — to build

Advanced by one `advance()` call at a time; each call is idempotent and safe to
repeat.

**Import:** `pending` → create the import (URL or upload; for upload also PUT
the bytes) → `importing` → poll; when `stopped` and `result.status` is
`success` (or `partial` with this media `success`) → `ready`; otherwise
`failed` with the message.

**Translation:** `pending` → wait for the import to be `ready` and for no
sibling to be `translating`; read the project's composition ids; start the
agent → `translating` → poll; on `success`, find the new composition →
`exporting` → export `txt` and `srt`, store both, fill the lesson transcript per
rule 4 → `done`. Any error → `failed` with Descript's message.

### Finding the translated composition

The agent result carries no composition id. Do both, in order:

1. **Name it.** The prompt tells the agent to name the new composition
   predictably, e.g. `Pilot Academy — fr`. Match on that name.
2. **Diff it.** Before starting the agent, record the project's composition
   ids; after, the new id is the one that was not there. Rule 5 guarantees only
   one translation ran in between.

If neither gives exactly one composition, mark the row `failed` with a clear
message rather than guess.

### The prompt

Configurable, because its wording is the least certain part of the plan.
Placeholders: `{language}` (the target's English name from `languages.name`,
e.g. `Portuguese (Brazil)`), `{name}` (the composition name to create). Starting
point, to be proven in step 0.6:

> Translate the captions of this composition into {language}. Create the
> translation as a new composition named "{name}". Do not change the original
> composition.

### Configuration

**The variable names are the owner's** — set in `.env` on 2026-10-01. Use these,
not any other spelling:

```dotenv
DESCRIPT_ENABLED=false
DESCRIPT_API_TOKEN=
DESCRIPT_API_BASE_URL=https://descriptapi.com/v1
DESCRIPT_PROJECT_FOLDER="Pilot Academy/Transcriptions"
DESCRIPT_TIMEOUT_SECONDS=30
```

`DESCRIPT_PROJECT_FOLDER` is the import's `folder_name` — every project the app
creates goes in that folder (nested with `/`). `DESCRIPT_TIMEOUT_SECONDS` is the
HTTP timeout per request.

`config/services.php`:

```php
'descript' => [
    'enabled' => env('DESCRIPT_ENABLED', false),
    'token' => env('DESCRIPT_API_TOKEN'),
    'base_url' => env('DESCRIPT_API_BASE_URL', 'https://descriptapi.com/v1'),
    'project_folder' => env('DESCRIPT_PROJECT_FOLDER', 'Pilot Academy/Transcriptions'),
    'timeout' => (int) env('DESCRIPT_TIMEOUT_SECONDS', 30),
    'translate_prompt' => env('DESCRIPT_TRANSLATE_PROMPT', '…the prompt above…'),
],
```

The token never reaches the browser, a log line, or the repository.

### Where the button lives

`app/Filament/Resources/Lessons/Pages/EditLesson.php` → `getHeaderActions()`,
beside the existing `TranslateContentAction` (read it — it is the pattern to
follow). Visible only when Descript is enabled and configured **and** the lesson
has at least one uploaded video. The modal: choose the video (if more than one
upload), tick target languages (every active language except the lesson's own),
with done and in-flight languages shown as such and not tickable. A second
action, **Check progress**, appears while anything is in flight.

## Before starting — what the owner must do first

Nothing below can be done by an agent. Steps 0.1–0.5 unblock step 0.6, and step
0.6 is the only way to settle the biggest unknown in this plan.

- [x] **0.1 Plan.** A Descript plan that includes **API access** and **AI
  credits** (translation spends credits; dubbing spends more). *Done by the
  owner, 2026-10-01.*
- [x] **0.2 Drive.** *Done by the owner, 2026-10-01.* Note: the token is scoped
  to **"S's Drive"**, which already holds other projects — academy projects are
  kept apart by `DESCRIPT_PROJECT_FOLDER` (`Pilot Academy/Transcriptions`)
  rather than by a separate drive. That works; keep the folder setting.
- [x] **0.3 Token.** In Descript, **Settings → API**, create a token for that
  Drive. Put it in the server's `.env` as `DESCRIPT_API_TOKEN` — never in the
  repository, a chat, or a ticket. *Done by the owner, 2026-10-01; verified
  working — see [Proven on a live account](#proven-on-a-live-account).*
- [x] **0.4 Spend.** Note the plan's monthly credit allowance and decide a
  ceiling for the first month. Descript answers `402` when credits run out; the
  app will report that, not retry it. *Done by the owner, 2026-10-01.*
- [ ] **0.5 Data.** Confirm that sending lesson videos to Descript is acceptable
  under the organisation's data and confidentiality rules. Use a synthetic test
  video for 0.6, never one with customer data.
- [ ] **0.6 Prove the prompt.** With the token, by hand, on one short test
  video — this decides whether the plan works as written:

  ```bash
  TOKEN=…   # from 0.3; do not paste into a file
  # 1. Import a video from a public URL
  curl -s -X POST https://descriptapi.com/v1/jobs/import/project_media \
    -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d '{"project_name":"Descript API test","add_media":{"Test video":{"url":"https://…/test.mp4","language":"en"}}}'
  # 2. Poll until job_state is "stopped"
  curl -s https://descriptapi.com/v1/jobs/<job_id> -H "Authorization: Bearer $TOKEN"
  # 3. Ask for a French translation
  curl -s -X POST https://descriptapi.com/v1/jobs/agent \
    -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d '{"project_id":"<project_id>","prompt":"Translate the captions of this composition into French. Create the translation as a new composition named \"Pilot Academy — fr\". Do not change the original composition."}'
  # 4. Poll that job, then list the compositions
  curl -s https://descriptapi.com/v1/projects/<project_id> -H "Authorization: Bearer $TOKEN"
  # 5. Export the French transcript
  curl -s -X POST https://descriptapi.com/v1/export/transcript \
    -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
    -d '{"project_id":"<project_id>","composition_id":"<new id>","format":"srt"}'
  ```

  Record in [Proven on a live account](#proven-on-a-live-account): did a new
  composition appear, under the requested name, with French text; how many
  `ai_credits_used`; how long each job took. **Then try a dubbing prompt the
  same way** and record whether the agent can dub without manual voice
  assignment.

## Progress

| Item | State |
| --- | --- |
| Descript API researched and documented | Done |
| Owner decisions (output, trigger, storage) | Done |
| Branch `feature/descript-integration` off `laravel` | Done |
| Migration + `DescriptImport` / `VideoTranslation` models | **Done** — written; `php -l` clean; migration runs in the suite (one test, via `RefreshDatabase`); no tests of their own yet |
| Before starting 0.1–0.4 | **Done** by the owner; token verified with free calls |
| Before starting 0.5 (data sign-off), 0.6 (prove the prompt) | Waiting — 0.6 spends credits and needs a synthetic test video with speech |
| Steps 1–9 below | Not started |
| Dubbing | Not started — waits for 0.6 |

## To do — in order

Each step can be handed to an agent on its own once the ones before it are done.
Steps 1–7 can be built and fully tested **without a token**, with
`Http::fake()`; only 0.6 and step 9 need the live account.

### 1. Configuration

- `config/services.php` — the `descript` block above.
- `.env.example` — the three variables, `DESCRIPT_ENABLED=false`.
- A small `enabled()` check (in the client, step 2): enabled flag **and** a
  token present.

**Done when:** with no token, `enabled()` is false and nothing in steps 2–6
makes any HTTP call.

### 2. The HTTP client — `App\Services\Descript\DescriptClient`

Laravel's `Http` facade, no package (none is used in the app yet; this is the
first). One method per endpoint above: `createImport()`, `upload()`, `job()`,
`agent()`, `project()`, `exportTranscript()`, later `publish()` and `download()`.

- Bearer header from config; base URL from config.
- `upload()` streams: `Http::withBody(fopen($path, 'r'), 'application/octet-stream')->put($url)`.
- Errors become `App\Services\Descript\DescriptException` carrying the HTTP
  status and Descript's `error` / `message`, so the UI can say "out of credits"
  (`402`) differently from "token wrong" (`401`).
- Retry **only** `429` and `5xx`, at most twice, honouring `Retry-After` capped
  at 10 seconds — these run inside a web request.
- Log status, endpoint, job/project id. **Never** log the token or any
  transcript text.

**Done when:** unit tests with `Http::fake()` cover the header, each request
body, the upload being a stream, `402` / `401` / `429` handling, and that the
token appears in no log line.

### 3. The orchestrator — `App\Actions\TranslateLessonVideo`

`request(Lesson, string $videoPath, array $languages, User)` creates rows by
rules 1–2 and returns which languages were newly requested, which were already
done, and which were already running. `advance(VideoTranslation)` and
`advanceImport(DescriptImport)` implement the state machines;
`advanceLesson(Lesson)` advances everything in flight for one lesson.

**Done when:** feature tests prove — and these are the ones that matter most:

- requesting the same language twice makes **no** second agent call;
- five languages for one video make **one** import;
- a `done` row is never sent again; a `failed` row is retried only on request;
- the export's text lands on the row and the `.srt` on the `public` disk;
- an existing human transcript translation is **not** overwritten;
- a `402` marks the row `failed` with a readable message and stores nothing;
- with Descript disabled, nothing is called.

### 4. Artisan command — `descript:sync`

Advances every in-flight import and translation. Safe to run repeatedly; safe
to put on cron later. Prints a one-line summary.

**Done when:** a test runs it twice over faked jobs and the second run makes no
calls for rows already `done`.

### 5. The lesson button

`App\Filament\Actions\TranslateVideoWithDescriptAction`, added to
`EditLesson::getHeaderActions()`, plus **Check progress**. See
[Where the button lives](#where-the-button-lives). Authorisation is the lesson's
own edit permission — creators stay scoped to their products.

**Done when:** Livewire tests prove it is hidden without an uploaded video and
when Descript is disabled; that done languages cannot be ticked; that a creator
cannot reach another product's lesson; and that submitting twice does not create
duplicate rows.

### 6. Strings, in six languages

`lang/{en,ru,es,fr,pt,ar}/admin_descript.php`, and `'admin_descript'` in
`Translator::SHIPPED_GROUPS`. Arabic plural lines take six forms.

**Done when:** `StudentSiteTranslationTest` passes.

### 7. Show the result

Where a partner sees it: the lesson already shows its `transcript` translated by
`HasContentTranslations`, so rule 4 makes Descript's text appear with no view
change. Decide whether to also offer the `.srt` as captions on the `<video>`
player (`<track kind="captions">`) — a small view change in the lesson page,
and a real improvement for partners.

**Done when:** a lesson viewed in French shows the French transcript; if
captions are added, the `<track>` points at the stored file and the player
offers it.

### 8. Documentation

`docs/CHANGELOG.md` (plain language, under the current version), the admin
guide in all six languages, `DEPLOY.md` (the variables, enabling, running
`descript:sync`), `README.md` if setup changed, a work-log entry in `agents.md`,
and this file's [Progress](#progress).

### 9. Live verification — needs 0.1–0.6

On staging with a real token: translate one synthetic lesson video into every
active language, check each transcript and `.srt`, confirm a second request
spends nothing, record credits used. Then decide whether dubbing (below) goes
ahead.

### Later — dubbing

Only after 0.6 shows the agent can dub. Same tables (`kind = dub`), same rules.
After the agent job, `POST /jobs/publish` the dubbed composition, then
**download `download_url` at once** into the `public` disk and store the path in
`dub_path` — the link expires. The lesson page then needs a way to choose the
dubbed video by language: a change to how `video_sources` is played, to be
designed then.

## Open questions

1. Offer the stored `.srt` as player captions (step 7)? Recommended: yes.
2. Which Descript plan, and what monthly credit ceiling (0.1, 0.4)?
3. May **creators** spend credits, or admins only? The plan assumes the lesson's
   edit permission; narrow it if spend must be admin-only.
4. Add the `callback_url` webhook later so progress arrives without anyone
   clicking? Needs a signed public route; production only.

## Proven on a live account

**2026-10-01 — the token works (no credits spent).** Read-only calls with the
owner's token from `.env`:

- `GET /status` → `200`: `{ "drive_id", "drive_name": "S's Drive", "api_version": "v1" }`.
- `GET /projects` → `200`: `{ "data": [ { "id", "name", "created_at", "updated_at" }, … ] }`
  — note the list is wrapped in **`data`**, which the spec summary did not show.

**Still unproven** — step 0.6: whether the translate prompt creates a correctly
named composition, its credits and time per job, and the answer on dubbing.

## Risks

- **The prompt is the weak point.** Natural-language instructions can be
  followed loosely. Composition detection (name, then diff) and failing loudly
  rather than guessing are there for that reason.
- **Credits.** Every agent call costs. Rules 1–2 and the database's unique key
  are what stop double spending; do not weaken them.
- **Re-translation overwrites edits in Descript.** Irrelevant to the app,
  because the app never re-translates a done row and keeps its own copy.
- **The original changes, the translation does not.** A replaced video is a new
  path and so a new import; an edited-in-place one is not detected. Acceptable
  for now; uploads are replaced, not edited.

## References

- [Descript API overview](https://help.descript.com/developers/api-reference/overview)
- [OpenAPI specification](https://help.descript.com/developers/openapi.yaml)
- [Rate limiting](https://help.descript.com/developers/guides/rate-limiting)
- [Translate captions (app)](https://help.descript.com/repurpose/translate-captions.md)
- [Dub speech (app)](https://help.descript.com/repurpose/dubbing.md)
- [Do-not-translate list](https://help.descript.com/repurpose/do-not-translate.md) — worth setting up with "Pilot", product names and menu terms before translating.
