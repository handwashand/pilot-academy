# Case Studies Implementation Plan

Last updated: 2026-09-29

## Objective

Add a Case Studies area to Pilot Academy so partners and integrators can find proven deployment patterns, follow reproducible implementation steps, and allow authorized editors to draft, preview, and publish studies safely.

## Status Summary

| Phase | Status | Notes |
| --- | --- | --- |
| 1. Inspect existing application | Done | Reviewed Academy navigation, routes, authentication, roles, content models, publishing, Filament resources, media, schema, seeders, and tests. |
| 2. Public Case Studies experience | Done | Added Academy navigation, listing, search, filters, study details, related content, media support, empty states, and responsive layouts. |
| 3. Editorial workflow | Done | Added role-aware Filament create, edit, preview, publish, unpublish, archive, validation, and product ownership controls. |
| 4. Starter content | Done | Added three anonymized draft studies with illustrative outcomes and no invented customer claims. |
| 5. Automated verification | Done | The whole suite passes in Docker (397 tests); frontend production build passes. |
| 6. Documentation and handoff | Done | Updated the changelog, admin guides, learner Help guides, work log, and this implementation plan. |
| 7. Product acceptance | In progress | The listing and a study were checked in Chrome at 375px and 1280px in English and Russian. Editorial acceptance of the three drafts remains with the product owner. |
| 8. Every language | Done | The panel screens and both partner pages go through `__t()`, in all five languages. Added after review: they were first written as fixed English text. |

## Reused Application Patterns

- Academy Blade layout, route naming, controller structure, Tailwind utility styles, and pagination.
- Existing `Product`, `Lesson`, and `MediaItem` relationships.
- Existing `HasPublishStatus` behavior and draft/published/archived states.
- Existing administrator and creator roles, including creator ownership by product.
- Existing Filament resource, form, table, action, filter, and media-upload patterns.
- Existing Laravel policies, factories/test setup, and database seeding conventions.

## Implementation Steps

### Phase 1: Discovery

- [x] Inspect Academy navigation and public routes.
- [x] Inspect authentication, roles, policies, and creator product ownership.
- [x] Inspect course and lesson publishing behavior.
- [x] Inspect Filament content-management resources.
- [x] Inspect media uploads and attachment storage.
- [x] Inspect database migrations, seeders, and related feature tests.
- [x] Decide against adding a second partner-submission workflow because the application has no existing moderated submission pattern.

### Phase 2: Data and Permissions

- [x] Add the `case_studies` table and indexes.
- [x] Add the `CaseStudy` model with publishability validation, search scope, media relationships, related lessons, and visibility rules.
- [x] Add `CaseStudyPolicy` for administrators and product-scoped creators.
- [x] Extend `User` with case-study management authorization.
- [x] Hide unpublished studies from ordinary Academy users.
- [x] Allow authorized editors to preview their unpublished studies.

### Phase 3: Academy Experience

- [x] Add Case Studies to Academy desktop and mobile navigation.
- [x] Add a Case Studies entry to the Academy home page.
- [x] Add a searchable listing page.
- [x] Add industry, Pilot feature, and difficulty filters.
- [x] Show title, short problem statement, industry, features, difficulty, and estimated implementation time on each card.
- [x] Add empty-filter results handling.
- [x] Add the ten required study sections.
- [x] Add cover image and diagram/attachment support through existing media items.
- [x] Add related Academy lessons and documentation links.
- [x] Use responsive layouts and keyboard-accessible native controls and links.

### Phase 4: Editorial Workflow

- [x] Add the Case Studies Filament resource.
- [x] Add create, edit, list, preview, publish, unpublish, archive, and delete actions according to existing permissions.
- [x] Require core editorial fields before publishing.
- [x] Store a general source/verification note.
- [x] Store a separate verification note for performance claims.
- [x] Add `anonymized` and `customer approved` flags.
- [x] Default studies to anonymized and not customer approved.
- [x] Scope creators to products they are permitted to manage.
- [x] Support related lessons, documentation links, cover media, and diagram media.

### Phase 5: Starter Content

- [x] Add a draft study for delivery arrival and departure monitoring with geofences.
- [x] Add a draft study for overspeeding detection and escalation.
- [x] Add a draft study for fuel event investigation.
- [x] Clearly identify the scenarios and expected outcomes as illustrative.
- [x] Avoid customer names, testimonials, measured savings, credentials, locations, and live vehicle data.
- [x] Seed all starter studies as drafts for editor review before release.

### Phase 6: Verification

- [x] Test Academy navigation, search, and filters.
- [x] Test all detail sections and related lessons.
- [x] Test draft privacy and authorized preview.
- [x] Test successful publishing of a complete study.
- [x] Test rejection of publishing when required content is incomplete.
- [x] Test creator product scoping.
- [x] Run related publishing, creator-role, and Academy translation regressions.
- [x] Run the frontend production build.
- [ ] Complete final manual browser acceptance on representative desktop and mobile viewports.
- [ ] Have a Pilot subject-matter expert review configuration terminology before publishing starter drafts.

### Phase 7: Documentation

