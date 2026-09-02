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
**Changelog**, so admins read it. Newest first, dated, plain language — describe
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

Last updated: **2026-08-31**

### Branches

| Branch | State |
| --- | --- |
| `laravel` | Main. Deploys to production by `git pull`. At the merge of PR #33. |
| `feature/sqlite-postgres` | **Pushed, no PR opened.** SQLite → PostgreSQL move. Ready for review. |
| `feature/admin-dashboard` | **In progress, uncommitted work on it.** Dashboard, branding, contrast fix. |
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
- **Two PRs still need opening**: sqlite-postgres and admin-dashboard.
- **`APP_URL` must be the real domain in production.** The certificate email
  builds its logo URL from it; a wrong value ships broken images to students.

---

## Work log

Newest first. Add to this every time.

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

This is not theoretical: a hero input styled with an absent `text-slate-900`
would have inherited the card's `text-white` and rendered **white on white**.

**Tailwind preflight makes form controls transparent.** An `<input>` with no
`bg-*` class has no background. Fine on a white card, invisible on a coloured
one.

**`@php(...)` cannot take a ternary.** Blade emits a raw, unterminated `<?php`
and swallows the rest of the template — the page 500s and `view:cache` still
reports success. Use the `@php … @endphp` block form.

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
