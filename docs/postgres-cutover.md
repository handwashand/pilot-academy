# Moving production from SQLite to PostgreSQL

A one-time cut-over for a server still running the old `database.sqlite` file.
Read it through once before starting — steps 3 and 4 are what make it reversible.

Budget **15–30 minutes** of downtime for a database of this size. Almost all of
it is the smoke test at the end.

The app is not dialect-specific: there is no raw SQL anywhere in it, so nothing
in the code changes. Only the connection and the data move.

## Before the window

**1. Install the PHP extension.** Nothing works without it:

```bash
sudo apt install php8.4-pgsql
php8.4 -m | grep pdo_pgsql        # must print pdo_pgsql
```

**2. Create the database and role:**

```bash
sudo -u postgres createuser --pwprompt pilot
sudo -u postgres createdb --owner=pilot pilot_academy
```

**3. Confirm the app code is deployed** — `git pull` on `laravel` so that
`db:copy-to-pgsql` exists:

```bash
cd /var/www/pilot-academy
git pull
composer install --no-dev --optimize-autoloader
php8.4 artisan list | grep copy-to-pgsql
```

Nothing so far touches the live database. The site is still up and on SQLite.

## The window

**4. Maintenance mode:**

```bash
php8.4 artisan down
```

**5. Back up the SQLite file.** This is the rollback:

```bash
cp database/database.sqlite "database/database.sqlite.bak-$(date +%F-%H%M)"
```

**6. Point `.env` at PostgreSQL.** Keep a note of the old values.

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pilot_academy
DB_USERNAME=pilot
DB_PASSWORD=<the password from step 2>
```

Then clear the cached config so the new connection is actually used:

```bash
php8.4 artisan config:clear
```

**7. Build the schema.** The migrations own it — do not import a dump:

```bash
php8.4 artisan migrate --force
```

**8. Copy the data:**

```bash
php8.4 artisan db:copy-to-pgsql --source=database/database.sqlite
```

It prints a row count per table, resets the id sequences, then compares every
table against the source and **exits non-zero if any count differs**. A clean
run ends with `All 14 tables match.`

If it reports a mismatch, stop and go to *Rolling back*. Do not bring the site
up on a database it has told you not to trust.

**9. Re-cache and come back up:**

```bash
php8.4 artisan optimize
php8.4 artisan up
```

## Smoke test

Work through all of these — each covers something the others do not:

- [ ] Student log in on the public site.
- [ ] Open a course, then a lesson. Video and text render.
- [ ] Submit a lesson quiz and get the pass screen. *(Writes to the database.)*
- [ ] Admin log in at `/admin`.
- [ ] **Courses** — Status badges read Draft/Published/Archived, not blank.
      *(Confirms the status strings copied intact.)*
- [ ] **Products** — products are listed with their creators.
      *(Confirms `product_user` came across; if this is empty, every Creator has
      silently lost access to their courses.)*
- [ ] **Users** — the Admins / Creators / Learners tabs each show the right people.
- [ ] Take a final quiz through to a certificate, and download the PDF.
      *(Confirms JSON `question_ids` survived and sequences are correct.)*
- [ ] Create a new course, then delete it. *(Proves the id sequence was reset —
      without step 8's sequence reset this fails on a duplicate key.)*

## Rolling back

Safe at any point. Put the old connection settings back:

```bash
php8.4 artisan down
# restore the SQLite lines in .env
php8.4 artisan config:clear && php8.4 artisan optimize
php8.4 artisan up
```

The SQLite file is untouched by the copy — it is only ever read. Anything a user
did *after* the cut-over lives only in PostgreSQL and is lost on rollback, which
is why the window should be short and out of hours.

## After a successful cut-over

- Keep `database.sqlite` and its backup for a couple of weeks, then remove them.
- Add PostgreSQL to whatever backs the server up. `pg_dump -Fc pilot_academy`
  replaces "copy the file" — see `DEPLOY.md`.
- Everyone is signed out, because sessions live in the database and did not come
  across. That is expected.

## Notes on what the copy does

- **Booleans.** SQLite stores 0/1; PostgreSQL rejects an integer in a boolean
  column. The command reads the boolean columns off the *target* schema and
  casts them, rather than working from a hard-coded list that would go stale the
  next time a migration adds or drops a flag.
- **JSON.** `lessons.doc_links` and `quiz_attempts.question_ids` are valid JSON
  text and are inserted as-is. Re-encoding them would double-escape.
- **Order.** Tables are copied parents-first so foreign keys always resolve.
- **Left behind on purpose.** `sessions`, `cache`, `cache_locks`, `jobs`,
  `job_batches`, `failed_jobs`, `password_reset_tokens` — all transient. The
  migrations create them empty.
