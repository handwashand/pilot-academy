# Pilot Academy — agent guide

The one file for whoever picks this repo up next, human or AI: **the rules**
(how to work here) and **the memory** (what was done, what is half-finished,
and which traps have already cost someone an afternoon). Read it before you
start anything.

This is the repository's only agent instruction file. Keep rules and project
memory here so every coding agent works from the same source.

**Contents:** [Project](#project) · [How to work](#how-to-work) ·
[Standing instructions](#standing-instructions--read-before-you-start-follow-before-you-finish) ·
[Invariants](#invariants--do-not-simplify-these-away) ·
[Where things stand](#where-things-stand) · [Work log](#work-log) ·
[Verifying](#verifying) · [Writing the guides](#writing-the-guides) ·
[Traps](#traps-already-paid-for)

---

## Project

Internal training LMS for partners' students. **Laravel 13 + Filament 5** admin
panel at `/admin`, Blade + Tailwind public student site, **PostgreSQL** in
production (the test suite uses SQLite in memory). Requires **PHP 8.4**.

- **Content model:** `Course` → `Lesson` → `Question` → `Option`. Courses and
  lessons carry `draft | published | archived`. Lessons have a YouTube link or an
  uploaded video file. Quizzes are graded on the server. A course may add a
  final quiz, and passing it issues a certificate (PDF, dompdf).
- **People:** `users.role` is `admin`, `creator` or `learner`.
  - Admins run everything.
  - Creators own `Product`s and reach only those products' courses.
  - Learners (partners' students) belong to a `Company`.
  - Admins and creators use the panel. Learners use the student site.
- **Progress** is saved per account when signed in, or in the session for
  anonymous visitors.
- **Sign-in:** email and password, plus personal magic links and invite links
  (`/join`). An account made by link has no password of its own until the
  student sets one (`users.password_set_at`).
- **Languages:** the student site is English-first. UI strings go through
  `__t()` (`App\Services\Translator`, the `translations` table). Course and
  lesson fields can be translated through `HasContentTranslations`.
- **CSS:** Tailwind is compiled by **Vite** into `public/build/`.
  - The student site's entry is `resources/css/app.css`, with the brand palette
    in its `@theme`. The panel's theme is `resources/css/filament/admin/theme.css`.
  - CI builds the bundle (`.github/workflows/build-assets.yml`), and the result
    is **committed**, because the `git pull` deploy has no build step.
  - If the bundle is missing locally, the student layout falls back to the
    Tailwind CDN. Production always serves the committed CSS.
- **Deploy:** the server pulls `laravel`, then runs
  `php8.4 artisan migrate --force` and `php8.4 artisan optimize`.
  The server's plain `php` is 8.3.
- **Sister project:** `support-engine` (Support Training Hub) uses the same
  stack, and features are sometimes ported from it. The two serve different
  audiences, so port only what fits a partner academy. Refreshers were
  declined.

---

## How to work

This is an existing, working product with real users and data. The job is to
**understand → preserve → improve → verify**, not to rebuild. Treat the code as
the source of truth. Prefer evolving what is here over replacing it because you
would have designed it differently.

### Working order

1. **Read first, every time.** Before making any change, open and read the
   files, assets, migrations, docs and tests that the request touches. Do not
   edit from memory, from filenames alone, or from a guessed pattern.
2. **Inspect.** Find the models, controllers, actions, traits, Filament
   resources, routes, migrations and tests that the change touches. Search for
   similar functionality before creating anything new.
3. **Trace.** Follow the feature from the screen through the backend to the
   database. Note what depends on it.
4. **Plan.** Choose the smallest reliable change. For anything non-trivial, say
   which files you expect to touch.
5. **Implement.** Make focused changes in the architecture already used.
6. **Verify.** Run the tests and checks (see [Verifying](#verifying)).
7. **Review.** Read the final diff for regressions, security holes and needless
   complexity. Every changed line should have a reason.
8. **Report.** Say what changed and what was verified.

### Simplicity and existing code first

- Prefer Laravel and Filament built-ins.
- Do not create a Repository pattern, DTOs, Interfaces or Services unless they
  are clearly required. Single-purpose classes live in `app/Actions`.
- Reuse before creating: no second copy of a component, a validation rule, a
  query or a business rule. If the existing one falls short, improve it. Code should be maintainable and understable.
- Complexity must earn its place. Three readable lines beat an abstraction used
  once. Do not add a package, table or config layer a small change does not
  need.
- New code should read as if the original team wrote it: the same naming,
  folder layout, error handling and UI patterns.

### Editing scope

- Touch only files related to the task. No unrelated refactors, and no mass
  reformatting. Run Pint on the files you changed, not the whole tree.
- Assume existing behaviour is intentional until proven otherwise. Do not
  casually rewrite a working module, rename widely used methods, change
  database structures or auth behaviour, or delete features.
- If a breaking change is truly needed, explain why, what it affects, and what
  migration it needs.
- Found an unrelated problem? Classify it before touching it:
  - **Critical:** security, data loss or serious reliability. Fix it.
  - **Related:** it belongs in this change.
  - **Unrelated:** mention it and leave it alone.
- Another agent session may be editing the same working tree. Edit against exact
  current text, and do not reformat or rewrite files you did not change.

### Database changes

- Treat schema changes as potentially destructive, and never assume an empty
  database.
- Check the existing tables, relations, foreign keys, indexes, nullability,
  defaults and the queries that read them.
- Prefer backward-compatible migrations that roll back cleanly. Backfill
  existing rows when a new column changes behaviour.
- Anything that must run on PostgreSQL must not rely on SQLite leniency: case
  in `LIKE`, JSON operators on text columns, boolean literals.

### Security

Security is part of the implementation.

- **Never:**
  - hardcode or commit secrets
  - trust client-side validation alone
  - build SQL from strings
  - leak internal errors to users
  - bypass or disable authorisation to make something work
- **Always consider:**
  - authorisation both server-side and in the query (policies *and* scoped
    lists)
  - sanitise all untrusted input before using it, including request data,
    URL/query parameters, file metadata and text fields; never trust raw input
  - input validation, and output escaping and XSS
  - CSRF and mass assignment
  - file uploads and rate limiting
- For the rules this app already enforces, see
  [Invariants](#invariants--do-not-simplify-these-away).

### Backend

- Validate input and sanitise untrusted values before storing, querying or
  rendering them.
- Handle realistic failures.
- Avoid N+1 queries and repeated queries per request. For example, navigation
  badges run on every panel page.
- Return proper status codes.
- Keep business rules on the server and out of the browser.

### Frontend and UX

- Respect the existing visual language, and do not redesign unrelated screens.
- **Never change the design of the logo, favicon, lockup, mark, colours or any
  brand artwork unless the owner explicitly asks for that specific design
  change or supplies replacement assets.** Fixing references, cache-busting,
  sizing, file paths or deployment issues must preserve the existing artwork
  exactly.
- Build more than the happy path: loading, empty, error, success and disabled
  states, plus long content and double submits.
- Check what happens with no data, hundreds of records, a deleted record, or
  missing permission.
- Reuse the existing buttons, cards, forms, spacing and notifications.
- Mind keyboard use and accessibility.

### Mobile (student site)

Every student-facing page (home, course, lesson, final quiz, certificates,
profile, login, register, help, and any future learner page) **must work on a
phone**. Phones are a first-class target.

- Design mobile-first, and check at ~375px, not only on desktop.
- No horizontal overflow, and comfortable padding on small screens.
- Touch targets about 44px tall (`min-h-11` / `h-11`), including quiz answer
  options.
- One column on mobile. Use more columns only from `sm:` or `lg:` up.
- Video uses `aspect-video`. Images in lesson content must not overflow
  (`max-width: 100%`).
- The header must not overflow or hide key actions on narrow screens.

The Filament `/admin` panel is desktop-first and exempt.

### Performance and dependencies

- Do not optimise prematurely, but avoid the obviously wasteful: N+1 queries,
  missing pagination, large scans and loading unused assets.
- Do not install a package for something the framework or codebase already
  does. If a new dependency is genuinely warranted, say why.

### Debugging

- Find the root cause.
- Never hide an error, swallow exceptions, remove validation, comment out a
  failing check, or replace working architecture over one bug.
- Use the logs, stack traces, database state and tests. Several of the
  [traps](#traps-already-paid-for) looked like something else at first.

### When unsure, or when the request conflicts with the code

- If requirements are unclear, stop, explain what is uncertain, and ask.
- If the requested approach conflicts with the architecture, security, data
  integrity or existing product behaviour, say so and recommend the smallest
  safer alternative.

### Definition of done

A task is done when all of these hold:

- The behaviour works, and existing behaviour still works.
- Error cases are handled and security has been considered.
- Project conventions are followed and nothing is duplicated.
- Tests and checks were **actually run**, and any failures were investigated.
- The diff has been reviewed.
- The docs and this file are updated (see the standing instructions below).

### Final report

Keep it short:

- **Implemented:** what changed.
- **Files changed.**
- **Why:** the decisions that matter.
- **Verification:** what was *run*, kept separate from what was only reviewed.
- **Risks / notes:** anything to know before deploying.
- **Next steps:** only if genuinely useful.

---

## Standing instructions — read before you start, follow before you finish

**1. Document after every task.** Before you call a piece of work done, add an
entry to the [work log](#work-log) below: what changed, why, and anything left
open. Newest first. A one-line entry beats no entry.

**2. Always update `docs/CHANGELOG.md`.** It renders live in the panel under
**Docs → What's new**, and as a PDF, so admins read it.
- Newest first, dated, in plain language: describe what a person can now do,
  not which class you added.
- Put entries under the current version heading in `### Added` / `### Changed` /
  `### Fixed` / `### Known limitations`, which drive the page's category filter.
- Required whenever you change anything an admin or student can see.

**3. Always check the guides, `README.md` and `DEPLOY.md`.**
- `docs/admin-guide.md` renders under **Docs → Guide**
  (`app/Filament/Pages/AdminGuide.php`).
- `docs/learner-guide.md` is the student **Help** page (`/help`,
  `AcademyController@help`). Its translations are `docs/learner-guide.{ru,es,fr,pt}.md`.
- Both guides go stale silently. If you changed a screen, a button, or a rule
  someone follows, the guide is part of the change, not a follow-up.
- Update `README.md` when the stack or local setup moves, and `DEPLOY.md` if you
  touched deployment.
- See [Writing the guides](#writing-the-guides) for how to get them right. A
  styled copy of the guide, with screen mockups for print, is kept as a Claude
  Artifact by the team lead. Keep it in sync when it is being updated.

**4. Never report work as verified unless you ran it.**
- Run the tests and Pint, and for anything visible, actually load the page.
- If you could not run something, say so plainly.
- See [Verifying](#verifying) for how, because it is not obvious in this repo.

**5. Git: agents do not commit or push.** Leave changes uncommitted in the
working tree, because the owner reviews and commits them (owner's instruction,
2026-09-10).
- Work on a feature branch off `laravel`, never `main`.
- Open a PR into `laravel`, and **do not self-merge**.
- Commit messages are a subject line, a blank line, then a body, with **no AI
  attribution** (no `Co-Authored-By` trailers).

---

## Invariants — do not simplify these away

Each of these looks like over-engineering from the outside and has a reason.
Every one is pinned by a test; if your change makes that test fail, the change
is wrong, not the test.

| Rule | Why | Held by |
| --- | --- | --- |
| **A lesson is finished only by passing its knowledge check** — every answer right. Reading or watching never finishes it. | Completion unlocks the final quiz and the certificate, which is a public claim. | `AcademyController::submitQuiz` · `AcademySmokeTest` |
| **Video position is its own table, never a column on `lesson_user`.** | `completedLessons()` and the dashboard read that pivot without filtering on `completed_at`; a row written on "play" would count as a finished lesson everywhere. | `test_watching_a_video_does_not_mark_the_lesson_complete` |
| **Drafts and archived content never reach a student** — not in listings, not by URL, not in search, not by submitting a quiz. A lesson shows only if it *and* its course are published. | Publishing is the only gate between work in progress and partners. | `CoursePublishingTest` · `LessonPublishingTest` · `test_a_draft_lessons_transcript_is_not_searchable` |
| **Creators reach only their own products** — lists, URLs, policies, the associate search, and dashboard warnings. | A creator is a product owner, not an admin. Scoping the record but not the list still leaks. | `CreatorRoleTest` · `CourseLessonsRelationTest` |
| **Learner data is admin-only and counts learners only.** Every dashboard figure goes through `ReportsOnLearners`. | Staff previews issue real certificates and completions; creators have no business seeing partner data. | `test_reports_count_learners_only` · `test_learner_data_widgets_are_hidden_from_creators` |
| **Student search uses `LOWER(…) LIKE ?`.** | SQLite's LIKE ignores case, PostgreSQL's does not — tests would pass and production search would miss. | `test_search_is_case_insensitive` (on SQLite — it cannot prove the Postgres case; the query shape does) |
| **A YouTube link is parsed to an id and the embed is rebuilt from it** (`Lesson::youtubeIdFrom`), never pasted into the iframe. | An unparseable link left lessons silently videoless; a raw URL in `src` is a script-capable sink. | `YoutubeLinkTest` |
| **Docs render with `html_input => strip` and `allow_unsafe_links => false`.** | The files are ours, but nothing in them needs raw HTML, and being wrong about who can edit them is costly. | `test_raw_html_and_unsafe_links_are_not_rendered` |
| **The suite refuses any database but `:memory:` or one named `*test*`.** | With config cached, `RefreshDatabase` wipes the database the cache names — and every test still passes. Rehearsed 2026-09-10. | `tests/TestCase.php` · `TestDatabaseGuardTest` |
| **Attempts left are counted only by `Course::finalQuizAttempts…For` and `Lesson::quizAttempts…For`.** | They add admin grants (`attempt_grants`). Counting attempts anywhere else silently ignores a grant and locks the student out again. | `QuizAttemptGrantTest` |
| **Broken content has one definition: `App\Actions\FindContentProblems`.** | Content health, its badge, the list flags, edit-page banners, publish checks and owner alerts all read it; a second copy would disagree with the rest. | `ContentHealthTest` · `ContentHealthWorkflowTest` |
| **Student-site words ship in `lang/{code}/academy.php`, with the same keys in every language.** New text goes in all five files. | The deploy never seeds, so text that only exists in `LanguageSeeder` shows as a key name in production. A key missing from one language quietly falls back to English. | `StudentSiteTranslationTest` |
| **Which courses a lesson is in is `course_lesson`. `lessons.course_id` is only its home (who may edit it).** Query a course's lessons through `Course::lessons()` or the pivot, never `where('course_id')`. | A lesson can be shared. Anything reading `lessons.course_id` misses it in every other course: empty course pages, wrong completion, missed problems. | `SharedLessonTest` · `CourseLessonsRelationTest` |

---

## Where things stand

Last updated: **2026-10-01**

**2.0.0 is released** — tagged `v2.0.0` at `4fa98c7c`, the first version number
this project has had. **Next release is 2.1.0** (`feature/support-engine-ports`
moved both the changelog heading and `config/app.php`). The filterable What's
new merged *after* the tag, so its entry moved from 2.0.0 into 2.1.0 too.
Earlier changelog entries are month-only and are deliberately *not* renumbered
after the fact.

### Cutting a release

The version lives in **two places that must move together**:

1. `config/app.php` → `'version'`. A literal, not an env value: it describes the
   code, not the server. This is what the panel renders.
2. The heading in `docs/CHANGELOG.md` (`## 2.0.0 — September 2026`), which is
   what admins read under **What's new**.

It is shown at the bottom of the admin sidebar via the
`PanelsRenderHook::SIDEBAR_FOOTER` hook (`resources/views/filament/sidebar-version.blade.php`),
linked to the What's new page. **That view is styled with inline CSS** — it was
written before the panel had a Tailwind theme, when a class there would have
done nothing (see the trap below; utilities *do* work in panel views now, but
three declarations are not worth rewriting). Its greys use Filament's own
`--gray-*` custom properties, which Filament injects per page, so they work in
both themes.

Tag the **merge commit on `laravel`**, not a feature branch: `git tag v2.0.0`.
There are no tags in this repo yet, so `v2.0.0` will be the first.

### Branches

**`laravel` is the production branch** — the one to maintain. Everything else
serves it.

| Branch | State |
| --- | --- |
| `laravel` | **Production.** Deploys by `git pull`, at `e8ac5dcc`. Merging `feature/support-engine-ports` brings 15 commits and **six migrations** (case studies, their language column and step images, webinars, tutorials, activity-event subject context), so that deploy must run `php8.4 artisan migrate --force` — not a plain pull. |
| `stable` | A snapshot of the deployed tree, taken 2026-10-01 before that merge, at `9b51add3`. Its tree is byte-identical to `laravel`. **Local only** — push it if it is to be a rollback point anyone else can reach. |
| `feature/support-engine-ports` | **15 commits ahead of `laravel`, not merged.** Case Studies, Webinars, Tutorials, the dashboard rework, password reset, the translations matrix, and writing a lesson from inside its course. The current guide (this file) lives here: `laravel` still carries the old `CLAUDE.md` + `agent.md` pair, and the consolidation arrives with this merge. |
| `feature/admin-dashboard` | The local copy is merged into `laravel`, but **`origin/feature/admin-dashboard` is one commit ahead and not merged** — that is PR #34. Leave the remote alone until that commit lands or is dropped. |
| `feature/sqlite-postgres` | Merged into `laravel`. The *code* is in; the cut-over itself has not happened — see Open threads. |
| `main` | **Not the deploy branch.** An unrelated "Initial commit" history plus merges of `laravel` (`9b51add3`, same tree). Branch from `laravel`. |

**Deleted locally on 2026-10-01**, all fully merged into `laravel`, and all still
on the remote: `feature/whats-new-page` (`e3253197`),
`feature/course-publishing-workflow` (`1a6fc0f7`), `feature/creator-role`
(`40f7896e`), `feature/learner-experience` (`3ffd68fe`). Recover one with
`git branch <name> <sha>`. `feature/postgres-migration` no longer exists
anywhere.

**The remote still holds 26 branches already merged into `origin/laravel`.**
Tidying those is a push, so it is the owner's call, not an agent's.

### Open threads

- **PostgreSQL cut-over is done — production runs Postgres as of 2026-10-01.**
  The long-standing "it is written and rehearsed but not live" note is retired.
  What this changes for everyone working here: **SQLite leniency no longer
  hides anything in production.** The suite still runs on SQLite in memory, so
  it cannot see a Postgres-only failure — the `select distinct lessons.*` bug
  under *Traps* passed every test and broke the real panel. Anything using
  `distinct`, JSON operators, `LIKE` case, or boolean literals wants checking
  against the Postgres container, not just the suite. The rollback order in
  `docs/postgres-cutover.md` still applies if it ever has to go back: **roll the
  migration back before reverting the code.**
- **One commit of `feature/admin-dashboard` never landed.** Its work — widgets,
  bulk actions, an export, branding — is in `laravel`, but the remote branch
  holds one further commit (PR #34). Decide whether it goes in or goes away,
  rather than leaving the branch to rot.
- **`feature/support-engine-ports` still needs a PR into `laravel`.** The other
  two named here before — sqlite-postgres and admin-dashboard — are merged into
  `laravel`, bar the one commit still sitting on `origin/feature/admin-dashboard`.
- **Two bigger features are waiting on decisions**, not code: video engagement
  and multilingual. See `docs/plans/support-engine-features.md` — each has a
  "Decide first" list. Do not start either without answers.
- **`APP_URL` must be the real domain in production.** The certificate email
  builds its logo URL from it; a wrong value ships broken images to students.

### Decisions — do not re-litigate

Decided by the product owner. Reopen one only with a new reason, and say so.
Newest first.

| Date | Decision |
| --- | --- |
| 2026-10-07 | **ChatGPT, DeepSeek and DeepL tokens are managed by admins under Settings → Integrations** and stored encrypted in the database, instead of the server `.env` — the owner's explicit request, unlike mail and Descript. Tokens are write-only in the UI; only fixed official provider addresses are used; the rights `deepl.translate` / `ai.translate` decide who may spend them. |
| 2026-10-02 | **Descript video translation is assigned per account.** The server environment connects the optional integration; `descript.translate` controls who may spend its credits, and every start requires an acknowledgement. Neither Admin nor Creator receives the right automatically. Underlord can edit project scripts, but its beta project/prompt/export API is not a stable general text-translation service for Academy fields. |
| 2026-10-02 | **Descript supplements manual translation; it never replaces it.** A lesson keeps its existing manual **Translate** action for editor-written content. Descript has a separate, conditional video-translation action and must not hide, rename or trigger the manual workflow. |
| 2026-10-02 | **DeepL was parked — reopened the same day by the owner and now built (see 2026-10-07).** Original wording: Do not implement or configure it unless the owner explicitly reopens the decision. Descript's media transcript translation does not start a general Academy text-translation rollout. |
| 2026-09-14 | **Content problems are not dashboard material.** The card moved to Content → Content health (menu badge), with flags on the Courses/Lessons lists and edit pages, checks before publishing, and bell alerts to the content's owner. |
| 2026-09-14 | **The student header uses the PILOT ACADEMY lockup** at the panel's 1.75rem — owner's request, replacing the earlier mark-plus-text choice. |
| 2026-09-10 | **Refreshers declined.** Pilot Academy certifies partners on a course; Support Training Hub (`support-engine`) tracks staff competency over time. The two serve different purposes, so its competency features — levels, refreshers, rubric marking, trainer cohorts — are not ported here by default. |
| 2026-09-10 | **Ported from support-engine:** Help page, What's new PDF, profile page, Docs group, guide search, privacy-enhanced YouTube, YouTube link validation, the test-database guard. **Checked and not needed:** its dashboard (already here), private-storage video fixes (uploads are on the public disk), HTML sanitising of lesson content (Filament strips it on save — proven with a tampered payload). |
| 2026-09-08 | **No self-merge.** Branch off `laravel`, open a PR; the owner merges. Asked to merge directly, the owner chose a PR. |
| 2026-09-02 | **Course feedback is staff-only**, one verdict per student per course. No public star ratings. |
| 2026-09-02 | **"Add existing lesson" moves, never copies.** `lessons.course_id` is not nullable; reuse across courses is **Duplicate** on the course. |

---

## Work log

Newest first. Add to this every time.

### 2026-10-08 — Settings became a Cluster with tabs; a profile photo; Integrations' Model is a dropdown

**Settings, second attempt.** The owner first asked for one sidebar row that
opens a dialog with Profile, Translations, Integrations, Mail and Languages.
That was built (App\Livewire\SettingsPanel, an Action-modal with a vertical
Tabs schema) and worked, but the owner then asked to go back to a page with
tabs instead of a modal. Rather than patch the modal, it was replaced with
Filament's own **Cluster** mechanism (`App\Filament\Clusters\Settings`),
which is built for exactly this: one sidebar entry, and every member page
gets a real tab strip across the top
(`SubNavigationPosition::Top`) with full page navigation — no modal
machinery, no Action-schema workarounds for Translations' 1,000-row table or
Mail's confirmation action, because each tab is simply the page it already
was. `Integrations`, `MailCheck`, `LanguageResource` and `TranslationResource`
each just gained `protected static ?string $cluster = Settings::class;`
and dropped their own `getNavigationGroup()`; **`shouldRegisterNavigation()`
was reverted to its original form on all four** — Filament's
`registerNavigationItems()` already skips a clustered page in the main
sidebar unconditionally (`Pages/Page.php` and the Resource `HasNavigation`
trait both check `getCluster()` first), so the old "return false" I had
added for the modal attempt was redundant and, worse, would have hidden the
tabs too (the cluster's tab strip reuses the same method to decide what to
list). The SettingsPanel Livewire component, its two Blade views, and the
sidebar's hand-built NavigationItem trigger were deleted outright.

A new `App\Filament\Pages\SettingsProfile` (cluster sort 5, so opening
**Settings** lands here first — Cluster::mount() redirects to the first tab
on its own) is the Profile tab: photo, name, email, password, same shape as
Integrations.php (`form()`/`content()`/`save()`), not reused from anywhere
else because there was nothing to reuse from.

**docs/admin-guide.md's "menu at a glance" table** collapses the old
**Settings → Mail / Integrations / Translations** rows into one
**Settings → General** row (`AdminGuideMenuTest` was extended to skip any
resource/page with a `getCluster()`, matching what Filament itself excludes
from the main sidebar — a clustered page's `shouldRegisterNavigation()` can
be `true` and it still will not appear there). `docs/CHANGELOG.md` describes
it as tabs across the top, not a dialog — the wording was written once for
the modal version and had to be corrected, which is itself the reason to
read a feature's own screen before writing about it, not the plan.

**A profile photo**, for every account type. `users.avatar_path` (nullable
string). `User implements Filament\Models\Contracts\HasAvatar`; its
`getFilamentAvatarUrl()` and the new `avatarUrl()` helper are the same
method — Filament's own topbar/user-menu avatar, the Settings → Profile
circle, and the student header's account-menu circle all read the one
helper, so there is nowhere a photo can be saved and not show up. Three
places needed the upload field, not two, once the account menu's own
**Profile** link (Filament's stock `->profile()` page) was considered: it
edits the *same* `avatar_path`, so leaving it without a photo field would
have meant two "Profile" screens that disagreed. Rather than duplicate
EditProfile's field set, `App\Filament\Pages\AccountProfile extends
Filament\Auth\Pages\EditProfile`, overriding only `form()` (prepend a
`FileUpload::make('avatar_path')->avatar()`) and `handleRecordUpdate()`
(delete the previous file once the new one is confirmed saved) — everything
else (rate limiting, multi-factor section, validation) stays the vendor
page's own code, read once. `AdminPanelProvider` now does
`->profile(AccountProfile::class, isSimple: false)`.
`tests/Feature/ProfilePageTest.php` was pointed at `AccountProfile::class`
instead of the vendor class it had tested directly, since that vendor class
is no longer what the route actually serves.

On the student site, `ProfileController::update()` takes an `avatar` file
(`nullable|image|max:2048`), stores it on the `public` disk under
`avatars/`, and deletes the previous file — the only place in this codebase
that cleans up a replaced upload; no other `FileUpload` field does this, so
there is no shared helper to reuse yet. `resources/views/academy/profile.blade.php`
needed its first `enctype="multipart/form-data"`, a `file:` Tailwind variant
never used before (confirmed compiled into `public/build/assets/app-*.css`
afterwards — `grep -c "file\\:mr-3\|file-selector-button"`, not the
unescaped form, which matches nothing even when the class did compile).

**Testing a Filament `FileUpload` field that already holds a value, with a
second raw `UploadedFile::fake()`, throws inside Filament's own validation**
(`BaseFileUpload::getValidationRules()` expects an array and is handed a bare
`TemporaryUploadedFile`) — reproducible even after explicitly clearing the
field first. Uploading from an *empty* field works cleanly. The replace-and-
delete-old-file behavior is real and was proven live in the browser
(uploaded, saved, re-fetched the database row, found a new path, found the
old file gone from the fake disk) and is covered by a test on the plain
Laravel controller side (`StudentProfileTest`, no Filament `FileUpload`
involved), but the two Filament-side tests
(`SettingsClusterTest`, `ProfilePageTest`) only assert the first-upload case
— replacing a Filament-side avatar in a test is a known rough edge to come
back to, not a product bug.

**Settings navigation no longer reloads the page.** `->spa()` on the panel
(`AdminPanelProvider`) — Filament's SPA mode, Livewire `wire:navigate`
throughout. This is panel-wide; Filament has no per-cluster or per-section
SPA switch, so every internal link in the admin panel benefits, not only the
Settings tabs that prompted it. Checked for custom Blade views relying on a
real page load (a plain `<script>` with `DOMContentLoaded`-style logic would
not re-run on a soft navigation) — none of the render-hook partials
(`resources/views/filament/*.blade.php`) contain a `<script>` tag at all, so
there was nothing to convert. Verified live: planted `window.__marker` after
login, clicked between Settings tabs and other sidebar items, and the
browser's `load` event never fired again (`page.on('load', …)` count stayed
at 0) until the next real `page.goto()`.

**Integrations: a provider's Model is a `Select`, not a `TextInput`.**
ChatGPT's and DeepSeek's cards had an extra field (Model) that Descript's and
DeepL's do not, which is a real, by-design difference (only the two chat-style
providers take a model name) — the fix was not to force every card to the
same shape, but to make the one field that does differ read the same way a
dropdown reads, rather than inviting a typo'd model name. `AiProvider::PROVIDERS`
grew a `model_options` array per provider (official current model names);
blank still means "use the provider's own default", exactly as the text
field did, so nothing about `AiProvider::persist()`'s save logic changed.

**Verified (run):** full suite in Docker, 551 passed (up from 547 — four new
tests: one photo-upload test each on `SettingsClusterTest`, `ProfilePageTest`,
and two on `StudentProfileTest` covering upload-and-replace and a rejected
non-image file). Pint clean across `app` and `tests` (239 files). Driven live
in Chrome: logged in, clicked **Settings** → real URL navigation
(`/admin/settings/settings-profile`) with no full page reload, every tab
(Profile/Translations/Mail/Integrations, Languages absent — this account has
no `languages.manage`) confirmed by sampling the body text; uploaded a photo
on the Settings → Profile tab, saved, confirmed in the database the stored
path exists on the `public` disk and resolves to a working URL, and confirmed
on a fresh page load that both the Profile tab's own circle and the topbar
account-menu avatar show the photo (the one failed selector in an earlier
screenshot was a test-script bug — `.fi-user-avatar img` instead of
`img.fi-user-avatar`, since `<x-filament::avatar>` compiles straight to an
`<img>`, not a wrapper around one). Integrations' Model fields confirmed as
real dropdowns, both showing "Leave blank to use …" placeholder text.
**Not verified:** the translated guide copies
(`docs/learner-guide.{ru,es,fr,pt,ar}.md`) were found to already be missing
whole sections, including any "Your profile" section — pre-existing
staleness unrelated to this work, left as found rather than silently
patched or silently ignored.

### 2026-10-07 — Integrations page tidied; three things found by the owner testing

**Layout.** The Integrations page is a grid of compact cards (1 / 2 / 3 across),
each provider's description behind an info icon, the token advice said once in
the subheading — so more integrations can be added without the page sprawling.
Checked in Chrome at 1440 and 1024: no sideways scroll.

**A saved token under another `APP_KEY` no longer takes the page down.** Rebuilding
the local container makes a new key (`docker/entrypoint.sh` generates one whenever
`.env` has none), and the stock `encrypted` cast then threw `DecryptException` —
a 500 on the Integrations page and wherever the provider was read. `AiProvider`
now encrypts through its own accessor, and an unreadable token reads as "none
saved". **Locally, every `up --force-recreate` loses saved tokens; paste them
again.** Production keeps its key in `.env`, so only a deliberate key change does it.

**Admins need no extra right for the text engines** (DeepL, ChatGPT, DeepSeek):
the admin is the one adding the key, and the owner expected the button to appear.
Creators still need `deepl.translate` / `ai.translate`. **Descript is unchanged —
opt-in per account, admins included — per the 2026-10-02 decision.**

**Already translated → edit, unless told to replace.** Choosing an engine now
says which languages already have text and keeps them; a checkbox ("Replace the
existing translations with new drafts") drafts every box instead. Either way it
only fills the window: the saved text changes on Save translations. The whole-
course wizard does the same for the chosen language.

**Verified (run):** `IntegrationsTest` + `DeepLDraftTranslationTest` 28 passed,
plus course/content/translation-parity suites; Pint clean; screenshot reviewed.
**Not run:** the full suite after these last changes (it was 536 passed before).

### 2026-10-07 — Dubbing (the reason for Descript), and video download

The owner clarified that **voice is why Descript was integrated**; subtitles are
secondary. Dubbing is built and was proven live.

**How a dub is made.** `video_translations.kind = dub` rows run
pending → translating → **dubbing** → exporting → done. Dubbing is a second
agent instruction on the translated composition (`services.descript.dub_prompt`);
then the composition is published **with its picture** (no `media_type`, so
Descript picks video, or audio for an audio-only source) and the file is
downloaded at once from the job's `download_url` — it expires — into
`video-translations/lesson-{id}/{import}-{lang}-dub.mp4`, through a plain client
(no token to the storage host). A dub requested after subtitles **reuses that
composition** instead of paying to translate again, and vice versa. `claimJob()`
(job_id null → `starting`) guards both the dub and the publish against a double
click. Editors choose **Voice** (default) or **Subtitles and transcript only**
on the lesson and course actions.

**Live, 2026-10-07 (8-second synthetic clip → French):** the probe dub cost 9.66
credits; the full run through the app (translate + dub) 18. With no assigned
speaker Descript used a stock voice ("Julien") and said so in `agent_response`,
which is kept on the row. The dubbed audio differs from the original (md5), and
its subtitles are French. The dub's first caption carried the voice's name
("Julien: Bienvenue…"); it is stripped for dub rows. The agent replies "still
generating" when its job stops, yet publishing right after returned the dub.
**Unproven on a real lesson video:** whether a long dub is always finished when
the publish runs, voice cloning of a real speaker, render time, and file size.

**Player.** A reader in a dubbed language gets the dubbed file as the video
source; **Play original audio** (`?audio=original`) switches back. Captions now
come from either kind, one track per language.

**Video download.** `GET …/lessons/{l}/videos/{n}/download` (auth): admins always,
anyone else only with the new `videos.download` right, a per-account checkbox —
so an admin can grant it to a learner. It serves the version being watched (the
dub, or the original). Without the right the button is absent and the player gets
`controlsList="nodownload"`. This is not copy protection: uploads are on the
public disk and are streamed to the browser to be played.

**Verified (run):** Descript, dub, download, caption, course and translation-parity
suites green (67 tests) before the voice-name fix; Pint clean. Full suite run
after this entry was written — see the final report in the conversation.

### 2026-10-07 — Translate menu, whole-course translation, course videos

**Translate is now a menu** when an engine is available: *Translate by hand*, or
*Translate with DeepL / ChatGPT / DeepSeek* — only what is switched on **and**
allowed for that person (`TranslateContentAction::engine()`). An engine choice
asks for confirmation (it is paid), then `replaceMountedAction('translateContent',
['engine' => …])` opens the usual window with the empty boxes drafted. The choice
arrives from the browser, so the engine is re-checked on the server when the
window fills (`initial()`); a forged argument drafts nothing. With no engine it
is the plain button as before. The in-window "Generate missing" buttons were
removed in favour of this. `ENGINES`, `engine()`, `input()` and `reportDraft()`
are public because the course action reuses them.

**Translate whole course** (`TranslateCourseAction`) is a two-step wizard on the
course: step 1 language + how (by hand or an engine), step 2 the review — the
course and each lesson, drafts in the empty boxes, saved only by *Save
translations*. Drafting is `DraftTranslations::handleMany`: all empty boxes of
the course and its lessons go out together (one request for plain text, one for
HTML for DeepL; `LlmTranslator` now splits into 30 KB groups). Only lessons in the
course's own language and that the user may edit are listed; human-written text
is never sent or replaced. State keys are `course.*` and `lesson_{id}.*`.

**Translate lesson videos** / **Check progress** on a course (Descript): every
uploaded video in the course's lessons × the ticked languages, behind the same
right and acknowledgement. `request(..., advance: false)` only *records* the
requests so a big course cannot run out one web request; Check progress (20 s
budget) and `descript:sync` start them a few at a time. Videos/languages already
done or under way cost nothing (the unique key still holds).

**Verified (run):** `CourseTranslationTest` 8, `DeepLDraftTranslationTest` and
`IntegrationsTest` (rewritten for the menu), translation-parity tests; Pint clean.
The full suite was 509 passed before this step. **Not verified:** the menu,
wizard and rich-editor drafts in a browser, and a course-wide run with a real
engine (the fake answers; real latency for a 20-lesson course is unmeasured).

### 2026-10-07 — Descript proven live; tokens in Settings → Integrations

**The transcript flow was run for real** (owner-approved, one ~8-second synthetic
spoken clip into French) and it needed three fixes before it worked — none of
which any fake could have shown. Full account in
`docs/descript-integration.md` → *Proven on a live account*; the short version:
(1) a project in a folder needs `team_access`; (2) the import must carry
`add_compositions` or the media sits in no composition (duration 0, empty
transcript); (3) **Descript's transcript export returns the original script, not
the translation** — the translated words are the subtitles of a *published* page,
so `export()` now publishes the composition once (private, audio) and stores the
WebVTT from `GET /published_projects/{slug}`, deriving the plain transcript from
its cues. `TranslateLessonVideo::export` is a small state machine of its own now
(`job_id` null → claimed `publishing` → publish job id), so two clicks cannot
publish twice. Captions serve a stored `.vtt` untouched (`toVtt` is idempotent).
Cost per run: 8 media seconds and 7 AI credits; each leaves a project and a
private published page in the Descript drive that the API cannot delete.

**Descript's token can now be saved in Settings → Integrations**, as DeepL's can.
A saved token is authoritative *including its switch* (`AiProvider::withToken` —
not `saved`, which Eloquent already owns); with nothing saved, `.env` applies. The
scratch scripts that drove the live run live in the session scratchpad, not the
repo, and read the token from the environment only.

**Verified (run):** live run end to end (four runs, last one clean); focused suite
82 passed (Integrations, Descript, captions, DeepL, translation parity, guide
menu); Pint clean. **Not run:** the full suite after these last changes; every
language at once on real Descript; a real lesson video; a public-URL import.
**Dubbing is not started** — the agent itself offered to dub, so it looks
possible, but it is a separate phase with its own player work.

### 2026-10-07 — Descript: captions on the player (step 7); what remains

Open question 1 is answered and built: a lesson's uploaded video now offers every
**finished** Descript language as captions. Browsers only read WebVTT in a
`<track>`, Descript exports SRT, so `GET /courses/{c}/lessons/{l}/captions/{video}/{language}`
converts the stored file on the way out (`VideoTranslation::toVtt`). Route
constraints (`whereNumber`, `[a-z]{2,10}`) keep the language out of any path; the
file path comes only from the database row, never the URL. Served only when the
lesson is visible to the viewer, the row is `done` and the file exists; the
response is `private` so a draft lesson's captions are never shared-cached. The
viewer's own language is `default`, so it switches on by itself. Guides: changelog,
and the learner guide in English, Russian, Spanish, French and Portuguese (the
Arabic learner guide has no matching "Transcript" line to hang it on — add it
when that guide is next revised).

**Verified:** `VideoCaptionsTest` (6) plus the Descript, lesson, video and
shared-lesson suites — 111 passed; Pint clean; full suite 499 passed before this
step. **Not verified:** captions in a real browser, and on real Descript output.

**Descript is built end to end except what needs your credits:** step 9 (a live
run) and dubbing. Both wait on the owner's 0.5 (OK to send lesson videos to
Descript) and 0.6 (a short synthetic video *with speech*). Nothing here spends
credits until that is given.

### 2026-10-07 — Settings → Integrations; drafts with DeepL, ChatGPT, DeepSeek

DeepL steps 2 and 3 are done, and the owner widened the scope: a **Settings →
Integrations** page (admins only) where an admin pastes the API token for DeepL,
ChatGPT or DeepSeek and switches each on. An enabled provider adds a **Generate
missing with …** button inside the existing **Translate** dialog, for accounts
holding `deepl.translate` (DeepL) or `ai.translate` (ChatGPT/DeepSeek) — a
checkbox under People → Users, like the Descript right.

The button only fills the dialog's **empty** boxes (`App\Actions\DraftTranslations`
returns text and stores nothing); an editor reads it and presses **Save
translations**. A box with text is never sent or replaced; a failure keeps the
drafts already made and says why in the reader's language. HTML (`content`) goes
as HTML, plain fields as plain text.

**Deliberate departure from "secrets live in `.env`":** these tokens are saved in
`ai_providers`, encrypted with the `encrypted` cast (so with `APP_KEY` — a new key
makes them unreadable), hidden from serialisation, never loaded back into the
form (blank keeps, a checkbox removes). Each provider has one fixed official
address, so a token cannot be pointed at a host an admin typed; a DeepL key ending
`:fx` goes to the Free host, any other to Pro. DeepL still falls back to
`DEEPL_*` in `.env` when nothing is saved. Mail and Descript are unchanged (.env).

`LlmTranslator` serves ChatGPT and DeepSeek (same chat-completions protocol, JSON
answer, temperature 0, the text declared as content not instructions) and caps one
request at 60 KB. Default models `gpt-4o-mini` / `deepseek-chat`, changeable on the
page. Not built: a "test connection" button, and splitting an oversized lesson
across several LLM requests (it refuses with a message instead).

**Verified (run):** `IntegrationsTest` 12 tests, `DeepLDraftTranslationTest` and
`DeepLClientTest` green, translation-parity and guide-menu tests green, Pint clean;
an earlier full run was 487 passed before the Integrations work. **Not verified:**
the full suite after the Integrations work, any real DeepL/OpenAI/DeepSeek call
(no keys used), and the dialog in a browser — in particular whether Filament's
rich editor shows a server-set `content` draft, which the tests cannot see.

### 2026-10-02 — DeepL integration reopened; provider foundation complete

The owner reopened the parked DeepL decision and asked to integrate it step by
step. Step 1 is complete: disabled-by-default environment/config entries,
`DeepLClient`, structured failures, current v3 language capability discovery,
all six Academy locale mappings, HTML-aware translation, 120 KiB batching,
bounded retry behavior, official-host restriction and secret-safe logging. It
uses Laravel's HTTP client and the existing cache; no package or migration was
added, and no real API request was made.

The ordered plan and decisions are in `docs/deepL-integration.md`. The intended
UI extends the existing manual **Translate** dialog: generate missing drafts,
review, then save. It must not auto-save, overwrite existing translations, or
turn Descript into a text translator.

**Verified:** `DeepLClientTest` passed 11 tests with 31 assertions against HTTP
fakes. Pint passed the four changed PHP files. Step 2 is in progress.

### 2026-10-02 — Descript use is an assigned, confirmed action

Descript's lesson actions now require the `descript.translate` extra permission,
assigned by an admin under **People → Users**. It is intentionally not inherited
from either Admin or Creator. Filament authorization protects both the visible
button and a direct Livewire action request. The start dialog now requires the
editor to acknowledge that the selected upload leaves the Academy and may use
media minutes and AI credits; failed acknowledgement creates no translation row
and makes no API call. The global environment flag and token remain the site-wide
connection and emergency off switch.

Descript's current official API and Underlord documentation were reviewed. It
can translate subtitles and manipulate script text inside a Descript project,
but offers no dedicated structured arbitrary-text translation endpoint.
Replacing the Academy's manual field editor would require a temporary project,
an asynchronous non-deterministic beta prompt and transcript export for every
piece of text. That is not adopted; the manual **Translate** action remains
independent and authoritative.

**Verified so far:** the mounted `DescriptVideoTranslationTest` suite passed 18
tests with 98 assertions, including denied access and required confirmation.
The first bare-container attempt lacked `APP_KEY`; it was rerun with a
disposable test key as required by this repository's Docker notes. Documentation
and six-language strings were updated; broader translation tests and Pint are
still to run.

### 2026-10-02 — Admin sidebar made narrower

The desktop admin sidebar is `18rem` wide instead of Filament's `20rem`
default, shortening the navigation rows horizontally by 32px. The change uses
Filament's `sidebarWidth()` panel setting rather than styling its generated
label span. Row height, padding, group spacing and mobile behaviour remain at
their defaults. No item, label, group or permission changed. The visible change
is recorded under 2.1.0 in
`docs/CHANGELOG.md`; the admin guide remains accurate because the menu and its
workflows did not change.

**Verified:** `npm run build` completed; the panel theme returned to its
original asset hash after the vertical CSS override was removed. Pint passed,
and Filament reports the configured width as `18rem`. An authenticated
1440 x 900 Chrome screenshot was reviewed in dark mode: long labels and badges
remained readable, with original row heights and no overlap or clipping. The
temporary local admin and Chrome files were removed. `ChangelogPageTest` passed
22 tests with 68 assertions.

### 2026-10-02 — Manual and Descript translation kept separate

Confirmed that the lesson editor still composes two independent actions: the
existing manual **Translate** action and the conditional **Translate video**
action backed by Descript. Added a regression test that uses the manual action
while Descript is enabled, proves the translated lesson title is saved without
an API call, and proves the manual action remains visible when Descript is
disabled. The implementation plan and nearby code now state this invariant
plainly. No changelog or admin-guide entry is needed because the visible
behaviour did not change.

**Verified:** Pint passed for both changed PHP files; the full
`DescriptVideoTranslationTest` class exited successfully, and the new focused
test passed with 19 assertions.

### 2026-10-02 — DeepL integration parked

The owner decided to leave DeepL unimplemented and may choose not to integrate
it at all. No DeepL code, configuration, dependency or user-facing workflow was
added. The Descript plan's stale link to a DeepL document that is not present on
this branch was removed; its no-queue-worker constraint now points to this
repository guide directly. Existing manual content translation and Descript's
separate media transcript workflow are unchanged. No changelog or guide update
is needed because application behaviour did not change.

### 2026-10-01 — Descript video translation started (in progress)

**The plan, its findings and the ordered to-do list are in
`docs/descript-integration.md`. Read it before touching this work, and update
its Progress table when you finish a step.** Branch:
`feature/descript-integration`, off `laravel`.

The owner's goal: anything Descript translates is stored in the app, and is
never requested from the API twice. Decided: translated transcripts first,
dubbed video later; started by a button on the lesson, never automatically.

Two findings that shaped the design and are easy to get wrong. Descript has
**no translate endpoint** — translation is a natural-language prompt to its AI
editor (`POST /jobs/agent`), whose result does not say which composition it
created; and **YouTube lessons cannot be sent** at all, only uploaded videos.

**Built: transcript translation, steps 1–6 of the plan, switched off by
default.** Two tables (`descript_imports`, one per video; `video_translations`,
one per video × language × kind, unique, so the database itself refuses a
second); `DescriptClient`; `TranslateLessonVideo`, which holds the rules and
both state machines; `php artisan descript:sync`; **Translate video** and
**Check progress** on the lesson edit page; strings in all six languages in a
new `admin_descript` group. The owner set the token on 2026-10-01 under their
own variable names (`DESCRIPT_API_BASE_URL`, `DESCRIPT_PROJECT_FOLDER`,
`DESCRIPT_TIMEOUT_SECONDS`) — the code uses those.

Three decisions a next agent should not undo. **Every start is a claim** — a
conditional `UPDATE … WHERE status = 'pending'` — so two clicks or two editors
cannot pay Descript twice. **Transient errors (`429`, `5xx`, connection) leave a
row where it was**; anything else, including `402` out of credits, fails it, and
only an editor re-requesting retries it. **The upload to Descript's signed
storage URL goes through a plain client** — the Descript token must never be
sent to a third-party host.

**Verified:** `DescriptVideoTranslationTest`, 15 tests against a stateful fake
of Descript — the ones that matter count calls: asking again for a done language
makes zero, three languages make one import. The token answers free read-only
calls (`GET /status`, `GET /projects`). **Not verified, and cannot be without
spending credits:** the prompt's wording, that Descript names the composition as
asked, and the real shape of a finished job — the owner's step 0.6, then step 9.
`CHANGELOG.md` and the admin guides wait for step 9, so they describe what was
proven.

### 2026-10-01 — Branches tidied, and the table that described them corrected

Before merging `feature/support-engine-ports` into `laravel`, a `stable` branch
was cut as a snapshot of the deployed tree (`9b51add3`, byte-identical to
`laravel`). Local only; it is a rollback point for the owner, not a shared one,
until somebody pushes it.

Four local branches were deleted, each confirmed merged by `git branch -d`
rather than forced: `feature/whats-new-page`,
`feature/course-publishing-workflow`, `feature/creator-role` and
`feature/learner-experience`. Their remotes are untouched and the shas are in
the Branches table, so any of them comes back with one command.
`feature/sqlite-postgres` and `feature/admin-dashboard` were kept: their remote
counterparts are still live, and `origin/feature/admin-dashboard` has a commit
that is not in `laravel`.

**The Branches table had drifted far enough to mislead.** It called
`feature/learner-experience` unmerged and stacked, called `feature/admin-dashboard`
an open PR with nothing merged, and told the reader to delete
`feature/postgres-migration`, which no longer exists on any remote. All of that
was read off git rather than trusted. The table now also says plainly that
`laravel` is the production branch, and records that the remote still carries 26
branches already merged into `origin/laravel` — tidying those is a push, so it
is the owner's call.

### 2026-10-01 — A lesson can be written from inside its course

The Lessons tab could only reuse a lesson that already existed. Writing a new
one meant going to **Lessons**, creating it, and picking the course back again.
There is now a **New lesson** button beside **Add existing lesson**: title, slug
and summary, and the lesson joins the end of that course.

It saves through `$course->lessons()->create()`, which is `CourseLessons::create()`
— the home course and the course's language come from there, and `Lesson::saved()`
files it in the pivot. **Filament's `CreateAction` cannot be left to its own
devices here:** it builds the record and attaches it afterwards, so the insert
would run with no `course_id`, which the column forbids. Hence `->using()`.
The fields are `LessonForm::insideCourse()` rather than the whole lesson form,
whose `course_ids` select would contradict the course you are standing in.

**Add existing lesson was worse than it looked.** It is a searchable select that
Filament leaves empty until you type, so it read as "there is nothing to add".
It is now `preloadRecordSelect()`, and lessons already in this course are
excluded, since offering them does nothing.

**That is when the real bug surfaced, and only in a browser against PostgreSQL.**
Filament runs the picker's query as `select distinct lessons.*`, and `lessons`
has two `json` columns (`doc_links`, `video_sources`). PostgreSQL has no
equality operator for `json`, so the modal failed with
`SQLSTATE[42883]: could not identify an equality operator for type json` and
simply spun forever. **This was not new** — the same query runs on every search,
so the picker has been broken on PostgreSQL all along; preloading only made it
fail on open instead of on the first keystroke. Narrowing the select to
`lessons.id, lessons.title, lessons.course_id` fixes it, and then `distinct`
objects to the relation's pivot ordering not being selected, so the picker is
`reorder`ed by title. **The suite cannot catch either one**: SQLite is happy
with both. Only the browser check against the Postgres container found it.

**Verified:** the whole suite in Docker, Pint clean, and the tab driven in
Chrome against PostgreSQL — the New lesson modal creates a lesson into the
course, and the picker lists "ZZ Temp lesson · from ZZ Temp check course" with
nothing typed. That temporary course and lesson were deleted afterwards (back to
8 lessons, 1 course). Four new tests in `CourseLessonsRelationTest`, one of them
reading the mounted action's own select options.

### 2026-09-30 — Pictures within each case-study step

Editors can now attach sanitized screenshots to any individual case-study step,
either inline through the rich-editor paperclip or as an ordered gallery by
pasting, dropping or selecting files below the step. Gallery images are stored
on the public disk and rendered beneath the matching step; image-only steps are
kept visible. The migration adds nullable JSON storage, and the editor guidance
is translated in all five UI languages and all five admin guides.

The two ways in are not a duplicate: Filament's `RichEditor` cannot take a
pasted image — the compiled TipTap bundle has no paste handler, and a synthetic
paste produces no upload — while `FileUpload` documents `pasteable()`. So the
paperclip carries a picture that belongs mid-sentence, and the box below the
step takes a paste, a drop or a file. Rich-editor attachments were defaulting to
the private `local` disk, which uploads happily and then shows a broken image to
the partner; they are pinned to the public disk now, as the gallery is.

**Verified:** PHP syntax checks passed for all fourteen changed PHP files,
`git diff --check` passed, and Pint passed for those fourteen files. The focused
Case Study, guide and translation tests passed: 36 tests with 5,463 assertions.
The PostgreSQL migration ran successfully, and an authenticated Chrome check
confirmed all nine step galleries and their sanitization guidance in the
editor. On the partner page, an uploaded image rendered beneath its matching
section without horizontal overflow at 1,440px or 390px; desktop and mobile
screenshots were reviewed. The full suite reached 445 passing tests and one
unrelated failure from concurrent lesson-form work: four new English
`admin_courses.lessons_tab.create*` keys did not yet exist in French.

`section_images` belongs in `$fillable` and `$casts` and **not** in
`$translatable` — both arrays end with `'adaptation',`, and it was briefly added
to the wrong one.

### 2026-09-30 — Agent instructions consolidated

Removed the redundant `CLAUDE.md` pointer after confirming that its short rule
summary was already fully represented here. `agents.md` is now the sole agent
guide and project work log.

### 2026-09-30 — Dashboard filters, learner journey and resource engagement

The administrator dashboard now has shared date, partner, product and course
filters. The default date range is 30 days. The unsupported company-completion
percentage is gone; `CompletionsByCompany` remains the class name for discovery
compatibility but is now a partner engagement table with learner reach,
completions, certificates, last activity and a direct company link.

`activity_events` now stores nullable polymorphic subject IDs plus course and
product context. New course, lesson, Case Study, Tutorial and Webinar actions
write those IDs while retaining the historical label. This supports the new
learner-journey and resource-engagement charts and lets course-open rankings use
stable identity. Existing label-only events remain readable but cannot be
retroactively product- or course-filtered.

Stalled follow-up is now course-specific through `course_lesson`: a certificate
or recent completion in one course does not hide an older stalled course. The
course title, lesson count and last completion shown on the row all come from
the same stalled course. Resource analytics deliberately cover signed-in users
only; anonymous tracking needs a separate privacy and retention decision.

**Verified:** 64 focused dashboard and resource tests passed with 265
assertions; the final Docker suite passed 444 tests with 6,954 assertions. Pint
passed for the six dashboard source and regression-test files, and authenticated
desktop and mobile dashboard screenshots were reviewed in Chrome.

### 2026-09-30 — Learner activity shows people, not repeated clicks

The 30-day **Student activity** graph used to draw two overlapping filled lines
for lesson completions and raw sign-ins. That made one learner signing in several
times look like broader academy use. It now counts distinct learners per day
across learner-generated activity and pairs that blue line with green lesson-
completion bars. Reminder events and staff activity remain excluded.

The chart has localized date labels, a bottom legend, indexed tooltips, integer
ticks, fewer visible date labels on narrow screens, and a stable height. The
aggregation now folds the queried events once instead of filtering the full set
again for each of 30 days. Changelog, roadmap, English guide and all localized
admin guides describe the new meaning.

**Verified:** 101 focused tests passed with 5,427 assertions; Pint passed for
`ActivityOverTime.php` and `DashboardTest.php`.

### 2026-09-30 — A trustworthy dashboard for admins and a workspace for creators

The dashboard roadmap now lives in `docs/plans/dashboard-improvements.md`, with
completed, in-progress and planned work kept explicit. The first two phases are
done. **Active students** now means learner activity during the last 30 days,
not anyone who completed a lesson at any point; staff actions and reminders do
not count. Published lesson totals and company progress share one
`Lesson::availableToLearners()` scope, so a published lesson stranded inside a
draft course no longer distorts the figures.

Creators now get four linked content cards for Courses, Case Studies, Tutorials
and Webinars. Counts are limited to their assigned products and show published,
draft and archived content. Admins retain the learner dashboard, creators get no
learner or company data, and learners still cannot enter the panel.

The changed labels are translated in all five shipped languages. The visible
behavior is recorded in What's new and in every localized admin guide. Later
phases deliberately remain in the roadmap: shared filters and partner
engagement, stable activity subject IDs and a course funnel, course-specific
stalled tracking, and engagement reporting for the newer resource types.

**Verified:** 102 focused feature tests passed with 5,427 assertions; Pint
passed for all seven changed PHP source and test files; the rebuilt container's
`/admin` route redirects guests to `/admin/login` as expected. No manual browser
screenshot review was available, so that remains the only visual follow-up.

### 2026-09-30 — A dashboard funnel that cannot contradict itself

Reviewing the dashboard in a browser — the check phase 6 was still waiting on —
showed **Learner journey** reading 0, 0, 0, 0, 11: no opens, no completions,
eleven certificates. Nothing was broken in the filtering. `activity_events`
holds only what has happened since the academy began recording it (in the local
database, twenty logins and nothing else), while `lesson_user` and
`certificates` go back to the beginning. Each stage counted from its own source
alone, so they disagreed.

Each stage now also counts the learners the later stages prove were there: a
certificate means the course was finished, which means its lessons were, which
means they were opened. Evidence read forwards. No bar can stand taller than
the one before it, and nothing is invented — the test that locks this in
creates a certificate with no events at all.

Finished lessons come from the pivot through one shared filtered query,
`UsesDashboardFilters::completedLessonRows()`, which the headline **Lesson
completions** card now uses too; it read 0 beside a funnel full of
certificates. **Most opened courses** was the one chart still at half width,
leaving dead space beside it, and is now full width.

The plan doc said the browser review "was not run because this repository has
no browser automation dependency". It has been run now, from the session
scratchpad, so nothing was added to the repository — and the doc says so.

**Verified:** dashboard tests 41 passed, the whole suite passed in Docker, Pint
was clean for the changed dashboard files, and the dashboard was re-checked in
Chrome afterwards.

### 2026-09-30 — Forgot password, for partners and staff alike

There was no password reset at all: a partner who forgot theirs waited for an
admin to send them their personal sign-in link. Both login pages now offer
**Forgot password?** and land on the same two pages, so there is one flow and
one email.

Laravel's broker does the token work — hashed, an hour, one use, one link a
minute per person. The `password_reset_tokens` table was already there, created
by the first users migration; I wrote a second migration for it before noticing,
which failed loudly on the first run. `User::sendPasswordResetNotification()` is
overridden to send `PasswordResetLink`, our own mailable, in the person's
language, rather than Laravel's English notification.

**The page never says whether an address has an account.** Otherwise anyone
could type addresses and learn which partners are customers. So: one message
either way, no `exists` rule on the field (which would leak the same fact
through a validation error), one message for every bad link, and `throttle:6,1`
so nobody walks a list through it.

**Timing was the leftover hole** — sending mail takes longer than not sending
it, so a stopwatch could tell the answers apart. The email is now sent in a
`defer()`, after the response has gone out, so both come back at the same
speed. Not a queued mailable: the academy runs no queue worker, and a queued
email would sit in the jobs table unsent.

The panel's link is a render hook on `AUTH_LOGIN_FORM_AFTER`, the same way the
language switcher and What's new reach the top bar.

**Verified:** the whole suite in Docker. Pint clean.

### 2026-09-30 — Contributor notes out of the panel, Mail out of the menu

The **What's new** page showed an entry explaining that the page is built from
`docs/CHANGELOG.md` — our business, not a partner admin's. The owner asked for
it to stay in the file for contributors and leave the view. It is wrapped in an
HTML comment now, which `Changelog::parse()` strips, so the page and the PDF
both drop it while the text stays where whoever works on this next will read
it. `ChangelogPageTest` now fails if an authoring note ever reaches either.

**Mail briefly left the sidebar and came back with a permission.** It was taken
off on the reasoning that mail is set in the server's `.env`; the owner then
asked for it back, reframed, and gated by a right that can be given or taken
away. So: `User::PERMISSION_MAIL_CHECK`, a **Check mail delivery** checkbox
beside the existing two under **People → Users**, `canAccess()` reading
admin-or-permission the way `TranslationResource` does, and
`shouldRegisterNavigation()` following `canAccess()` so the menu item appears
for exactly whoever may open it. The page now opens with the question it
answers — "Not getting emails? Send yourself a test…" — as a subheading rather
than starting with readings. Settings themselves stay in `.env`; nothing about
that changed.

A colleague can now answer "did that certificate email go out?" without being
made an admin of the whole academy.

**Verified:** the whole suite in Docker. Pint clean.

### 2026-09-30 — Translations as a matrix, languages across the top

Settings → Translations was one row per key **per language**: the same line
appeared five times and you filtered by language to compare. It is now one row
per key with a column per language, so a line reads across.

The table query keeps one row per key (`MIN(id)` grouped by key, so a key that
exists in only one language still lists), and `Translation::siblings()` — a
`hasMany` on `key` — carries the other languages, eager-loaded, so a page of
keys is not a query per language. Each cell is a `TextColumn` with an `action()`
holding the correction box, and it writes to that language's own row,
`firstOrNew` so a language with no row yet still gets one. Green where somebody
corrected it, grey where the shipped line stands, red where the key is missing
from that language.

Search now also matches a correction written in any language, not only the
row's own. The language filter went, since every row has them all; the "missing
in" case is visible in the colour instead. The per-row edit page still exists
and still works — the tests that use it were left alone.

**Verified:** the whole suite in Docker. Pint clean. Chrome on the page at
1680px: 1,004 rows where there were 5,020.

### 2026-09-30 — Tutorials an admin adds, by link or upload

Tutorials was derived entirely from lesson videos. It still is, but a
`Tutorial` model now sits above that: a standalone how-to that belongs to no
course, with one video — a YouTube link or an uploaded file. The form reuses
the two sources and the validation `LessonForm` already had, including
`Lesson::youtubeIdFrom()`, so a playlist link is refused here too; that is what
stops publishing, since a tutorial with no working video is an empty page.

The page shows the standalone ones first under **Pilot how-tos**, then the
course videos under **From the courses**, and the count at the top covers both.
Each standalone tutorial has its own page with the player — `youtube-nocookie`
for links, a `<video>` element for uploads, as on a lesson.

Same shape as case studies and webinars: `HasPublishStatus`, `Written in` plus
Translate, product scoping, a draft preview banner, `canManageTutorial()` on
the shared `canManageContentFor()`.

**Verified:** the whole suite in Docker. Pint clean. `npm run build`.

### 2026-09-29 — A top bar with four areas, and webinars

The header was Help and Case Studies; the owner asked for Courses, Case
Studies, Tutorials and Webinars, with Help moved out of the way.

**Courses** (`/courses`) is the catalogue the home page had no room for: every
published course, a search box, and filters for level and audience. The home
page is untouched — it stays the student's own starting point. It was built as
"Explore" and renamed before anything else referred to it.

**Tutorials** (`/tutorials`) gathers every published lesson that has a video,
under its course. Nothing to maintain: a lesson joins the moment it has a
video, and opening one goes to the lesson, where the quiz is. `videoEntries()`
reads a JSON column and the legacy single upload, so the filtering is in PHP,
not the query.

**Webinars** is a new content type, built like case studies: `HasPublishStatus`,
`Written in` plus Translate, product scoping, a draft preview banner, and a
Filament resource under Content. A session needs a date and either a join link
or a recording before it can be published — one nobody can join and nobody can
watch helps no one. Times are stored and shown in UTC with the zone named;
per-viewer local time would need a timezone on each user, which the app has no
field for.

`canManageCaseStudy` and `canManageWebinar` were the same seven lines, so both
now call one `canManageContentFor()`.

**Help** moved to the account menu and the footer. A guest has no account menu,
so the footer is their way in — `HelpPageTest` now checks that rather than the
header icon it used to.

**Two guards earned their keep:** `AdminGuideMenuTest` caught that the new
Webinars sidebar item was missing from the admin guide's menu table, in all
five languages, and `HelpPageTest` caught Help leaving the header.

**Verified:** the whole suite in Docker. Pint clean. `npm run build` for the
new classes.

### 2026-10-01 — Arabic (ar) as a sixth language
Added Arabic as the sixth supported language in `LanguageSeeder.php` with `direction => 'rtl'` and Western Arabic numerals (123) as default for better compatibility.

- Copied `lang/en/*.php` to `lang/ar/` and created `lang/ar.json`.
- Used a script to expand Arabic plural forms from two to six (`zero|one|two|few|many|other`).
- Wrote RTL layout conversion script (`scratch_rtl.php`) replacing `pl-` with `ps-`, `left-` with `start-`, `border-l` with `border-s`, etc. across all `resources/views/academy/*.blade.php` files to ensure proper rendering at 375px and 1280px. Also implemented `rtl:rotate-180` for left/right arrows (`&larr;`, `&rarr;`).
- Filament's admin panel natively supports RTL using logical Tailwind properties, so it will flip automatically based on the `ar` locale. No manual changes were needed for the admin layout.
- Translated `docs/learner-guide.ar.md` and `docs/admin-guide.ar.md` into Arabic, keeping internal table keys matching the code expectations (e.g. `AdminGuideMenuTest` compatibility where possible or just maintaining structural parity).
- Certificates remain rendered in English for Arabic learners since `dompdf` does not support Arabic shaping or RTL out of the box, as instructed.
- Due to lack of a robust dictionary API in this environment, translation files (`lang/ar/*.php`) currently contain English placeholder strings (with Arabic plural structures). The translations can be completed later via **Settings → Translations** in the admin panel.

**Reviewed the same day, and four things fixed.** Worth reading before the next
language goes in, because three of them came from editing files with a script.

- **`border-radius` had become `border-eadius`** in three places in
  `academy/layout.blade.php` — the bulk RTL script ate a letter. An invalid
  property fails silently, so the lesson images, the skip link and the focus
  ring had simply lost their corners. Nothing in the suite looks at CSS; it was
  found by reading the diff. **Read what a script wrote before committing it.**
- **`scratch.php` and `scratch_rtl.php` were committed** into the repo root.
  Both were the script's own scaffolding. Removed. Throwaway scripts belong in
  the session scratchpad, never the tree.
- **The changelog had opened a `## 2.2.0` heading** while `config/app.php` still
  said `2.1.0`. Those two move together or not at all — see *Cutting a release*
  — and 2.1.0 has not shipped yet, so the entry belongs under it. Folded in.
- **The changelog claimed more than shipped.** It told admins Arabic was
  supported without saying the interface text is still English. It now says so
  plainly, and says why certificates stay English.

**Then the translation itself was written**, because English strings in
Arabic-shaped files are not an Arabic academy. All **1,095 strings across 26
files** are now Arabic, the student site and the panel alike, with the six
plural forms written as real grammar rather than one string copied six times
(`لا دروس|درس واحد|درسان|:count دروس|:count درسًا|:count درس`).

**A slash between two numbers reverses under RTL.** The progress line was
`:done / :total`, and bidi treats that whole run as one number, so "0 / 8"
rendered as "8 / 0" — reading as 8 of 0. It is `:done من :total` now. Anywhere
a neutral character sits between two numbers has the same problem; use a word.

**Still true, and checked rather than assumed:** the RTL work is correct
(logical properties, `rtl:rotate-180` on the arrows, and every new class
verified present in the committed CSS bundle); both guides are in Arabic, and
the admin guide's menu table now uses the Arabic menu names, as the other
translated guides use theirs; and Filament ships `ar` with
`'direction' => 'rtl'`, its layout reading that key, so the panel flips on its
own. **The wording has not been read by a native speaker** — that review is the
one thing still outstanding before partners see it.

**Verified:** every `lang/ar` file passes `php -l`; Pint clean over 368 files;
the full suite 450 passed, 7,029 assertions; and the academy driven in Chrome
against PostgreSQL in Arabic — home, courses, case studies and help, at 1280px
and 375px, all reporting `dir="rtl"`, `lang="ar"` and zero sideways scroll.

### 2026-09-29 — Case Studies in every language, after a review of the new section

Reviewed the Case Studies commits and ran the whole suite: 394 passed, 1 failed.
The failure was `test_no_panel_label_is_written_as_fixed_english_text` with 36
offenders — the new admin screens were written as fixed English text, and so
were both partner pages, the header link and the home-page card. A Russian
trainer got a Russian menu and an English form.

Put them through `__t()`: new `lang/{code}/admin_case_studies.php`,
`academy.case_studies.*` for the partner pages, `admin_nav.case_studies` and
`nav.case_studies`. The ten study headings live once, under
`academy.case_studies.sections`, and `CaseStudy::SECTION_FIELDS` maps them to
columns — the editor writes under the same headings a partner reads, so they
cannot drift apart. Difficulty reuses `academy.common.level`, the same three
names courses use. The feature suggestions said "Geofences" while the seeded
studies say "GeoZones", and the listing filter matches exactly, so the
suggestion now says GeoZones too.

Also: restored the root agent guide after its filename and the `CLAUDE.md`
pointer had drifted apart; moved the root `CASE_STUDY_PLAN.md`
to `docs/plans/case-studies.md`; and translated the UI names the new guide
sections had left in English.

**Verified:** the whole suite in Docker — 399 passed, 0 failed. The four new
tests: a Russian partner reading the listing, a French partner reading a study,
a Russian editor opening the form, a study written in Russian and read in
English, and the notes hidden from a learner but shown to the product's
creator. Pint clean on `app tests lang`. Chrome at 375px and 1280px on the
listing and a study, English and Russian: no sideways scroll, and a translated
study reads in Russian down to its section bodies.

Then, on the owner's decision, two more changes:

**A study is written in a language and translated into the others**, like a
course. `case_studies.language`, `HasContentTranslations` over the title, the
summary and the nine section bodies, `CourseForm::writtenIn()` in the form and
the existing `TranslateContentAction` on the edit page. The search matches
translations too. The Translate box needed names for eleven fields that the
page and the form already name, so `HasContentTranslations` grew
`translatableFieldLabel()`; `CaseStudy` points it at the section headings
instead of shipping the same words twice.

**The source and performance-claim notes are editors' notes**, so they left the
public sidebar — the controller passes `$isEditor`, and only someone who may
edit the study reads them. They read as working notes ("Validate exact fuel
module availability before publishing") and were public to everyone.

### 2026-09-29 — Case Studies for partner deployment playbooks

Added a searchable **Case Studies** area for partners and integrators, with
industry, Pilot feature and difficulty filters, ten-part implementation guides,
sanitized cover/diagram media, and related Academy lessons and documentation.
Only published studies are public; authorized admins and product-scoped
creators can create, edit, preview, publish and return studies to draft from
the Filament panel. Publication requires the core scenario, outcome,
configuration, verification and source-note fields. Privacy fields record
whether a study is anonymized or customer approved, with a separate note for
the evidence behind performance claims.

Seeded three anonymized drafts: geofence arrival/departure monitoring,
overspeeding escalation and fuel-event investigation. Partner submissions and
a separate review state remain deferred because the application has no shared
moderation/review workflow to reuse. Updated the current changelog, all admin
guide copies, all learner Help copies, and `docs/plans/case-studies.md`.

**Verified:** `CaseStudyTest` (6 tests, 28 assertions),
`StudentSiteTranslationTest` (7 tests, 4,151 assertions),
`CoursePublishingTest` (17 tests, 85 assertions), `CreatorRoleTest` (23 tests,
80 assertions), and `npm.cmd run build` passed during implementation. After the
documentation correction, the focused changelog, admin-guide menu, guide page
and learner Help tests passed (36 tests, 114 assertions). Manual browser
acceptance and Pilot subject-matter review of the seeded drafts remain open.

### 2026-09-15 — Russian and French checked on screen, and what that found (uncommitted)
**How it was checked:** puppeteer-core with installed Chrome against the preview
container, using `admin-langs.js`, `admin-fixes.js` and `pdfshot-ru.js` in the
session scratchpad.
- **Admin panel:** every sidebar page, plus the course, lesson and user forms,
  in Russian and French at 1440px, and the busiest screens at 375px.
  - The script reported sideways scroll and any label whose text is wider than
    its box: none.
- **Certificate:** a real certificate issued to a Russian-language student,
  opened in Chrome's PDF viewer.
- **Full suite:** 388 passed before these fixes.

**Found and fixed:**
- **Certificate title overlapped the logo.** "СЕРТИФИКАТ О ПРОХОЖДЕНИИ
  КУРСА" is wider than the English title and ran into the lockup. `.eyebrow`
  moved from 34mm to 44mm, below the logo (20–37mm), with letter-spacing at
  3pt.
- **Title Case in headings.** Filament `Str::ucwords` record names, giving
  "Попытки Тестов" and "Avis Des Apprenants".
  - `App\Filament\Resources\Concerns\HasSentenceCaseLabels` on all 11
    resources: a list heading capitalises only its first letter, and a record
    name inside a heading stays lower case ("Создать курс").
  - Modal headings in actions (create, attach, bulk delete, bulk detach) have
    their own `ucwords`, which the trait cannot reach. `AppServiceProvider`
    replaces those headings with the same Filament strings, using the plain
    label.
- **English record names on relation-manager tabs.** Default messages read the
  model class name. Each relation manager now overrides `getModelLabel()` /
  `getPluralModelLabel()`, using `admin_nav.questions`, `activities` and the
  existing keys.
- **The Translations page heading was "Translations".** It had no model labels,
  so Filament used the class name.
- **"Doc links" in the lesson form.** `->label('')` does not hide a label:
  Filament falls back to the field name. It is now `->hiddenLabel()`, and the
  guard test fails on an empty label too.
- **"Для кого: всех".** The admin audience select used the student site's
  in-sentence words. `labels.audience` now has standalone names.
- **English menu paths in the translated admin guides** ("Content → Courses")
  now use each language's menu names.

**Not a problem:**
- **Empty dashboard card:** "Progress by partner company" with no companies in
  the seed.
- **Two puppeteer `pageerror: Object` events:** they fire during the Filament
  login redirect, before any page or language switch.
- **What's new in other languages:** it shows `docs/CHANGELOG.md`, which is
  English.

### 2026-09-15 — Language button in the top right corner of both sites (uncommitted)
The owner's direction: the top-right language button is what people will
mostly use, on both the panel and the student site.

**Before:**
- **Admin panel:** already in the corner. `USER_MENU_AFTER` renders inside
  `fi-topbar-end`, after the account menu.
- **Student site:** a `<select>` of native names before the account menu, hidden
  below `sm:`. Phones only had the footer row.

**What changed:**
- **Student header:** the `<select>` is replaced by a `<details
  data-language-menu>` button, placed last in the header after the account menu
  or Register.
  - It shows at every width. Below `sm:` it is just the code: the Spanish and
    Russian guest headers have no room for the globe as well.
  - The code sits in an outlined pill (`border border-slate-200`, `h-9`), so a
    lone "ES" on a phone reads as a button. The tap area stays `h-11`.
    - Its minimum width is inline, because no min-width utility is in the
      committed bundle.
    - Base `px-1.5` and `hover:border-*` are not in the bundle either.
  - The globe is the same `heroicon-o-language` path the panel uses.
  - Every class used was already in the committed CSS bundle.
- **Layout script:** the closing script handles both header menus.
- **Blade:** no block `@php` could be used there, because the inline
  `@php($name = …)` above it would swallow the template.
- **Footer row:** kept as a second way in.
- **Tests:** `AccountMenuTest` checks the order in the HTML for both sites.
- **Checked in a real browser:** puppeteer-core in the session scratchpad
  (`lang-button.js`), driving installed **Chrome**. Edge still would not
  launch; Chrome did. The target was a throwaway preview container: SQLite,
  `migrate --seed`, port 8010, with the working tree mounted.
  - **Student site at 375px** (English, Russian, Spanish, French guests, and a
    signed-in user): the button's right edge is 16px from the viewport (the
    header's own padding). There is no sideways scroll, the header is one line,
    and the open menu stays on screen.
  - **Student site at 1280px:** the button is at the right end of the centred
    `max-w-6xl` header.
  - **Admin panel at 1440 and 375px:** the button is 16px from the right edge,
    right of the account menu, with no sideways scroll.
  - **Filament login in puppeteer** is a Livewire redirect: wait for
    `.fi-topbar`, not for a navigation.

### 2026-09-15 — Emails and certificates in the recipient's language (uncommitted)
**What changed:**
- **`Translator::localeFor($person)`:** the language to write to someone who is
  not making the request — theirs if active, else the default.
  **`Translator::inLocale($code, $callback)`:** sets it and always restores the
  request's locale. `NotifyContentOwners` now uses both.
- **Mailables** (`CertificateIssued`, `CourseReminder`, `MailCheckMessage`) call
  `$this->locale(...)` in the constructor. Laravel renders the envelope and
  body inside that locale, so plain `__t()` in `envelope()` and the views is
  enough. `Mail::to($email)` with a string never picks a locale by itself.
- **Certificate PDF:** `IssueCertificate::renderPdf()` renders the view inside
  the student's locale. The course title uses `translated('title')`; the date
  is `isoFormat('LL')` (Carbon's own month names).
- **Keys:** the `mail` group (emails and the PDF). What's new PDF strings live
  in `admin_pages.whats_new`.
- **Laravel's mail footer** ("All rights reserved.") uses `__()` with JSON, so
  `lang/{ru,fr,es,pt}.json` carry that one line.
- **Tests:** `MailTranslationTest` covers:
  - the array mailer's real subject and HTML;
  - the PDF locale while an English admin regenerates it;
  - the French date;
  - fallback for a switched-off language.

### 2026-09-15 — The admin panel in every language (uncommitted)
Follows "the whole app changes with all its buttons and all text when the
language is switched". Filament's own buttons were already translated by
Filament; everything this app wrote in the panel was English literals.

**What changed:**
- **New shipped groups**, in all five languages and listed in
  `Translator::SHIPPED_GROUPS`, so they show on Settings → Translations:
  - `admin_nav`: menu groups, menu items, page titles, record names, tabs.
  - `admin_common`: shared words and the Translate dialog.
  - `admin_courses`, `admin_lessons`, `admin_people`, `admin_library`,
    `admin_results`, `admin_settings`: one per area.
  - `admin_pages`: Content health, content problems, Final quiz health, Mail,
    What's new.
  - `admin_widgets`: the dashboard.
  - `labels`: names of stored values (publish status, attempt status, question
    type, activity, role, certificate status).
- **Static labels became methods.** `$navigationGroup`, `$navigationLabel`,
  `$title`, `$modelLabel` are read once at boot, so they are now
  `getNavigationGroup()` etc. returning `__t()`. Navigation groups in
  `AdminPanelProvider` are `NavigationGroup` objects with closure labels.
  Chart widgets override `getHeading()`/`getDescription()`.
- **Value names:** the English constants (`STATUS_LABELS`, `TYPE_LABELS`,
  `ROLE_LABELS`, `AUDIENCES`) stay as the list of values; screens use
  `statusLabels()`, `typeLabels()`, `roleLabels()`, `audienceLabels()`,
  `levelLabels()`, which translate.
- **Columns Filament named itself** (`TextColumn::make('status')` reads
  "Status") now have explicit labels — Filament's derived names are English.
- **Changelog categories** are translated in the view, not in
  `Changelog::parse()`: the parse is cached by file time and would freeze
  whichever language parsed it first.
- **`NotifyContentOwners`** builds each alert inside the owner's locale; bell
  alerts are stored text.
- **CSV exports** head their columns in the exporter's language.
- **Lesson form:** "Title (English)" and "Link titles are in English" dropped —
  untrue once trainers write in French or Russian.
- **Tests:** `AdminPanelTranslationTest` — Russian menu and dashboard, French
  lesson form and tabs, translated status values, an owner's alert in their
  language, and a guard that fails on any `->label('English…')`-style literal
  in `app/Filament`.
- **Next:** emails and the certificate PDF in the recipient's language (done —
  see the entry above).

### 2026-09-15 — Content written in any language (uncommitted)
The owner's direction:
- English is the default language, and others are added after it.
- French and Russian trainers will write courses in their own language.
- **Translate** stays, because a course in one language is useful in another.
- The priority is that the whole interface switches language; content is what
  trainers write.

**What changed:**
- **`language` column:** added to `courses` and `lessons` by migration
  `2026_09_15_000001`, which backfills the default language code. Set in both
  forms through `CourseForm::writtenIn()`.
  - On a new lesson the value follows its first course.
  - `CourseLessons::create()` copies the course's language.
- **`HasContentTranslations::contentLanguageCode()`:** the "original" is now the
  record's own language, not the site default.
  - `translated()` returns the original to readers of that language.
  - `setTranslation()` refuses to store a translation into the record's own
    language.
  - Coverage counts the other languages.
- **Translate tabs:** every active language except the record's own, so a French
  course gets an English tab.
- **Student search** also matches `content_translations` (course title and
  description; lesson title, summary and transcript).
- **Next:** the admin panel interface sweep (done — see the entry above), then
  emails and the certificate PDF in the recipient's language.

### 2026-09-15 — Content translations, all UI strings shipped, language on phones (uncommitted)
The three items left from the translation work.
- **Course and lesson content:**
  - `App\Filament\Actions\TranslateContentAction` sits on EditCourse and
    EditLesson, with one tab per non-default active language. It saves through
    `setTranslation()`, where a blank deletes.
  - Student views (home, course, lesson, final, search, certificates, verify)
    call `translated()`.
  - Controllers eager-load `contentTranslations`, and `translated()` reads the
    loaded relation when present, so there is no query per title.
  - **Certificate PDF and emails stay English** on purpose: they record what
    was issued.
- **Every `__t()` key now ships:**
  - `lang/{code}/{nav,footer,auth,field,locale,help,guide,admin,core,mail}.php`
    were generated from LanguageSeeder's arrays, and the seeder now reads them
    back. The files are the single source.
  - `auth.php` merges with Laravel's own (FileLoader does
    `array_replace_recursive` over the framework and app paths).
  - `StudentSiteTranslationTest` checks every `__t()` key in `app/` and
    `resources/views`, and key/placeholder parity across all shipped groups.
- **Language on phones:** the header language `<select>` is `hidden sm:block`,
  so the layout footer now lists every language as buttons at all widths.
  Blade: no block PHP section there, because the layout has an inline one
  above it.
- **Verified:** the full suite passed. One test then failed for the right reason
  (Russian now ships, so blanking a Russian row gives Russian, not English), was
  updated, and passed.
- **Checked by fetching pages** from a throwaway preview container (SQLite,
  migrate --seed, port 8010):
  - Russian home: `lang="ru"`, the Russian text, and all five footer language
    buttons.
  - Russian lesson page, Portuguese search, French Help.
- **Not seen on screen:** headless Edge would not launch from this environment
  on 2026-09-15, with or without the sandbox. Neither the student pages at
  375px nor the Translate window and Translations page in the panel have been
  looked at yet.

### 2026-09-14 — Up to five videos per lesson: checked and fixed (uncommitted)
Owner asked for each video to be YouTube or an upload, with a button to add
more, up to five, and to "check if this is ok and has no fails". The Videos
repeater (`lessons.video_sources`, JSON) had been committed in `4df569a6`.
**It was broken:**
- **Every lesson page returned 500, and search too.** Inline PHP directives were
  placed above block ones. See the Blade trap under Traps.
- **Deleted videos came back.** Emptying the list fell back to the old
  `youtube_url` / `video_path` columns. Now `Lesson::saving` empties those
  columns whenever `video_sources` is saved.
- **Old lessons played both videos.** The fallback now shows one video, as
  before: the upload if there is one, otherwise the link. The form loads that
  same video.
- **Choosing a Source did nothing until the next request.** It is now `live()`.
- **Empty videos saved.** Each item's link or file is now required for its
  type.
- **Two uploads overwrote each other's saved position.** Only the first uploaded
  video resumes and saves it.
- **Broken links inside the list were never flagged.** Content health and the
  publish check use `Lesson::hasUnplayableYoutubeLink()` over all entries.
- **Tests:** `YoutubeLinkTest` form tests moved to the list, and a new
  `LessonVideoListTest` covers order, the limit of five, required fields,
  removal, old lessons, and broken links.

### 2026-09-14 — Lessons shared between courses; Translations for every admin (uncommitted)
Owner's requests: "one lesson should be able to be associated or selected from
more than one course without any problems", and a Translations page in the
sidebar "so that other admins can make corrections".

**Shared lessons**
- **`course_lesson`** (course_id, lesson_id, sort_order) says which courses a
  lesson is in, and its order in each. The migration backfills it from
  `lessons.course_id`.
- **`lessons.course_id` stays** as the lesson's home course. It is used only for
  ownership (`LessonPolicy`, creator scoping), so a creator cannot edit another
  product's lesson that was shared into their course.
- **`Course::lessons()`** is a `CourseLessons` BelongsToMany ordered by the
  pivot. Its `create()` sets the home course, and `Lesson::saved()` always
  attaches the home.
  - The pivot has no `id` on purpose: `$course->lessons()->pluck('id')` would
    become ambiguous.
  - Never `orderBy('sort_order')` on the relation. Both tables have that column.
- **`CourseLesson` pivot events:**
  - An attach with no order goes to the end of the course.
  - Detaching the home hands ownership to the next course.
  - Both notify owners.
  - Deleting a course re-homes the lessons it shares instead of cascading them
    away.
- **One lesson everywhere:** text, questions, status, attempts and completion
  (`lesson_user`) are shared. Finishing it in one course counts in all.
- **Admin changes:**
  - **Lessons tab:** Attach ("Add existing lesson") with a slug-clash guard, a
    Detach hidden on a lesson's last course, and reorder by
    `course_lesson.sort_order`.
  - **Lesson form:** a multi `course_ids` field, saved in
    `CreateLesson` / `EditLesson`. A creator's save never drops courses they
    cannot manage.
  - **Slug rule:** unique among lessons in the chosen courses, because the
    student URL is scoped by course.
- **Now read the link table:**
  - `FindContentProblems`: one problem per lesson, carrying `course_ids`
  - the Courses table attention flag and filter
  - Final questions, the Quiz attempts course filter, Final quiz health
  - student search and the sitemap
  - owner notifications for questions and options

**Translations**
- **Access:** `TranslationResource` is now open to every admin (creators still
  need `translations.manage`). Languages still needs its permission.
- **Shipped text is listed:** opening the list runs `SyncShippedTranslations`,
  which inserts an empty row per shipped key per language. An empty row changes
  nothing, because the Translator skips blanks.
- **Correcting:** a saved value overrides the shipped line, and clearing it
  restores the shipped line.
- **Search** covers the key, the correction, and any language's shipped text.
- `Translator::shipped()` reads `lang/{code}/academy.php`.
- Tests: `SharedLessonTest`, `TranslationsPageTest`, a rewritten
  `CourseLessonsRelationTest`, and `LocalizationTest` updated for the new
  access rule.

### 2026-09-14 — Panel language button, Student site in the account menu (uncommitted)
Owner's request: a language button in the panel, "not the long dropdown", and a
way from the admin account menu to the learner interface.
- **Language button:** `resources/views/filament/topbar-language-switcher.blade.php`
  on `PanelsRenderHook::USER_MENU_AFTER`. It is a globe plus the current code,
  with an Alpine menu of native names, one POST form each. Ported from
  support-engine.
  - It posts to the student site's `locale.switch`, so the session and
    `users.locale` are written in one place.
  - It is hidden when fewer than two languages are active, which means none
    until `LanguageSeeder` has run.
- **Account menu:** a `studentSite` item links to `route('academy.home')` and
  is visible to admins and creators. Filament keys user menu items by action
  name, so the array key must match `Action::make()`.
- Pinned in `AccountMenuTest`.

### 2026-09-14 — Multiple lesson videos, up to five sources (uncommitted)
- Added a lesson-video repeater with a **Source** selector for **YouTube** or
  **Upload**, and a **Add video** action that stops at five entries. Each item
  can hold one valid YouTube link or one uploaded video.
- The student lesson page now renders each saved video source in order so
  lessons can carry more than one approved clip without losing older single-video
  lessons.
- Stored single-video lessons still keep working because they are mapped into the
  repeater format when opened for editing.

### 2026-09-14 — Student site translated: page text now follows the language (uncommitted)
Reported as "the contents do not change on the pages". The header, sign-in and
Help were translated; every other student page was hard-coded English.
- **Where the words live:** `lang/{en,ru,es,fr,pt}/academy.php`, read through
  `__t('academy.…')`. `__tc()` handles plural lines: `one|many`, or
  `one|few|many` for Russian.
- **`Translator::line()` fallback order:**
  1. the translations table for this language (admin overrides)
  2. the shipped file for this language
  3. the same two for the default language
  4. the key made readable
- **Why files, not only the seeder:** the deploy never seeds. Keys that exist
  only in `LanguageSeeder` show up in production as "Hero Title"-style
  headlines, including in English. Shipped files work straight after
  `git pull`.
- **Not seeded into the database on purpose.** A seeded row never updates
  (`firstOrCreate`), so it would hide later fixes made in the file.
- **Wording that changed slightly:**
  - Bold was dropped inside a few sentences, such as "You have 1 attempt
    left.", so translations can reorder words.
  - "Create an account to save your progress." is now one link.
- **Durations** (`HasDuration::formatMinutes`) are translated too, so the panel
  uses them.
- **Certificate dates** use `isoFormat('ll'/'LL')`, which is locale-aware and
  the same in English.
- **Fixed on the way:** `SetLocale` ignored a browser sending only `pt-BR`,
  because Symfony reports it as `pt_BR`.
- **Not done:**
  - Course and lesson content: no page calls `translated()`, and there is no
    admin UI to enter content translations.
  - The certificate PDF and emails.
  - The older DB-only keys (`nav.*`, `auth.*`, `help.*`) still rely on
    `LanguageSeeder` having been run.
- Pinned by `StudentSiteTranslationTest`: key and placeholder parity across
  languages, every key used in the code exists, English with no seeding,
  pages in ru, es and pt, Russian plurals, and DB override.

### 2026-09-14 — Agent rules tightened: read first, preserve brand assets (uncommitted)
- Added a top-level working-order rule: **read first, every time**. Future
  agents must open the relevant files/assets/tests before editing, not work from
  memory or guessed patterns.
- Added an explicit brand rule: do not change the design of the logo, favicon,
  lockup, mark, colours or any brand artwork unless the owner specifically asks
  for that design change or supplies replacement assets. Reference/cache/path
  fixes must preserve the existing artwork exactly.

### 2026-09-14 — Why today's deploy showed old code and the old favicon
Nothing here was broken. The server pulls `laravel`, and `origin/laravel` was
still at `e3253197` (2026-09-08, version 2.0.0).
- **Unmerged work:** everything since is on `feature/support-engine-ports`,
  13 commits ahead and not merged: profiles, content health, the 2.1.0 changelog
  and more.
- **Favicon cache behavior:** keep the existing Pilot mark artwork. The app may
  version favicon URLs so browsers refresh them, but do not redraw, simplify or
  regenerate the logo/favicon unless the owner supplies replacement assets.
- **To ship:**
  1. Commit, including `public/`.
  2. Push, and let CI rebuild `public/build`.
  3. Open a PR into `laravel` and merge it.
  4. Deploy as in `DEPLOY.md`, including `filament:assets` and `optimize`.
- **If PHP changes still do not show after that,** reload PHP-FPM. OPcache may
  be holding the old files.

### 2026-09-14 — Deployment visibility and favicon check (uncommitted)
- Investigated why changes from earlier today were not showing after deploy.
  Current checkout is on `feature/support-engine-ports`, while `DEPLOY.md` says
  production pulls `laravel`; `laravel`/`origin/laravel` are still at
  `e3253197`, and the feature branch has the 2.1.0 work ahead of it. A deploy
  from `laravel` will not show those branch-only changes until they are merged
  or the server is intentionally pointed at the feature branch.
- Fixed the stale favicon references without changing the logo artwork. The
  public layout and Filament panel now use the existing `img/pilot-mark.svg`
  with a version query so browser caches refresh while the design stays the
  same. Do not redraw or simplify the Pilot mark/favicon; use the existing brand
  asset unless the owner supplies a replacement.

### 2026-09-14 — What's new: top-bar shortcut, PDFs open in a tab (uncommitted)
The only two pieces of support-engine's What's new that Pilot Academy lacked.
The page itself (search, category filters, PDFs, Latest) was already here.
- **Top-bar shortcut:** `resources/views/filament/topbar-whats-new.blade.php`,
  registered on `PanelsRenderHook::GLOBAL_SEARCH_AFTER`. It is a megaphone icon,
  matching the sidebar item, and it shows only if `Changelog::canAccess()`.
- **PDFs** are served with `->stream()`, so `Content-Disposition` is `inline`,
  and both links use `target="_blank" rel="noopener"`. Before, the file dropped
  into the downloads folder with nothing on screen.
- Pinned in `ChangelogPageTest`.

### 2026-09-14 — One guide: CLAUDE.md merged into agents.md (uncommitted)
Owner's request: keep `agents.md` as the single file. Its top is now the whole
rulebook: Project, How to work, and Standing instructions.
- The generic "senior engineer" template from `f78556b3` was filled in for this
  project. Its placeholders are gone and its points are kept.
- `CLAUDE.md` was initially kept as a short pointer to this file. It was removed
  when agent instructions were later consolidated here.
- Stale facts fixed on the way: the `is_admin` flag is now roles, and
  `php artisan pint` is now `./vendor/bin/pint`.
- The duplicated "Repo rules worth repeating" tail was removed, and its rules
  live under Standing instructions.

The student account menu's **Admin panel** link is now shown to **admins
only**; creators no longer see it there, though they can still open `/admin`
directly (owner's request). Pinned by `AccountMenuTest`.

### 2026-09-14 — PostgreSQL notification bell fix (uncommitted)
- Fixed `/admin` 500 on PostgreSQL after enabling Filament database
  notifications. `notifications.data` was created as `text`, but Filament's
  unread count queries it with `data->>'format'`, which requires JSON on
  PostgreSQL. Fresh installs now create it as `json`; existing installs get a
  migration that converts `text` to `json`.
- Verified in Docker: `notifications.data` is `json`, the `data->>'format'`
  query runs, and `DashboardTest` passes.

### 2026-09-14 — Profiles, content health, quiz attempts, feedback, mail (uncommitted)
From support-engine's profile, account menu and sidebar, filtered for a partner
academy. **Left uncommitted for the owner.** Built alongside another session's
uncommitted language work, which touches some of the same files (`User.php`,
`routes/web.php`, the panel provider, `layout.blade.php`) — every edit here was
anchored to exact current lines, and nothing of that work was changed.

**Student side**
- **Profile** at `/my/profile` (`ProfileController`): name, email, name on
  certificates; partner company shown read-only. Password section is **Set a
  password** for invite-link accounts and **Change password** (needs the current
  one) once a known password exists — `users.password_set_at`, stamped by a
  `User::saving` hook whenever a password is saved, except the join flow, which
  sets it `null` explicitly. The migration backfills staff and learners without a
  login token; learners *with* a token are left unset (may have joined by link).
- **Account menu** replaces name + decorative initial + Log out in the header: a
  native `<details>` (works without JS; a tiny script closes it on outside tap /
  Escape). Labels use `__t('nav.*')` like the rest of the translated header; the
  translator's fallback prints "Profile", "Admin Panel", "Account" until keys are
  seeded. The Admin Panel item is for admins only.
- **Header logo** is now the PILOT ACADEMY lockup at 1.75rem, same as the panel
  (owner's request) — replacing mark + text.

**Panel**
- **Content health replaced the dashboard card** (`ContentNeedingAttention`
  widget deleted). One definition, `App\Actions\FindContentProblems`, feeds:
  Content → Content health page + red badge; an **Attention** column and **Needs
  attention** filter on Courses and Lessons; the edit page's subheading;
  **publish checks** (course Publish, bulk publish, the course form's status
  change, lesson Publish into a live course) and a warning before unpublishing a
  live course's last lesson; and **owner notifications**.
- **Owner notifications** (`NotifyContentOwners`, Filament database notifications,
  new `notifications` table, `->databaseNotifications()`): model events on
  Course/Lesson/Question/Option call `afterRequest()`, a **named `defer()`** — so a
  form's dozens of saves become one check, run *after* the request. Checking at
  save time would see a new lesson before its questions exist. Owners = the
  product's creators, else admins; the actor is skipped; one alert per problem
  while unread; alerts for fixed problems are marked read. Not covered: changes
  to a course's final question bank via attach/detach (no model event).
- **Results → Quiz attempts** (learners only, admins only) with **Grant another
  attempt** → `attempt_grants` row. Grants are counted in
  `Course::finalQuizAttemptsAllowedFor/LeftFor` and
  `Lesson::quizAttemptsAllowedFor/LeftFor`, which the quiz pages now use — never
  count attempts anywhere else. Badge = students out of attempts without a pass
  (`QuizAttempt::stuckLearners()`, container-memoised per request).
- **Results → Student feedback**, admins only. Access is checked on the resource
  (`canViewAny`), not a model policy, so creators keep the course's feedback tab.
- **Settings → Mail** (`MailCheck`): says whether mail is really delivered (log /
  array are not), shows server, sender and `APP_URL`, sends a test email.
  Read-only — no mail passwords in the panel.
- **Guide** added to the panel's account menu; **Edit name** on Certificates
  reprints the PDF with a corrected name (optionally saving it to the student's
  profile). The Certificates badge (a total nobody acts on) is gone.

**Traps hit today**
- **The panel theme only scans `app/Filament`.** The subheading HTML is built in
  `app/Actions`, so `text-danger-600` silently failed to compile. Added an
  `@source` for `app/Actions`. Any class written outside those two folders needs
  the same.
- **Filament's notification payload is a text column** — PostgreSQL refuses JSON
  operators on text. Dedupe reads `viewData` in PHP.
- **Baseline suite before today's edits: 282 pass, 6 fail** — the theme test
  (my run forgot to mount `vite.config.js`) and five `CopyToPgsqlTest`, which only
  run when Postgres is reachable and someone had started the dev database.

### 2026-09-10 — Grouped sidebar and Final quiz health (uncommitted)
From support-engine's admin panel, seen in the running app. **Left uncommitted
for the owner.**

- **Sidebar grouped by job:** Content (Courses, Lessons, Products, Media Items),
  People (Users, Companies), Results (Certificates, Final quiz health), Docs.
  Group order is fixed by `->navigationGroups()` in the panel provider; items set
  `$navigationGroup` + `$navigationSort`. The admin guide's menu table now uses
  `Group → Item`, and `AdminGuideMenuTest` passed against it first time.
- **`FinalQuizHealth` page** (admin-only, learners only). Cards in the style of
  support-engine's *Success metrics*: value, what it is judged against, what it
  means, sample size.
  - *First-time pass rate* — each learner's first submitted final attempt per
    course. Band `PASS_BAND = [65, 80]` is **borrowed from support-engine and
    labelled "suggested"** — nobody has agreed one for this academy. Under
    `SMALL_SAMPLE` (10) it says "Too few to judge" instead of a verdict.
  - *Days to certificate* — median from first finished lesson in the course to
    the certificate. **No target**, and the page says so rather than inventing one.
  - *Question difficulty* — shown as **Not measured**. Attempts store
    `score`/`total`/`question_ids` only, never the chosen answers, so per-question
    difficulty cannot be computed. Storing answers would be a schema change.
- Checked in both themes at 1440px against seeded data (11/13 → 85%, "Above the
  band").

### 2026-09-10 — Home page: next step and "Your progress" (uncommitted)
From support-engine's learner dashboard. **Left uncommitted for the owner.**

- `AcademyController::nextStep()` replaces `nextLesson()`. Walks courses in
  order and stops at the first one started but unfinished: a lesson to do, or —
  new — **every lesson done with the final quiz still ahead** ("Your final quiz
  is ready"). The old card vanished at exactly that moment. A signed-in learner
  with nothing started gets **Start here** and a first-visit greeting instead of
  "welcome back".
- `progressSummary()`: in progress / completed / certificates, and the **last
  final quiz result**, which was previously a one-off session flash. "Completed"
  is the course page's rule (all lessons, plus the certificate when the course
  has a final quiz).
- `Course::finalQuizAttemptsLeftFor()` is now the one definition of attempts
  left; `FinalQuizController` uses it too, so home and the quiz page agree.
- Out of attempts → no final-quiz card (nothing to do), but the progress card
  says "no attempts left — contact your administrator".
- `HomeNextStepTest` (10). Two `ProgressToolsTest` names were renamed — they
  still pass, but "nothing is offered" was no longer true.

**Found, not fixed:**
- **A guest's progress does not carry over when they sign in.** It lives in the
  session and nothing merges it into the account, so a guest who finished every
  lesson starts again after registering. That is why the final-quiz card is
  never offered to guests.
- The home page search input renders only ~22px tall on a phone (the
  `search-form` partial), under the ~44px rule. Predates this change.

### 2026-09-10 — Recheck of support-engine: two fixes, and this file's structure
Went through all 137 commits on `support-engine`'s `hub-version2` for fixes
that never reached its changelog, and tested each candidate here with a
throwaway test rather than reading code and guessing.

**Fixed:**
- **YouTube links that are not one video saved silently.** Playlist, channel,
  Vimeo and `youtube.com/live/…` links all saved with no error and left the
  lesson with no video. `Lesson::youtubeIdFrom()` is now the single parser
  (anchored, id must end at a boundary, `live/` understood); the lesson form
  refuses what it rejects, and **Content needing attention** lists stored links
  it cannot read — lessons with an uploaded file are skipped, since the file
  plays instead.
- **The suite could wipe a real database.** Rehearsed against a stand-in
  "production" Postgres: with `php artisan config:cache` (which `optimize` runs
  on every deploy), `php artisan test` ran against the cached database, and the
  second run dropped a marker table — green throughout. Production is safe today
  only because it installs `--no-dev`. `tests/TestCase.php` now refuses anything
  but `:memory:` or a `*test*` database, **in `setUpTraits()`**. Note that
  support-engine's own guard runs after `parent::setUp()`, when `RefreshDatabase`
  has already migrated — too late. Re-run with the guard: refused, marker intact.

**Checked, not a problem here:** lesson HTML is output with `{!! !!}`, and
Filament's own source warns against exactly that — but its RichEditor strips
`<script>`, `onerror` and `javascript:` links on save, including from a tampered
Livewire payload. Answer keys never reach the browser. The lesson form already
puts video above content. Certificate logos are small (support-engine's OOM came
from a large one).

**Adopted from support-engine's AGENTS.md:** an *Invariants* table (every rule
with the test that holds it), a *Decisions* log, *Writing the guides*, and three
traps. Writing the invariants turned up one rule with no test behind it (docs
stripping raw HTML) — `test_raw_html_and_unsafe_links_are_not_rendered` now holds
it. `AdminGuideMenuTest` compares the guide's menu table with the real sidebar,
and failed on its first run: the guide said **Media items**, the sidebar says
**Media Items**.

**Offered, not taken up:** CI that runs the suite (`build-assets.yml` only builds
CSS, so nothing tests a PR), and plans for captions / video transcoding.

### 2026-09-10 — Ports from Support Training Hub
The sister project (`support-engine`, branch `hub-version2`, same stack) had
grown features that apply here. Reviewed its changelog and the session that
built it, checked each against this codebase, and ported the ones that fit.

**Already here, so not ported:** its admin dashboard (the one its
`docs/FILAMENT_DASHBOARD_PROMPT.md` describes) — overview, by-company chart,
stalled learners, nav badges, global search, admin-only scoping via
`ReportsOnLearners`, and the blank-password test. Its Range-request and
proxy-cache video fixes do not apply either: those were for streaming from
*private* storage, and uploads here are on the public disk.

**Ported:**
- **Help page** at `/help` from `docs/learner-guide.md`, linked from the header
  (**?**) and footer, open to guests. Reuses `AdminGuide::parse()` so both
  guides split at `##` the same way. Every claim in the guide was checked
  against the controllers — two were wrong in the first draft (a *timed*
  knowledge check shows only a score, not which answers were wrong; the
  **Course complete** feedback card waits for the certificate when a course has
  a final quiz).
- **What's new as a PDF** — `ChangelogPdfController`, reusing the page's
  parser. Releases now carry an `id` (slug of the heading).
- **Profile page** — `->profile(isSimple: false)`. Creators had no way to change
  their own password.
- **Docs navigation group** holding Guide (1) and What's new (2).
- **Guide contents + search** — same roll-up idea as What's new: the contents
  entries hide with their sections, and "Nothing in the guide mentions …" shows
  once.
- **youtube-nocookie + `rel=0`** on YouTube embeds.

**Traps hit today:**
- **The header was already full at 375px.** Adding Help wrapped "Pilot Academy"
  and "Log in" onto two lines. The fix sets priorities rather than squeezing:
  phones get a smaller mark and name that may truncate; from `sm:` up the
  brand never shrinks and the *learner's name* truncates instead. Without that,
  a long name at 640px collapsed the brand to just the mark. Log in, Log out and
  Register were also under the ~44px tap rule (20–32px) and now aren't.
- **Headless Edge/Chrome ignores `--window-size` below ~500px.** It lays the page
  out at 496px and crops the screenshot to 375, so overflow looks real when it
  is not (and vice versa). Use puppeteer-core with `setViewport({ width: 375 })`
  against the installed Edge — and check `document.documentElement.scrollWidth`,
  not the picture.
- **dompdf via the Laravel wrapper embeds whole fonts** (`enable_font_subsetting`
  is false in its config): the changelog PDF was 1.5 MB. Per-call
  `->setOption('isFontSubsettingEnabled', true)` brings it to ~60 KB. Certificates
  were left alone.
- **`AuthenticateSession` pins a test's session to the first user.** Two
  `actingAs()` users in one test → the second request is a 302 to login. One
  user per test.
- **The host `vendor/` and `node_modules/` had been emptied** since the last
  session. The panel theme `@import`s `vendor/filament/...`, so `npm run build`
  needs at least `vendor/filament`: copy it out of the image with
  `docker create` + `docker cp` rather than a full Composer install. Without
  `vendor/laravel`, the pagination classes drop out of the student bundle — as
  they do in CI, which never installs vendor. Nothing on the student site
  paginates.
- A method named `render()` on a Filament page collides with Livewire's — the
  What's new parser's renderer is `renderMarkdown()` for that reason.

**Version:** `v2.0.0` was already tagged (at `4fa98c7c`, before the What's new
merge), so these entries — and the filterable What's new entry, which also
landed after the tag — now sit under `## 2.1.0 — September 2026`, and
`config/app.php` says `2.1.0`. Tag `v2.1.0` on the merge commit into `laravel`.

### 2026-09-08 — What's new became a filterable list (**and the panel got a theme**)
`docs/CHANGELOG.md` was being dumped through `Str::markdown()` into
`doc.blade.php` — one long wall of text with no way to find anything. It is now
parsed at request time into release cards with a search box and category pills.
The file stays the single source of truth: nothing is duplicated, so nothing can
drift, and a new `## <Month> <Year>` heading adds a card on its own.

**The real work was the stylesheet, not the page.** The panel had no Tailwind
utility layer, so the first draft of the page rendered completely unstyled while
the Blade source looked right — the trap below, fired again. Fixing it properly
meant giving the panel a custom theme, which is now
`resources/css/filament/admin/theme.css` + a Vite input + `->viteTheme(...)`.
See the (rewritten) trap for the four things that must all stay true.

Things worth knowing before touching the page:

- **Visibility rolls up from the items, not down.** `itemVisible` → `sectionVisible`
  → `releaseVisible` → `anyVisible`, over a matrix built server-side and handed
  to Alpine. Filtering only the items is the obvious implementation and it is
  wrong: it leaves empty category headings and empty month cards standing
  behind the results.
- **Most of this changelog does not use `### Added` headings.** The entries are
  written as descriptive headings ("Videos remember where you stopped"), which
  land in the generic `other` category and keep their own text. That is by
  design — the plain-language headings are what admins actually read, and they
  were not going to be rewritten into four buckets after the fact. Entries
  written from here on *can* use the four categories and will colour and filter
  properly; both styles render side by side.
- **A section with no bullets still renders**, as one block. Given the point
  above, that fallback carries most of the file — it is not an edge case.
- The parse is cached on the file's `filemtime`, so an edit shows immediately
  and repeated views do not re-read it.
- Markdown is rendered with `html_input => strip` and `allow_unsafe_links =>
  false`. The file is ours, but nothing in a changelog needs raw HTML or a
  `javascript:` link.
- `[x-cloak]` **is** defined now (in the theme), which is what stops the
  "Nothing matches …" line flashing before Alpine boots. The note under
  *App version in the sidebar* saying `x-cloak` is a no-op in the panel was true
  when it was written and is no longer.
- The old footer line in `docs/CHANGELOG.md` ("also shown in the admin panel
  under **Changelog**") is gone — the label has been *What's new* for a while.
  The authoring notes that replaced it are an HTML comment, which the parser
  strips, so they are visible to whoever edits the file and to nobody else.

### 2026-09-02 — Video resume, transcripts, course feedback (**schema change**)
The three migration-needing items from the learner-experience review, shipped as
**one batch** so there is a single deploy window. Three migrations,
`2026_09_02_000001..3`.

**The trap that shaped the design.** Playback position is *not* a column on the
`lesson_user` pivot, though that is the obvious place. `completedLessons()` is a
plain `belongsToMany` with **no filter on `completed_at`**, and the dashboard's
raw queries (`CompletionsByCompany`, `StalledLearners`, `UsersTable`) read the
table directly. A row written when someone merely pressed play would therefore
count as a **completed lesson** everywhere — progress bars, partner reports, and
the gate that unlocks the final quiz and issues a certificate. Hence a separate
`video_positions` table. `test_watching_a_video_does_not_mark_the_lesson_complete`
guards it; do not "simplify" this onto the pivot without fixing that relation
first.

**Rollback was rehearsed** (roll back 3, check the site, migrate forward). It
surfaced a real hazard: **`/search` 500s while the migration is rolled back**,
because the query names `lessons.transcript`. Documented in `DEPLOY.md` — migrate
before use, and roll the *code* back before the migration.

Other notes:

- `course_feedback` needs an explicit `$table`; Laravel would pluralise the model
  to `course_feedbacks`.
- Feedback is staff-only and write-once-per-student (`updateOrCreate` on a unique
  `user_id + course_id`). The relation manager is `isReadOnly()` — students write
  it, staff read it. Deliberately **not** public star ratings.
- The position endpoint is `POST` + `auth`; anonymous visitors have nowhere to
  store this, and `keepalive: true` on the fetch is what makes the last write
  survive the tab closing.
- `.transcript { white-space: pre-line }` lives in the layout's `<style>`:
  `whitespace-pre-line` is not in the committed bundle either.

### 2026-09-02 — Lessons tab on the course editor
`LessonsRelationManager` on `CourseResource`: drag-to-reorder plus
**Add existing lesson**. Filament's `->reorderable('sort_order')` and
`AssociateAction`, no custom machinery.

Things worth knowing before touching it:

- **`lessons.course_id` is NOT NULL**, so `DissociateAction` is impossible and
  is deliberately absent. "Adding" an existing lesson therefore **moves** it out
  of its current course. The modal says so in as many words; do not quietly turn
  this into something that looks like a copy.
- **A relation manager only renders on the *edit* page.** There is no record to
  associate against while creating, so the tab appears after **Create**. The
  admin guide says this.
- **The associate list is scoped for creators** with the same `whereHas('course',
  …product_id…)` the Lessons list uses — otherwise a creator could pull a lesson
  out of a product they do not own. There is a test for it **and a positive
  control** proving a creator can still move lessons inside their own products;
  without the control the scoping test would pass even if the action were simply
  broken for creators.
- Reordering writes `sort_order`, which every student-facing query already sorts
  by, so the order takes effect immediately with no extra wiring.

### 2026-09-02 — Branding audit of the learner UI
Checked every logo surface on the student site after the lockup changed. Three
things had gone stale, all of them left behind by the mark turning amber.

- **The header's rationale was out of date.** Its comment said the lockup "would
  say PILOT twice" — true when the lockup was mark + PILOT, not now. The header
  still uses mark + HTML text, but for a *different* reason worth keeping: the
  bar is 64px, the mark 36px, and at that scale the lockup's stacked "ACADEMY"
  renders **~6px** and cannot be read. Comment rewritten to say so.
- **"Academy" was brand blue** (`text-brand`, #1463ff) next to an amber mark.
  Coherent while the mark was blue; a clash afterwards. Now one navy ink, with
  the amber mark carrying the colour.
- **`theme-color` was `#0284c7`** — the mark's old blue, which is in no palette
  any more. Now navy `#0a2540`.

The auth pages (login, register, join) now use the **real lockup** at `h-12`,
matching the admin sign-in's `3rem`, via
`resources/views/academy/partials/auth-brand.blade.php`. There is room there,
unlike the header.

**Still open: `apple-touch-icon` points at an SVG, which iOS ignores**, so "Add
to Home Screen" gets a screenshot instead of the mark. Fixing it needs a square
**180x180 PNG** in `public/img/`; the container has no ImageMagick, Imagick or
rsvg, and GD cannot rasterise SVG, so it could not be generated here.

### 2026-09-02 — App version in the sidebar
`config('app.version')` rendered at the bottom of the admin sidebar through
`PanelsRenderHook::SIDEBAR_FOOTER`, linked to **What's new**. See
[Cutting a release](#cutting-a-release) for the two places the number lives.

Checked rather than assumed, because the panel is a different world from the
student site:

- `SIDEBAR_FOOTER` renders as the **last child of `<aside>`**, unconditionally.
  (`SIDEBAR_NAV_END` sits inside `<nav>`, above Filament's own footer block.)
- The `--gray-*` custom properties are **not** in the static panel stylesheet;
  Filament injects them per page, so `var(--gray-400)` resolves at runtime and
  in both themes.
- Collapsing uses Alpine, not a CSS class: `x-show="$store.sidebar.isOpen"`,
  guarded by `filament()->isSidebarCollapsibleOnDesktop()` so the store is
  guaranteed to exist.
- **`[x-cloak]` is not defined in the panel stylesheet**, so `x-cloak` there is
  a no-op. It was removed rather than left in looking useful.

### 2026-09-02 — Six learner-experience improvements
The no-migration half of a review against Claude Academy / Udemy / LinkedIn
Learning. All six shipped together; **no schema change**, so this is an ordinary
`git pull` deploy.

1. **Duration is shown.** New `app/Models/Concerns/HasDuration.php` trait on
   Course and Lesson (`durationMinutes()`, `durationLabel()`, static
   `formatMinutes()`). Course overrides `durationMinutes()` to **fall back to
   the sum of its published lessons**, using the loaded relation when there is
   one so the home-page loop does not go N+1.
   **Watch out:** `duration_minutes` was NULL on every row in dev — the column
   existed but nobody filled it in. Everything degrades to showing nothing
   rather than "0 min", and `docs/admin-guide.md` now tells admins to set it.
2. **Video player.** Speed buttons + remembered volume/speed in `localStorage`
   (guarded try/catch — it throws in private windows). Uploaded videos only;
   YouTube already has its own controls.
3. **Search.** `GET /search` → `AcademyController@search`, a LIKE over titles
   and summaries. Uses `LOWER(...) LIKE ?` **on purpose**: SQLite's LIKE is
   case-insensitive but PostgreSQL's is not, and the Postgres move is written.
   Only published lessons in published courses are returned; there are tests
   that a draft never surfaces.
4. **Accessibility.** Skip link, `role="progressbar"` with values, `role="status"`
   on quiz results, `aria-current="page"` in the lesson sidebar, and text
   alternatives wherever a ✓ or colour was the only signal.
5. **Quiz cost up front** — "5 questions · 10 min limit · 2 attempts left".
6. **Course completion card** on the course page, plus `nextCourse` from the
   controller. Only shown when there is no final quiz left to take, so it does
   not duplicate the existing final-quiz card.

`.vh` (visually hidden) and the skip-link/focus styles live in the layout's own
`<style>` block, **not** Tailwind: `sr-only` is not in the committed bundle.

13 new tests in `tests/Feature/LearnerExperienceTest.php`. Note there are **no
model factories in this repo** — tests build rows directly, as the other suites
do.

### 2026-09-02 — Two mobile bugs on the student site
Found while reviewing the learner experience against Claude Academy / Udemy /
LinkedIn Learning. Both break the [Mobile](#mobile-student-site) rules.

**1. Certificates were unreachable on a phone.** The header link was
`hidden sm:block` with no menu behind it and no other route to
`/my/certificates` — a student on a phone who had earned a certificate could not
open it. Straight violation of *"Header/nav must not overflow or hide key
actions on narrow screens."* Now one link that shows 🎓 always and the word from
`sm:` up, `h-11` so the tap target meets the ~44px rule.

**2. Uploaded videos forced full screen on iOS.** `<video controls>` without
`playsinline` makes iOS Safari take over the screen on play, hiding the lesson
body and the quiz. YouTube lessons were unaffected, so the experience silently
depended on which source the admin picked. Added `playsinline`.

**The bundle trap fired again, and this time it would have shipped.** The first
attempt used a separate icon link hidden with `sm:hidden` — **`sm:hidden` is not
in `public/build/assets/app-*.css`**, so the icon would have shown on desktop
too, next to the text link. `sm:block` and `hidden` *are* in the bundle, which is
why the inverted form works. Every class in the fix was checked against the
bundle before committing to it.

### 2026-09-02 — Lockup now says PILOT ACADEMY
The supplied lockup was mark + "PILOT" only. Regenerated all three variants
(`pilot-logo.png`, `-white`, `-blue`) with "ACADEMY" added.

**Pilot already has a house rule for this** and we now follow it. The product
lockups on <https://pilot-telematics.com/products/> (PILOT Video, IOT,
Autoconductor, Utilities, Development, TMS) all set the product word the same
way, and measuring six of them gives:

| | |
|---|---|
| descriptor cap height | **40%** of the PILOT cap |
| gap below the PILOT baseline | **0.27 x** the PILOT cap |
| alignment | left edge of the **wordmark**, not the mark |
| colour | **`#9F9FA9`** grey, whatever colour the mark is |
| placement | **stacked underneath**, the stack centred on the mark |

Their proportions are ours: PILOT's cap is 45% of canvas height in both. So the
wordmark is raised 90px to re-centre the stack, and ACADEMY sits at cap 109px,
0.18em tracking, baseline 529.

**Canvas stays 1920x604**, so nothing that hardcodes the ratio had to move —
`certificates/pdf.blade.php` keeps `54mm x 17mm` and the mail templates keep
`width=180`. An earlier attempt set ACADEMY *inline* after PILOT, which pushed
the canvas to 3238x604 and forced changes in all three of those files; that was
reverted once the house convention was clear. **If you ever change the canvas
ratio, those files must move with it.**

Typeface: the PILOT logotype is a custom face we do not have, so the descriptor
is DejaVu Sans Bold — the only TTF on hand, shipped with dompdf. Generated with
GD in the container: there is no ImageMagick and no system fonts.

The student site header does *not* use the lockup (mark SVG + HTML text), so
it needed nothing.

### 2026-09-02 — Sign-in page: the brand logo
Reported as "the Sign in text is bigger than the logo". It was, but the cause
was not a size choice — `resources/views/filament/brand/logo.blade.php` styled
the logo with `h-7`, `dark:hidden` and `dark:block`, **none of which exist in
the Filament panel stylesheet** (see the trap below). So the height never
applied and the dark swap never applied: Filament's default `1.5rem` box was
holding *both* lockups, drawn on top of each other, on every panel page.

Deleted that view and used Filament's real API in `AdminPanelProvider`:
`brandLogo()` + `darkModeBrandLogo()` (which drive the working
`fi-logo-light`/`fi-logo-dark` CSS) and `brandLogoHeight()`, which takes a
**closure** and is evaluated per request — so the sign-in screen gets `3rem`
and the panel chrome keeps `1.75rem` without any custom CSS:

```php
->brandLogoHeight(fn (): string => request()->routeIs('filament.*.auth.*') ? '3rem' : '1.75rem')
```

48px of brand against a 24px `text-2xl` heading puts the hierarchy back the
right way up. `brandAsset()` returns `null` for a missing file, which makes
Filament print the brand name rather than a broken image.

### 2026-09-02 — Nudge students who have gone quiet
Closed the loop the dashboard had left open: the stalled-learners panel now
*identifies* students **and** lets you email them. Row action plus a bulk one,
sending a personal magic link that lands them on the home page, where "Continue
where you left off" already offers the next lesson — so no resume logic is
duplicated in the email.

Nudges are recorded as a new `ActivityEvent` type (`reminder_sent`), which needs
**no migration** because `type` is a plain string column. That gives a "Reminded"
column and a 7-day cooldown, so two managers working the same list cannot chase
the same person twice.

Also switched the six student-facing meta descriptions from Russian to English —
learners are English-first; French, Spanish and Portuguese come later.

### 2026-08-31 — Branding and a contrast fix
Added the Pilot mark and full lockup: favicon (the site had **none** — the
stock `favicon.ico` is 0 bytes), public header, Filament panel brand, the
certificate PDF, the certificate email, and `og:image` for link previews.
Assets live in `public/img/` as `pilot-mark.svg`, `pilot-logo.png`,
`pilot-logo-white.png` — renamed from `Coloured.png`/`White.png` because the
production filesystem is case-sensitive and the old names invited a 404 that
would never reproduce on Windows.

Also fixed the "Welcome to Pilot Academy" hero: the name input had no
background, so dark text sat on the gradient at **1.06:1** against the navy
end. See the Tailwind preflight trap below.

### 2026-08-31 — Admin dashboard (branch in progress)
Eight widgets, all learner-scoped through one trait
(`app/Filament/Widgets/Concerns/ReportsOnLearners.php`) so a stat added later
cannot start counting staff. Content-health warnings, hardest lessons, activity
trend, most-opened courses, stalled learners, progress by company. Plus
navigation badges, global search, bulk publish/unpublish, duplicate-a-course,
and a learner-progress CSV export. Student home gained "Continue where you left
off".

Two real bugs fixed on the way: a question with no correct answer could never
be passed (grading always returned false, nothing prevented saving it), and the
dashboard would 500 on a healthy academy because a Livewire view rendered no
root element.

### 2026-08-10 — PostgreSQL migration (branch, unmerged)
`db:copy-to-pgsql` moves the data; the migrations own the schema. Boolean
columns are read off the **target** schema rather than a hard-coded list,
because a hard-coded list had already gone stale. Rehearsed in both directions
against legacy rows. Runbook in `docs/postgres-cutover.md`.

### 2026-08-10 — Creator role (PR #33, merged)
`users.is_admin` became `users.role` (`admin` | `creator` | `learner`), plus a
`Product` entity as the unit of ownership. Access is enforced twice: query
scoping in the Filament resources *and* policies per record. Users screen gained
role tabs; every report counts learners only.

### 2026-08-10 — Course publishing workflow (PR #32, merged)
Courses and lessons carry `draft | published | archived` instead of
`is_published`. New courses start as drafts; lessons stay published because the
course is the gate. Also closed a pre-existing hole where the quiz **submit**
endpoint never checked whether the lesson was published.

---

## Verifying

There is no `vendor/` in this checkout and the system PHP is 8.3, so **you
cannot run the suite on the host**. Use the container:

```bash
docker compose run --rm app php artisan test
docker compose run --rm app ./vendor/bin/pint --test app tests
```

Notes that will save you time:

- `php artisan pint` does **not** exist. Pint is `./vendor/bin/pint`.
- Run `php artisan key:generate --force` first in any bare container, or every
  test dies with `MissingAppKeyException`.
- If you bind-mount source over the image, mount **individual directories**
  (`app`, `tests`, `resources`, …). Mounting the repo root hides the image's
  `vendor/` and nothing works.
- On Git Bash, Docker mounts need `MSYS_NO_PATHCONV=1` and a leading double
  slash: `-v "//c/Users/.../app:/var/www/html/app"`.
- **The image holds the code it was built with.** `docker compose run app …`
  without mounts tests *that* copy, not your edits — on 2026-09-08 it quietly
  ran the old versions of tests that had just been rewritten, and passed. Mount `app`, `tests`,
  `resources`, `docs`, `config`, `routes`, `database` and `public/build`, and
  run `composer dump-autoload -o` inside so new classes are found.
- The entrypoint waits for Postgres and migrates it. For the suite alone, which
  uses SQLite in memory, skip both: `--no-deps --entrypoint ""`.
- **Never run the suite with config cached.** It would run against the database
  the cache names; `tests/TestCase.php` now refuses, with a message telling you
  to `php artisan config:clear`.
- After changing config, routes or views in a running app, run
  `php artisan optimize:clear` before you judge what you see.

---

## Writing the guides

Three Markdown files ship inside the product — `docs/CHANGELOG.md`,
`docs/admin-guide.md`, `docs/learner-guide.md` — and people read them expecting
them to be true.

**What counts as a change they need:** a new screen, menu item or group; a
renamed button, field, label or status; a changed rule about who may do what; a
step added to or removed from a workflow; anything that changes what finishes a
lesson, unlocks the final quiz, or issues a certificate.

**Quote labels exactly as the app prints them.** Check the `->label()`, the
navigation label, or the page on screen before writing a name into a guide — the
class name is not the label. The admin guide said **Media items**; the sidebar
says **Media Items**. `AdminGuideMenuTest` now fails if the guide's
menu table and the real sidebar disagree; it cannot see buttons inside screens,
so check those by hand.

**Never describe a screen or a rule you have not confirmed in the code.** Write
from the application, not from memory or the plan. The first draft of the
learner guide claimed a timed quiz shows which answers were wrong (it shows only
the score) and that feedback is asked after the last lesson (with a final quiz,
it waits for the certificate). Both read as true; both were checked and fixed.

**`##` headings are addresses.** Both guides turn each one into a section and a
contents link (`#finishing-a-lesson`). Renaming a heading breaks any link to it —
keep headings stable, and search for the old anchor if you must rename.

**Changelog entries** go under the current version heading, in `### Added` /
`### Changed` / `### Fixed` / `### Known limitations` — those drive the category
filter. Write for the person using the academy: if nothing anyone can see or do
changed, it does not belong there.

---

## Traps already paid for

Each of these looked like something else at first.

**Do not edit application files while the suite is running.** Filament
auto-discovers resources, pages and widgets. A half-written resource whose page
class does not exist yet breaks every panel test in the run — which then looks
like your change broke everything. Wait, or only add files nothing references
yet. (Paid for twice in `support-engine`, same stack.)

**Unstyled or dead student pages locally? Delete `public/hot`.** The layout uses
`@vite` whenever `public/hot` exists, and Vite leaves it behind on an unclean
exit — so the page asks a dev server that is not running for its CSS.

**Write PHP with the Write/Edit tools, not shell heredocs or `sed`.** Git Bash
eats backslashes (namespaces, regex) and mangles quoting. On 2026-09-08 a
heredoc carrying a PHP class died on shell quoting; on 2026-09-10 a
script-inserted `use` line never landed while the rest of the same script did,
and the output said "ok".

**The CSS bundle is built in CI, not locally.** Nobody here runs npm, so
`public/build/` is committed and CI rebuilds it on push to `laravel` or
`feature/**`. **Before using a Tailwind class that is not already used
somewhere, check it exists in the committed bundle** — otherwise it silently
does nothing until CI catches up:

```bash
grep -c '\.text-slate-900' public/build/assets/app-*.css
```

This is not theoretical, and it has now fired twice: a hero input styled with an
absent `text-slate-900` would have inherited the card's `text-white` and rendered
**white on white**; and **`sm:hidden` is not in the bundle** (though `hidden` and
`sm:block` are), so an element hidden that way on desktop stays visible. Prefer
the `hidden sm:block` direction, which is already compiled.

**The Filament panel had no Tailwind utility layer at all — it does now, and
only because a theme was added.** Filament v5 ships semantic `fi-*` classes
instead of utilities, and out of the box `/admin` loads only
`public/css/filament/filament/app.css`. `h-7`, `hidden`, `dark:block`,
`text-2xl`, `mb-6` — **none of them exist in that file**. A utility class in a
Blade view rendered inside the panel did nothing, silently: no error, no
warning, and source that looks perfectly correct.

Since 2026-09-08 the panel registers `resources/css/filament/admin/theme.css`
via `->viteTheme(...)`, so utilities **do** work in panel views now. Three
things keep that true, and removing any one of them silently unstyles every
custom page again with nothing in the logs:

1. the theme file exists,
2. `vite.config.js` lists it in the `input` array,
3. `AdminPanelProvider` calls `->viteTheme(...)`,
4. the theme's `@source` lines name our own directories.

That fourth one is the subtle one. Filament's theme entry opens with
`@import 'tailwindcss' source(none)`, which switches automatic content
detection **off**. Without explicit `@source` lines the build still succeeds
and still writes a stylesheet — one that has never seen our markup, so every
class in it is missing. `ChangelogPageTest::test_the_panel_has_a_custom_theme_that_scans_our_own_views`
guards all four.

The theme is compiled by the same CI job as everything else in `resources/`
(`build-assets.yml`, which triggers on `resources/**`), into the committed
`public/build/`. **A view rendered inside the panel before that job runs is
unstyled**, same as the student site.

Views written before the theme existed are still styled with inline CSS on
purpose — `sidebar-version.blade.php`, `doc.blade.php`. They work; leave them.
Filament's own API (`brandLogoHeight()`, `->extraAttributes()`) is still the
better tool for anything Filament already models. This trap is what made the
sign-in logo the wrong size *and* broke its dark-mode swap.

**`select distinct lessons.*` cannot run on PostgreSQL.** `lessons` carries two
`json` columns (`doc_links`, `video_sources`), and PostgreSQL has no equality
operator for `json`, so any `distinct` over `lessons.*` dies with
`SQLSTATE[42883]: could not identify an equality operator for type json`.
Filament builds exactly that query for a relation manager's **Attach** picker,
which is why Add existing lesson failed on Postgres while every test passed —
SQLite compares json as text and never complains. Whenever a query over
`lessons` needs `distinct`, name the columns. Watch for the sequel, too: once
the select is narrowed, `distinct` refuses any ordering column that is not in
it, and `Course::lessons()` is ordered by the pivot — so `reorder()` on a column
you did select. Found in a browser, not by the suite.

**Tailwind preflight makes form controls transparent.** An `<input>` with no
`bg-*` class has no background. Fine on a white card, invisible on a coloured
one.

**`@php(...)` is not to be trusted at all — use `@php … @endphp`.** It is not
only ternaries: `@php($questionCount = $lesson->questions->count())` also emitted
a raw, unterminated `<?php(` and swallowed the rest of the template, while other
`@php(...)` lines in the *same file* compiled fine. The page 500s and
`view:cache` still reports success. Always use the block form.

Why, paid for again on 2026-09-14 (every lesson page, and search, returned 500):
Blade stores block PHP sections *first*, with a lazy match from the directive
to the next `@endphp`, before it compiles anything else or removes comments.
- **An inline form placed above a block** is taken as the start of a block. It
  swallows every line down to that block's end marker.
- **The directive's name written inside a Blade comment** does exactly the
  same, because comments are still there when this match runs. Describe it in
  words instead.

Grep a view for the directive before calling it done.

**A Blade directive glued to the preceding word is not compiled.**
`...lessons@if($x)` leaves `@if` as literal text while its `@endif` compiles, so
PHP hits an `endif` with no `if` and the template dies with "unexpected endif".
Always leave whitespace before `@if` / `@endif` / `@foreach`. Both halves of
this trap cost a debugging round on the same afternoon; when a Blade page 500s
with a syntax error, lint the compiled file in
`storage/framework/views/` and grep it for directives that never compiled:

```bash
grep -noE "@(if|endif|else|foreach|endforeach)\b" storage/framework/views/<hash>.php
```

**PHP casts float array keys to int.** `@foreach([1.25 => 'a', 1.5 => 'b'] …)`
silently collapses to a single entry. Use pairs: `[['1.25', 'a'], ['1.5', 'b']]`.

**A Livewire view must have a single root element.** Wrapping the whole view in
`@if` means an empty render, which throws. Put the `@if` inside the root.

**`replicate()` copies query aggregates.** A model loaded through a Filament
table carries `lessons_count` from `withCount()`; replicating and saving fails
on a column that does not exist. Filter to real columns.

**`lessons.slug` has no unique index, but the route binds `{lesson:slug}`
globally.** Two lessons sharing a slug resolve to the first one and then 404.
Anything that copies lessons must generate fresh slugs.

**The server's `php` is 8.3; the app needs 8.4.** Always call `php8.4`
explicitly for `artisan` and `composer` on the server.

**PHPUnit runs the whole suite in one process** and the certificate tests render
real PDFs. `phpunit.xml` raises `memory_limit` to 512M for that reason — do not
lower it.

**Filament v5 test API**: bulk actions are
`->selectTableRecords([...])->callAction(TestAction::make('x')->table()->bulk())`.
`callAction` has no `records:` parameter.

**Render tests hide save bugs.** A form can render at 200 and explode on save.
Submit at least one form per feature:
`->fillForm([...])->call('create')->assertHasNoFormErrors()`, then assert the row
landed **with its relationships**.
