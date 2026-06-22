# Pilot Academy

Internal training LMS. **Laravel 13 + Filament 5** admin panel at `/admin`,
Blade + Tailwind (CDN, no build step) public front end, **SQLite**.
Requires **PHP 8.4**.

Content model: `Course` → `Lesson` → `Question` → `Option`. Lessons carry a
YouTube link or an uploaded video file; quizzes are checked server-side and
lesson progress is kept in the session.

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
