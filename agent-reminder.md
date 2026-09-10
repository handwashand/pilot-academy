# Agent reminder

Working notes for whoever picks this repo up next — human or AI. It exists so a
new session can find out what was done, what is half-finished, and which traps
have already cost someone an afternoon.

`CLAUDE.md` is the rulebook. This file is the memory.

---

## Standing instructions — read before you start, follow before you finish

**1. Document after every task.** Before you call a piece of work done, add an
entry to the [work log](#work-log) below: what changed, why, and anything left
open. Newest first. A one-line entry beats no entry.

**2. Always update `docs/CHANGELOG.md`.** It renders live in the panel under
**What's new**, so admins read it. Newest first, dated, plain language — describe
what a person can now do, not which class you added. Required whenever you
change anything an admin or student can see.

**3. Always check `README.md` and `docs/admin-guide.md`.** The guide renders in
the panel under **Guide** and goes stale silently. If you changed a screen, a
button, or a rule someone follows, the guide is part of the change, not a
follow-up. The README describes the stack and local setup — update it when
either moves. `DEPLOY.md` too, if you touched deployment.

**4. Never report work as verified unless you ran it.** `php artisan test`,
`pint`, and — for anything visible — actually load the page. If you could not
run something, say so plainly. See [Verifying](#verifying) for how, because it
is not obvious in this repo.

---

## Where things stand

Last updated: **2026-09-10**

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

| Branch | State |
| --- | --- |
| `laravel` | Main. Deploys to production by `git pull`. At the merge of PR #33. |
| `feature/support-engine-ports` | Off `laravel`. Ports from support-engine: Help page, What's new PDF, profile page, Docs group, guide search, privacy-enhanced YouTube, header fit at 360px. |
| `feature/whats-new-page` | Merged into `laravel` (e3253197). Safe to delete. |
| `main` | **Not the deploy branch.** Carries an unrelated "Initial commit" history plus a merge of `laravel`. Branch from `laravel`, not from here. |
| `feature/sqlite-postgres` | **Pushed, no PR opened.** SQLite → PostgreSQL move. Ready for review. |
| `feature/admin-dashboard` | PR #34, open. Dashboard, branding, nudge, mobile fixes. |
| `feature/learner-experience` | **Stacked on `feature/admin-dashboard`, not on `laravel`.** Duration, search, video controls, accessibility, quiz cost, course completion. Merge #34 first. |
| `feature/course-publishing-workflow` | Merged as PR #32. Safe to delete. |
| `feature/creator-role` | Merged as PR #33. Safe to delete. |
| `feature/postgres-migration` | Duplicate of `feature/sqlite-postgres`, same commit. **Delete it** so nobody reviews the wrong one. |

### Open threads

- **PostgreSQL cut-over has not happened.** The code is written and rehearsed,
  but production still runs SQLite. It is a downtime window, not a normal
  deploy — follow `docs/postgres-cutover.md` exactly, and note the rollback
  order: **roll the migration back before reverting the code.**
- **`feature/admin-dashboard` is large** — widgets, bulk actions, an export, a
  learner-facing change, branding. Worth splitting before review; the
  content-health fix stands alone as a genuine bug fix.
- **PRs still need opening**: sqlite-postgres, admin-dashboard and
  support-engine-ports.
- **Two bigger features are waiting on decisions**, not code: video engagement
  and multilingual. See `docs/plans/support-engine-features.md` — each has a
  "Decide first" list. Do not start either without answers. **Refreshers were
  declined** (2026-09-10): the two academies serve different purposes, so
  support-engine's competency features are not ported here by default.
- **`APP_URL` must be the real domain in production.** The certificate email
  builds its logo URL from it; a wrong value ships broken images to students.

---

## Work log

Newest first. Add to this every time.

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
LinkedIn Learning. Both break rules `CLAUDE.md` sets for this project.

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

---

## Traps already paid for

Each of these looked like something else at first.

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

**Tailwind preflight makes form controls transparent.** An `<input>` with no
`bg-*` class has no background. Fine on a white card, invisible on a coloured
one.

**`@php(...)` is not to be trusted at all — use `@php … @endphp`.** It is not
only ternaries: `@php($questionCount = $lesson->questions->count())` also emitted
a raw, unterminated `<?php(` and swallowed the rest of the template, while other
`@php(...)` lines in the *same file* compiled fine. The page 500s and
`view:cache` still reports success. Always use the block form.

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

---

## Repo rules worth repeating

From `CLAUDE.md`, because they are the ones most often skipped:

- Work on a feature branch off `laravel`; open a PR into `laravel`.
  **Do not self-merge.**
- Prefer Laravel built-ins. No Repository / DTO / Service / Interface patterns
  unless clearly required.
- Touch only files related to the task. No unrelated refactors or reformatting.
- Every student-facing page must be **mobile-first** and readable at ~375px.
- Commit messages: subject line, blank line, body. No AI attribution.
