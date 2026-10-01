# DeepL integration plan

> Status: **Planning only - do not implement yet.**
>
> This document records the proposed design. Work starts only after the product
> owner chooses the options in [Decisions required](#decisions-required) and
> explicitly approves implementation.

## Recommendation at a glance

- Add DeepL inside the existing **Translate** modal.
- Let an editor request missing translations, review them, and then save.
- Start with course and lesson fields; treat quizzes as a separate decision.
- Reuse `content_translations`; the minimal release needs no migration.
- Keep manual translation working when DeepL is disabled or unavailable.
- Do not require a queue worker for the first release.

## Goal

Help editors translate administrator-authored Academy content with DeepL while
preserving the translation system, permissions, publishing workflow, language
selector, fallbacks, and Arabic right-to-left layout that already work.

The integration must:

- translate dynamic learning content, not static interface text;
- use the existing `content_translations` records;
- protect translations that a person has written or corrected;
- show editors what DeepL produced before it reaches learners;
- fail without damaging the source record or existing translations;
- keep the API key and all DeepL requests on the server;
- avoid paid calls when a translation already exists; and
- remain disabled until it is configured and deliberately enabled.

## What exists today

The application already supplies most of the required architecture.

### Languages

The six configured Academy languages are:

| Academy code | Language | Direction | Proposed DeepL code |
| --- | --- | --- | --- |
| `en` | English | LTR | Source: `en`; target variant must be chosen |
| `ru` | Russian | LTR | `ru` |
| `es` | Spanish | LTR | `es` |
| `fr` | French | LTR | `fr` |
| `pt` | Portuguese (Brazil) | LTR | `pt-BR` |
| `ar` | Arabic | RTL | `ar` |

DeepL currently lists all six language families for text translation. The
implementation must still query and cache
`GET /v3/languages?resource=translate_text` and check `usable_as_source` and
`usable_as_target`; support can change, and a missing target must be reported
to the editor rather than treated as success.

### Dynamic content storage

`HasContentTranslations` owns the existing translation contract:

- each record has a source language in its `language` column;
- translated values are polymorphic rows in `content_translations`;
- `setTranslation()` creates, updates, or deletes one translated field;
- `translated()` returns the translated value when available and otherwise
  falls back to the source value; and
- `translationCoverage()` reports completion by active language.

No new table is needed to store DeepL output.

The existing `TranslateContentAction` opens one tab per active target language
on the edit pages. Editors can already enter and correct translations there.
DeepL should extend this action, not create a second translation screen.

### Content currently translatable

| Record | Fields |
| --- | --- |
| Course | `title`, `description` |
| Lesson | `title`, `summary`, `content`, `transcript` |
| Case study | title, short problem, and all partner-facing study sections |
| Tutorial | `title`, `summary` |
| Webinar | `title`, `summary`, `description` |

Phase 1 should cover courses and lessons only unless the owner expands the
scope. Case studies, tutorials, and webinars can reuse the same service later.

Quiz questions (`questions.prompt`) and answer options (`options.text`) do not
use `HasContentTranslations`. They cannot be included honestly without a
separate model and UI change. That is an explicit scope decision below.

### Static interface translations

Navigation, buttons, labels, validation messages, emails, and other interface
text are shipped in `lang/{code}/*.php` and may be corrected through the
`translations` table. DeepL must not read or write either source.

### Locale and RTL behavior

`SetLocale` selects the user's language from their account, session, browser,
or the default. `Translator::direction()` supplies the page direction, and the
Academy layout renders it through the root `dir` attribute. DeepL has no role
in language selection or layout.

### Queues

Laravel's database queue is configured and local `composer dev` starts a queue
listener, but production deployment has no documented long-running queue
worker. Phase 1 must not depend on a background job. Adding automatic
after-save translation would first require a production worker, monitoring,
retries, and deployment documentation.

## Recommended editor workflow

The safest first release is review-first and editor-triggered:

1. The editor writes and saves the source course or lesson normally.
2. On its edit page, the editor opens the existing **Translate** action.
3. The editor selects target languages and clicks **Generate missing with
   DeepL**.
4. The server sends only empty selected fields to DeepL.
5. Generated text is returned to the existing language tabs but is not saved
   yet.
6. The editor reviews and adjusts the generated text.
7. The existing **Save translations** action stores the reviewed values through
   `setTranslation()`.
8. Learners see them through the existing locale and fallback behavior.

This is preferable to translating after every record save because it avoids
surprise costs, hidden content changes, repeated requests while an editor is
still writing, and accidental replacement of reviewed translations.

### Regeneration

The default operation fills missing values only. A separate **Regenerate
selected fields** command may be added if approved. It must:

- require the editor to select languages and fields;
- show that existing text will be replaced;
- require confirmation;
- put the result back in the form for review before saving; and
- leave every unselected value untouched.

Changing source content must not automatically overwrite translations. Without
translation metadata, the application cannot distinguish an old machine
translation from a human-edited one. Stale-translation indicators therefore
belong to the optional metadata phase, not the minimal first release.

## Proposed technical design

### API client

Use Laravel's existing HTTP client rather than adding a package. Add a small
server-side client, for example `App\Services\DeepLClient`, responsible only
for:

- authentication and the configured Free or Pro base URL;
- retrieving and caching text-translation language support for up to one hour;
- mapping Academy language codes to supported DeepL BCP 47 codes;
- batching fields without exceeding DeepL's 128 KiB request limit;
- translating plain text and HTML safely;
- parsing successful and error responses; and
- returning structured results to the Filament action.

Use `POST /v2/translate` with the `Authorization: DeepL-Auth-Key <key>` header.
The key must never be sent to the browser or written to logs.

Suggested configuration:

```dotenv
DEEPL_ENABLED=false
DEEPL_API_KEY=
DEEPL_API_URL=https://api-free.deepl.com
```

The accepted base URLs should be restricted to DeepL's official Free and Pro
hosts. Production may use `https://api.deepl.com` after the subscription is
chosen.

### Request behavior

- Send the record's actual `contentLanguageCode()` as the source. The existing
  app supports authoring in any active language and should keep doing so.
- Translate only the requested `translatableFields()`.
- Map Academy `pt` to DeepL `pt-BR`.
- Choose `en-US` or `en-GB` before translating into English.
- Batch multiple fields per target language where practical.
- Use `tag_handling: html` and `tag_handling_version: v2` for rich HTML fields
  such as lesson `content` so markup survives.
- Keep plain fields as plain text. Do not strip and rebuild rich content.
- Context may later improve short titles, but it is not required for phase 1.
- Do not send slugs, URLs, credentials, media, source notes, customer data, or
  any field outside the model's explicit translation list.

### Failure handling

The editor must receive a useful result per target language. A partial failure
must not erase a successful target or save blanks.

- Retry `429`, `529`, `500`, `503`, and `504` with limited exponential backoff
  and jitter, honoring `Retry-After` when present.
- Do not retry `400`, `403`, `404`, `413`, or `456` quota exhaustion.
- Log the HTTP status, machine-readable error code, target language, record type
  and ID, and DeepL `X-Trace-ID`.
- Do not log the API key, source content, or translated content.
- Report unsupported languages, invalid configuration, quota exhaustion, and
  temporary service failures differently.

### Permissions

The generation controls live inside the existing Translate action and inherit
the record's existing edit authorization and creator product scoping. Decide
whether creators may spend DeepL quota or whether generation is admin-only.
Manual translation remains available even when DeepL is disabled.

### Database impact

**Minimal phase:** no migration. DeepL fills only missing form values, and the
existing save path stores them in `content_translations`.

**Optional provenance phase:** add nullable metadata to existing content
translations if the owner wants labels such as "Generated by DeepL" or
"Source changed since translation". The smallest useful metadata would record
the provider, a hash of the source value used, and generation time. Existing
rows must be treated as human-owned and must never be overwritten by default.

## Decisions required

Implementation is blocked until the owner answers these questions.

1. **Trigger:** Use the recommended explicit **Generate missing with DeepL**
   control, or translate automatically after every save?
2. **Scope:** Start with course and lesson fields only, or also redesign quiz
   questions and answer options for translations?
3. **Other content:** Include case studies, tutorials, and webinars in phase 1,
   or add them after courses and lessons are proven?
4. **Review:** Must generated text always be reviewed in the Translate modal
   before it is saved? Recommended: yes.
5. **Regeneration:** Allow deliberate replacement of selected translations in
   phase 1, or ship missing-only generation first?
6. **Provenance:** Is a visible machine-generated/stale status required? If
   yes, approve the small metadata migration before coding.
7. **English variant:** Use `en-GB` or `en-US` when the source is not English?
8. **Access:** May creators generate translations for their assigned products,
   or is paid API use admin-only?
9. **Subscription:** DeepL API Free or Pro, and what monthly cost-control limit
   should be set?
10. **Data approval:** Is sending course and lesson text to DeepL acceptable
    under the organization's data-processing and confidentiality requirements?

## Implementation plan

No phase below starts until the owner gives explicit approval.

### Phase 0 - Confirm behavior and provider setup

**Owner:** product owner with engineering support

- Resolve all decisions above.
- Create a restricted DeepL developer key outside the repository.
- Set a low account or key-level cost-control limit for staging.
- Confirm the chosen endpoint and data-processing terms.
- Query the live language endpoint and verify all six mappings.

**Verification:** Record the approved choices in this document and make one
manual staging API request without placing the key or content in source control.

### Phase 1 - API client and configuration

**Can be assigned independently after Phase 0.**

- Add environment and `config/services.php` entries with DeepL disabled by
  default.
- Build the isolated HTTP client using Laravel's HTTP facade.
- Add language discovery/cache, locale mapping, payload splitting, HTML mode,
  structured errors, safe logs, and bounded retries.
- Use mocked HTTP responses in every automated test.

**Verification:** Unit tests for headers, hosts, mappings, batching, HTML,
timeouts, unsupported targets, retries, quota exhaustion, and secret-safe logs.

### Phase 2 - Existing Translate action integration

**Depends on Phase 1.**

- Add target-language and field selection to `TranslateContentAction`.
- Add **Generate missing with DeepL** without replacing manual entry.
- Populate the existing form state with generated values; do not persist until
  the editor submits the existing save action.
- Preserve all filled values by default.
- Add regeneration only if it was approved in Phase 0.
- Keep the control hidden or disabled with a clear reason when DeepL is not
  configured.

**Verification:** Filament tests prove missing fields are filled, existing
translations survive, selected regeneration is deliberate, unauthorized users
cannot generate, failures preserve form data, and double submission does not
create duplicate calls.

### Phase 3 - End-to-end content behavior

**Depends on Phase 2.**

- Cover courses and lessons, including rich lesson HTML.
- Confirm translated content appears through the existing student language
  selector.
- Confirm missing translations still fall back to the source text.
- Confirm Arabic keeps the existing RTL layout.
- If approved, extend the same action to other models without duplicating the
  API or overwrite rules.

**Verification:** Feature tests plus manual desktop and mobile checks in all six
languages. Use synthetic course content, never real customer information.

### Phase 4 - Operations and documentation

**Can be prepared alongside Phase 3.**

- Update `.env.example`, `README.md`, and `DEPLOY.md` with setup, enablement,
  cost control, key rotation, and troubleshooting.
- Update the English and five localized admin guides with the exact editor
  workflow.
- Add a plain-language entry to `docs/CHANGELOG.md`.
- Record implementation results and any limitations in `agents.md`.
- If a queue worker is approved later, document and monitor it before enabling
  any automatic jobs.

**Verification:** Documentation links and commands are checked against a clean
environment; no key appears in Git history, rendered pages, logs, or browser
network responses.

### Phase 5 - Release safely

- Deploy with `DEEPL_ENABLED=false`.
- Configure the key and endpoint on staging.
- Generate and review one small course in every target language.
- Check usage and error logs, then enable production for a limited editor group.
- Watch quota, latency, failures, and translation quality before expanding
  access or content types.

**Rollback:** Set `DEEPL_ENABLED=false`. Manual translation and all previously
saved content continue to work because the existing storage and read path are
unchanged.

## Acceptance checklist

- [ ] No request is made when DeepL is disabled or unconfigured.
- [ ] The API key is server-only and absent from logs and browser responses.
- [ ] Live DeepL language support is checked and cached.
- [ ] All approved Academy locale mappings are verified.
- [ ] Only explicit dynamic content fields are sent.
- [ ] Existing translations are not overwritten by default.
- [ ] Generated values are reviewed before persistence.
- [ ] HTML remains valid and safe after translation.
- [ ] Partial API failures do not save blanks or damage other languages.
- [ ] Source-language fallback still works.
- [ ] Arabic remains RTL and usable on mobile.
- [ ] Authorization and creator product scoping remain intact.
- [ ] Automated tests make no real DeepL calls.
- [ ] Focused tests, the full suite, Pint, and browser checks pass.
- [ ] Guides, changelog, deployment notes, and work log are updated.

## Official references

- [DeepL supported languages](https://developers.deepl.com/docs/getting-started/supported-languages)
- [Using the v3 Languages API](https://developers.deepl.com/docs/languages/using-the-languages-api)
- [Translate text API](https://developers.deepl.com/api-reference/translate/request-translation)
- [DeepL error handling](https://developers.deepl.com/docs/best-practices/error-handling)
- [DeepL pre-production checklist](https://developers.deepl.com/docs/best-practices/pre-production-checklist)

## Current progress

| Item | State |
| --- | --- |
| Existing translation architecture inspected | Done |
| Six Academy locales and RTL path identified | Done |
| DeepL API behavior checked against official documentation | Done |
| Recommended workflow and phased plan documented | Done |
| Product decisions | Waiting for owner |
| DeepL account, API key, and cost limit | Waiting for owner |
| Application implementation | Not started |
| Database changes | Not approved or started |
| Production enablement | Not approved or started |
