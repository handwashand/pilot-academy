# Pilot Academy

**Pilot Academy is a certification engine for Pilot's partner ecosystem.** Pilot is a white-label fleet and telematics SaaS sold through partners, so the quality of every end-customer experience depends on how capable the partner's team is.

The Academy trains and certifies partner staff, then certified partners train their own fleet-owner customers. Partner staff are the **multiplier** and certification is the **quality gate** that protects every customer downstream.

## Overview

Pilot Academy is a Laravel 13 application with a public student site and a Filament 5 admin panel at `/admin`.

Student site:

- courses, lessons, and written or video content, in English, Russian, Spanish, French, Portuguese and Arabic
- knowledge-check quizzes after each lesson, and a final course assessment
- certificates with PDF download and public verification by certificate number
- case studies, tutorials and webinars for partners, with search and filters
- profile, password reset, and a Help page

Admin panel:

- courses, lessons, quiz questions and final quiz banks, with translation of content and quizzes into any active language
- case studies, tutorials, webinars and media
- partners, users and roles (admin, creator, learner), with creators limited to their own products
- certificates, quiz attempts, student feedback, and a dashboard of learner progress
- a recycle bin for deleted content, and an in-panel guide and change log

## Built with

- Laravel 13 and Filament 5
- PostgreSQL in production; the test suite runs on SQLite in memory
- Tailwind CSS and Vite, with two entries: `resources/css/app.css` for the student
  site and `resources/css/filament/admin/theme.css` for the admin panel. The
  compiled CSS is committed in `public/build/`, so the server does not run Node.
- DOMPDF for certificate PDFs, and simple-qrcode for certificate QR codes

## Getting started

### Requirements

Composer
- Node.js and npm
- PostgreSQL 17- 
PHP 8.4 with the extensions listed in [DEPLOY.md](DEPLOY.md)
- Composer 2
- Node.js and npm, only if you change CSS or JavaScript
- PostgreSQL 17 for local development

Docker is an option for development and for running the tests. It is not part of
the production setup.
But Docker is optional but supported via `docker-compose.yml`

### Install locally

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --force
npm install --ignore-scripts
npm run build
```

### Run the app locally

```bash
php artisan serve
npm run dev
```

Open the public site at `http://127.0.0.1:8000` and the admin panel at
`http://127.0.0.1:8000/admin`.

## Development with Docker

`Dockerfile` and `docker-compose.yml` provide a development environment and a
place to run the tests, so PHP 8.4 and the extensions do not have to be installed
on the host. Production does not use Docker.

```bash
docker compose up --build
```

Open the public site at `http://localhost:8000` and the admin panel at
`http://localhost:8000/admin`. On first boot the container writes its `.env`,
generates the app key, migrates, and seeds the demo course plus an admin login
(`admin@pilot.local` / `password`). Change that password before using the
environment for anything shared.

The bundle committed in `public/build/` is served as-is, so no npm step is
needed in the container. PostgreSQL data and uploaded media live in named
volumes and survive `docker compose down`; add `-v` to start from a clean slate.

Docker copies the source into the image at build time, so rebuild after editing
PHP or Blade files (`docker compose up -d --build`) before testing them.

## Testing

Run the suite on a host with PHP 8.4:

```bash
composer test
```

Or inside Docker:

```bash
docker compose run --rm app php artisan test
```

Do not run the suite with the configuration cached. The test base class refuses
any database other than `:memory:` or one named `*test*`, and `composer test`
clears the cache first for that reason.

## Deployment

Production runs on a PHP-FPM server that pulls the `laravel` branch with
`git pull`, then runs `php8.4 artisan migrate --force` and
`php8.4 artisan optimize`. No Node build runs on the server. Read
[DEPLOY.md](DEPLOY.md) before deploying, especially the note about the committed
`public/build/` bundle.

## App structure

- `app/Http/Controllers/AcademyController.php` — student pages, course and lesson flows, lesson quizzes, completion tracking
- `app/Http/Controllers/FinalQuizController.php` — final course assessment and certificate issuance
- `app/Http/Controllers/CertificateController.php` — certificate listing, download, and public verification
- `app/Http/Controllers/Auth/StudentAuthController.php` — sign in, registration, the join flow, and magic-link access
- `app/Models/Course.php` — course configuration, published lesson access, final quiz unlock rules
- `app/Models/Lesson.php` — lesson content, video helpers, quiz limit helpers
- `app/Models/Certificate.php` — certificate validity, PDF path, verification URL
- `app/Models/User.php` — accounts and roles, login tokens, completed lessons, certificates, activity logging
- `app/Services/Translator.php` — interface translation and the translations table
- `docs/` — the admin guide, learner guide, privacy policy and change log, all read from disk at request time

## Student user flow

1. Register or join from the public site
2. Browse published courses
3. Open a lesson, watch the video or read the content, and complete the knowledge check
4. Finish every lesson in a course to unlock the final assessment
5. Pass the final quiz to receive a certificate
6. Download the certificate PDF from the account menu

Stuck at any step? **Help** (`/help`, in the account menu and the footer) explains
the flow in plain language.

## Admin flow

1. Sign in to the admin panel at `/admin`
2. Create courses, lessons and quiz questions, and publish them when ready
3. Add media, translations, and the final quiz bank
4. Set pass percentage, attempt limits and the certificate template for each course
5. Add partners and student accounts as needed
6. Review issued certificates, revoke or regenerate them, and verify certificate numbers

## Docs

- Admin guide: `docs/admin-guide.md`, shown in the panel under **Docs → Guide**
- Change log: `docs/CHANGELOG.md`, shown under **Docs → What's new** and downloadable as a PDF
- Student guide: `docs/learner-guide.md`, shown on the public site at `/help`
- Privacy policy: `docs/privacy-policy.md`

Each `## ` heading in a guide becomes a section and a contents entry. In the change
log, `## <Month> <Year>` starts a release, and `### Added`, `Changed`, `Fixed` and
`Known limitations` group the bullets under it for search and filtering.

## Notes

- Final quiz access unlocks once a student completes every published lesson in a course.
- Admins can preview the final quiz without completing the course.
- Certificates stay valid until revoked; public verification is at `/certificates/{number}`.

## License

MIT
