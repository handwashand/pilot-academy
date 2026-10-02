# DeepL integration plan

> **Status: Step 1 complete.** The provider client and disabled-by-default
> configuration are built and tested. No editor control is connected yet, and
> no real DeepL request has been made.
>
> Branch: `feature/descript-deepl-integration`. DeepL translates Academy text;
> Descript remains responsible only for lesson-video transcripts and later
> dubbing.

## Goal

Let an editor ask DeepL to draft translations for Academy content, review the
draft in the existing **Translate** dialog, edit it, and save it through the
existing `content_translations` storage.

The integration must never:

- translate automatically when content is saved;
- replace an existing human translation by default;
- save generated words before an editor reviews and submits them;
- send slugs, URLs, credentials, internal notes, or customer data;
- expose the API key to the browser or logs; or
- remove or weaken manual translation when DeepL is unavailable.

## Decisions

These defaults were chosen when implementation was reopened on 2026-10-02.

| Question | Decision |
| --- | --- |
| Trigger | Explicit **Generate missing with DeepL** inside the existing Translate dialog. |
| Review | Always review before saving. Generation changes form state only. |
| Overwrite | Missing values only. No regeneration in the first release. |
| English | American English (`en-US`). Configurable to `en-GB`. |
| First proof | Courses and lessons. |
| Later content | Reuse the same action for case studies, tutorials, and webinars after the first proof. |
| Quizzes | Out of scope: questions/options do not use `HasContentTranslations`. |
| Storage | Existing `content_translations`; no migration. |
| Background work | None. The editor-triggered request is synchronous. |
| Access | Use an assignable paid-service permission; record edit policies still apply. |

## Editor workflow

1. Save the source course or lesson normally.
2. Open its existing **Translate** action.
3. Choose target languages and fields that are still empty.
4. Click **Generate missing with DeepL** and confirm the paid request.
5. Review and edit the returned drafts in the existing language tabs.
6. Click the existing **Save translations** button.
7. Learners see the saved values through the existing locale/fallback system.

If DeepL fails, the dialog stays open and every value already in it remains.
Manual translation works whether DeepL is enabled, disabled, out of quota, or
temporarily unavailable.

## Current architecture

- `HasContentTranslations` defines the allowed fields and owns persistence.
- `TranslateContentAction` renders one tab per active target language.
- `Course`, `Lesson`, `CaseStudy`, `Tutorial`, and `Webinar` already use that
  same contract.
- Interface strings in `lang/{code}` and the correction matrix under
  **Settings → Translations** are separate and will not be sent to DeepL.
- Arabic direction remains a page-layout concern; DeepL only supplies text.

## Provider contract

Checked against DeepL's official documentation on 2026-10-02.

- Text translation: `POST /v2/translate`.
- Current capability discovery: `GET /v3/languages?resource=translate_text`.
- Authentication: `Authorization: DeepL-Auth-Key <key>`.
- Free host: `https://api-free.deepl.com`; Pro host:
  `https://api.deepl.com`.
- Request bodies must stay below 128 KiB. The client batches at 120 KiB.
- HTML uses `tag_handling: html` and `tag_handling_version: v2`.
- Retry `429`, `529`, `500`, `503`, and `504` with bounded backoff. Do not
  retry `456` quota exhaustion.
- Log status, error code and `X-Trace-ID`; never log source text, translated
  text, or the key.

Academy locale mapping:

| Academy | DeepL source | DeepL target |
| --- | --- | --- |
| `en` | `en` | `en-US` (or configured `en-GB`) |
| `ru` | `ru` | `ru` |
| `es` | `es` | `es` |
| `fr` | `fr` | `fr` |
| `pt` | `pt` | `pt-BR` |
| `ar` | `ar` | `ar` |

The client still checks `usable_as_source`, `usable_as_target`, and HTML tag
support from the live language response, cached for one hour.

## Configuration

```dotenv
DEEPL_ENABLED=false
DEEPL_API_KEY=
DEEPL_API_BASE_URL=https://api-free.deepl.com
DEEPL_ENGLISH_TARGET=en-US
DEEPL_TIMEOUT_SECONDS=30
DEEPL_REPORTING_TAG=pilot-academy
```

Only the two official Free and Pro hosts are accepted. The key belongs in the
real server `.env`, never `.env.example`, source control, a ticket, or a log.

## Implementation steps

| Step | State | Verification |
| --- | --- | --- |
| 1. Configuration and `DeepLClient` | **Done** | 11 mocked tests, 31 assertions; Pint clean |
| 2. Generate-missing control in Translate | In progress | Filament tests: form state only, no overwrite, failure preserves state |
| 3. Assignable access right | Not started | Denied without right; allowed with right; record policy still applies |
| 4. Course and lesson end-to-end proof | Not started | Plain fields, lesson HTML, fallback, student rendering, Arabic RTL |
| 5. Extend to other translated models | Not started | Case study, tutorial, webinar tests without duplicated logic |
| 6. Guides, deploy notes, changelog | Not started | Rendered docs and translation-key checks |
| 7. Live staging verification | Waiting for owner key/cost limit/data approval | Synthetic content in all six languages; usage reviewed |

## Step 1 files

- `config/services.php` and `.env.example`: disabled configuration.
- `app/Services/DeepL/DeepLClient.php`: official hosts, language discovery,
  mappings, translation, HTML mode, batching, retry and safe logging.
- `app/Services/DeepL/DeepLException.php`: structured failure details.
- `tests/Unit/DeepLClientTest.php`: no real network calls.

No dependency or database migration was added.

## Before live enablement

- Create a dedicated DeepL developer key outside the repository.
- Choose API Free or Pro and set a low monthly/key character limit.
- Confirm course and lesson text may be sent under the organisation's data
  handling rules. Use synthetic content for the first test.
- Deploy with `DEEPL_ENABLED=false`, configure the key, clear cached config,
  then enable only after the editor flow is verified.

Rollback is `DEEPL_ENABLED=false` followed by `php8.4 artisan optimize`.
Existing saved translations and manual editing continue to work.

## Official references

- [Translate text](https://developers.deepl.com/api-reference/translate/request-translation)
- [Using the v3 Languages API](https://developers.deepl.com/docs/languages/using-the-languages-api)
- [Supported languages](https://developers.deepl.com/docs/getting-started/supported-languages)
- [Error handling](https://developers.deepl.com/docs/best-practices/error-handling)