- [x] Add the feature to `docs/CHANGELOG.md` under the current release.
- [x] Add the Case Studies menu item and editor workflow to the admin guide.
- [x] Add public search, filters, study contents, and publication visibility to the learner Help guide.
- [x] Update the Russian, Spanish, French, and Portuguese guide copies.
- [x] Record the completed feature and remaining acceptance work in `agent.md`.

## Verification Results

The host Composer installation is incomplete, so PHP tests were run against the rebuilt Docker application image.

| Check | Result |
| --- | --- |
| Whole suite, after the language pass | Passed: 397 tests, 5,983 assertions |
| Whole suite, as first written | **1 failed**, 394 passed — `test_no_panel_label_is_written_as_fixed_english_text`, 36 fixed English labels |
| `CaseStudyTest` | Passed: 8 tests, including a Russian partner, a French study page and a Russian editor form |
| Pint on `app tests lang` | Passed: 304 files |
| Chrome, 375px and 1280px, English and Russian | Listing and study: no sideways scroll, headings follow the language |
| `npm.cmd run build` | Passed |

## Files Added

- `app/Filament/Resources/CaseStudies/CaseStudyResource.php`
- `app/Filament/Resources/CaseStudies/Pages/CreateCaseStudy.php`
- `app/Filament/Resources/CaseStudies/Pages/EditCaseStudy.php`
- `app/Filament/Resources/CaseStudies/Pages/ListCaseStudies.php`
- `app/Filament/Resources/CaseStudies/Schemas/CaseStudyForm.php`
- `app/Filament/Resources/CaseStudies/Tables/CaseStudiesTable.php`
- `app/Http/Controllers/CaseStudyController.php`
- `app/Models/CaseStudy.php`
- `app/Policies/CaseStudyPolicy.php`
- `database/migrations/2026_09_29_000001_create_case_studies_table.php`
- `database/seeders/CaseStudySeeder.php`
- `resources/views/academy/case-studies/index.blade.php`
- `resources/views/academy/case-studies/show.blade.php`
- `tests/Feature/CaseStudyTest.php`

## Files Updated

- `app/Models/User.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/academy/home.blade.php`
- `resources/views/academy/layout.blade.php`
- `routes/web.php`
- `public/build/manifest.json`
- Compiled CSS in `public/build/assets/`
- `docs/CHANGELOG.md`
- `docs/admin-guide.md` and its Russian, Spanish, French, and Portuguese copies
- `docs/learner-guide.md` and its Russian, Spanish, French, and Portuguese copies
- `agent.md`

## Decisions and Constraints

- Draft and published states reuse the established publishing architecture. The existing archived state remains available through the shared status convention.
- A separate review state was not added because the current content architecture has no review lifecycle. Adding one only for Case Studies would introduce inconsistent approval semantics.
- Partner submissions are deferred. There is no existing moderated submission pattern to reuse, and building one here would create a second content system.
- Media uses existing `MediaItem` records. This keeps upload authorization and storage aligned with the rest of the application.
- Starter content remains draft until product and domain review is complete.
- Performance claims cannot be published silently: editors have a dedicated verification-note field, and the seeded studies make no measured claims.

## In Progress

- [x] Record implementation scope, status, tests, and handoff steps in this plan.
- [ ] Product owner acceptance of the public listing and detail layouts.
- [ ] Editorial review of the three seeded drafts against the deployed Pilot version and enabled product features.

## Yet To Do

These items are intentionally outside the completed implementation or need product-owner validation:

- [ ] Check keyboard-only navigation and visible focus states. Width and language were checked in Chrome at 375px and 1280px.
- [ ] Decide whether a case study should be translatable the way a course is (`HasContentTranslations`, a **Written in** field and the existing Translate action). Today its text stays in the language its author typed; only the surrounding screen follows the reader.
- [ ] Decide whether the **Source note** and **Performance claims** shown in the study sidebar should be editor-only — they read as internal verification notes but are public today.
- [ ] Review the three starter drafts with a Pilot product specialist and publish only approved content.
- [ ] Add a formal review/approval state if the wider Academy content lifecycle adopts one.
- [ ] Add partner submissions only after a shared submission, moderation, abuse prevention, and notification pattern is designed.
- [ ] Add richer attachment collections if future studies need more than a cover and one diagram media item.

## Editor Publishing Workflow

1. Sign in to the administration panel as an administrator or authorized creator.
2. Open **Case Studies** in the content navigation.
3. Create a study or open one of the seeded drafts.
4. Complete the title, slug, problem summary, classification fields, all required implementation sections, and source/verification note.
5. Confirm that identifying data has been removed and set the `anonymized` and `customer approved` flags accurately.
6. Add optional cover/diagram media, related lessons, and documentation links.
7. Save the draft and use **Preview** to inspect the public presentation while it remains hidden from ordinary partners.
8. Return to the Case Studies list and select **Publish**. Publication is blocked if required content or verification information is missing.
9. Open `/case-studies` and confirm the study appears in listing, search, filters, and its detail page.

## Next-Agent Handoff

An agent continuing this work should begin with the unchecked items in **In Progress** and **Yet To Do**. Preserve the existing role and product ownership rules, keep starter content unpublished until domain review, and extend shared Academy patterns before introducing Case-Study-only workflow concepts.
