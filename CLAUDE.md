# Pilot Academy

Internal training LMS. **Laravel 13 + Filament 5** admin panel at `/admin`,
Blade + Tailwind public front end, **SQLite**. Requires **PHP 8.4**.

Tailwind is compiled by **Vite** into `public/build/` (entry
`resources/css/app.css`, brand palette in its `@theme`). Nobody here runs
npm locally, so the bundle is built in CI (`.github/workflows/build-assets.yml`)
and **committed** to the branch — the `git pull` deploy has no build step. If
the compiled bundle is missing (fresh checkout, no build), the academy layout
falls back to the Tailwind CDN for local dev only; production always serves the
committed static CSS.

Content model: `Course` → `Lesson` → `Question` → `Option`. Lessons carry a
YouTube link or an uploaded video file; quizzes are checked server-side.
Lesson progress is saved per user account when logged in, or in the session
for anonymous visitors. Students (partners) belong to a `Company`; the
`is_admin` flag separates admins (Filament panel) from student accounts.

## Simplicity

Prefer Laravel built-in features.

Do not create:

- Repository pattern
- DTOs
- Interfaces
- Services

unless clearly required.

## Existing code first

Before creating code:

- Search existing models
- Search existing controllers
- Search existing actions
- Search existing traits

Reuse before creating.

## Editing

Touch only files related to the task.

No unrelated refactors.

No mass formatting changes.

## Mobile (student-facing frontend)

Every student-facing page (home, course, lesson, login, register, and any
future learner page) **must be mobile-optimized**. Phones are a first-class
target, not an afterthought.

- Design mobile-first; verify at a narrow viewport (~375px), not just desktop.
- No horizontal overflow; comfortable padding on small screens.
- Touch targets ~44px tall (buttons, quiz answer options).
- Single column on mobile; multi-column only at `sm:`/`lg:` breakpoints.
- Responsive media: video uses `aspect-video`; images in lesson content
  must not overflow (`max-width: 100%`).
- Header/nav must not overflow or hide key actions on narrow screens.

The Filament `/admin` panel is desktop-first and exempt.

## Docs & changelog

Two Markdown files under `docs/` are rendered live inside the panel and must be
kept current:

- `docs/admin-guide.md` — the manager guide, shown under **Guide**
  (`app/Filament/Pages/AdminGuide.php`, `/admin/admin-guide`).
- `docs/CHANGELOG.md` — a plain-language change log, shown under **What's new**
  (`app/Filament/Pages/Changelog.php`, `/admin/changelog`).

Both render through `resources/views/filament/pages/partials/doc.blade.php`.

**Whenever you change academy behaviour visible to admins or students (courses,
lessons, the final quiz, certificates), in the same change:**

1. Update `docs/admin-guide.md` if the instructions change.
2. Add a dated entry to `docs/CHANGELOG.md` (newest first, plain language).
3. Update `README.md` if the stack or local setup moved, and `DEPLOY.md` if
   deployment did.

**Then record the task in `agent-reminder.md`** — the running work log at the
repo root. Read it before starting anything: it carries what is half-finished,
which branches are still open, and the traps this codebase has already sprung
on people (the CI-built CSS bundle, Blade `@php()`, Livewire root elements).
Add an entry before you call a task done.

A styled copy of the guide (with screen mockups, for print/PDF) is also
maintained as a Claude Artifact by the team lead — keep it in sync when it is
being updated.

## Verification

Before completion:

- `php artisan test`
- `php artisan pint`
- `php artisan optimize:clear`

## Uncertainty

If requirements are unclear:

- Stop
- Explain uncertainty
- Ask questions

## Git & deploy

- Work on a feature branch off `laravel`; open a PR into `laravel`. Do not self-merge.
- The server deploys by pulling the `laravel` branch (`git pull` + `php artisan migrate --force` + `php artisan optimize`).
- On the server use `php8.4` explicitly for `artisan`/`composer` (system `php` is 8.3).
