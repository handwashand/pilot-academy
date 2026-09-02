# Pilot Academy

**Pilot Academy is a certification engine for Pilot's partner ecosystem.** Pilot is a white-label fleet and telematics SaaS sold through partners, so the quality of every end-customer experience depends on how capable the partner's team is.

The Academy trains and certifies partner staff, then certified partners train their own fleet-owner customers. Partner staff are the **multiplier** and certification is the **quality gate** that protects every customer downstream.

## Overview

Pilot Academy is built as a Laravel 13 application with a public student site and a Filament-powered admin panel.

Public student features:

- course catalog and lesson browsing
- video lessons, written content, and knowledge-check quizzes
- timed/attempt-limited lesson quizzes when configured
- final course assessment and certificate issuance
- certificate download and public verification by certificate number

Admin features:

- manage courses, lessons, questions, and final quiz banks
- configure pass thresholds, attempt limits, and certificate templates
- issue, revoke, resend, and regenerate certificates
- manage partner companies and student accounts

## Built with

- Laravel 13
- Filament admin panel
- PostgreSQL (a `db` service is included in `docker-compose.yml`)
- Tailwind CSS and Vite
- DOMPDF for generating certificate PDFs
- simple-qrcode for QR codes on certificates

## Getting started

### Requirements

- PHP 8.4
- Composer
- Node.js and npm
- PostgreSQL 17
- Docker is optional but supported via `docker-compose.yml`

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

Open the public site at `http://127.0.0.1:8000`.

### Run with Docker

A `Dockerfile` and `docker-compose.yml` are included, so PHP 8.4 and the
extensions are not needed on the host.

```bash
docker compose up --build
```

Open the public site at `http://localhost:8000` and the admin panel at
`http://localhost:8000/admin`. On first boot the container writes its `.env`,
generates the app key, migrates, and seeds the demo course plus an admin login
(`admin@pilot.local` / `password`).

No npm step is needed: the Vite bundle committed in `public/build/` is served
as-is. The PostgreSQL data and uploaded media live in named volumes, so they
survive `docker compose down` — add `-v` to start from a clean slate.

## App structure

- `app/Http/Controllers/AcademyController.php` — public student flows, course and lesson pages, lesson quiz flow, completion tracking
- `app/Http/Controllers/FinalQuizController.php` — final course assessment and certificate issuance
- `app/Http/Controllers/CertificateController.php` — certificate listing, download, and public verification
- `app/Http/Controllers/Auth/StudentAuthController.php` — student login, registration, join flow, passwordless magic-link access
- `app/Models/Course.php` — course configuration, published lesson access, final quiz unlock rules
- `app/Models/Lesson.php` — lesson content, video/image URL helpers, quiz limit helpers
- `app/Models/Certificate.php` — certificate validity, PDF path, verification URL
- `app/Models/User.php` — student/admin accounts, login token access links, completed lessons, certificates, activity logging

## Student user flow

1. Register or join from the public site
2. Browse published courses
3. Open a lesson, watch video content, and complete the knowledge check quiz
4. Finish every lesson in a course to unlock the final assessment
5. Start the final quiz, pass, and receive a certificate
6. Download the certificate PDF from the student account

## Admin flow

1. Log in to the Filament admin panel at `/admin`
2. Create courses and publish them when ready
3. Add lessons, media, and quiz questions for each course
4. Enable the final quiz and configure pass percent, attempt limits, and certificate template
5. Add partner companies and student users as needed
6. Review issued certificates, revoke or regenerate them, and verify certificate numbers

## Testing

Run the Laravel test suite locally:

```bash
composer test
```

Or run the tests inside Docker:

```bash
docker compose run --rm app php artisan test
```

## Docs

- Admin guide: `docs/admin-guide.md`
- Change log: `docs/CHANGELOG.md`

## Notes

- Final quiz access is unlocked once a student completes all published lessons in a course.
- Admin users can preview the final quiz without completing the course.
- Certificates are permanent until revoked; public verification is available at `/certificates/{number}`.

## License

MIT
