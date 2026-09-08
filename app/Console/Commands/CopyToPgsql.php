<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-off data move from the old SQLite file into the freshly migrated
 * database. The schema is built by the migrations, not by this command — it
 * only carries rows across, so column types stay whatever the migrations say.
 *
 *     php artisan db:copy-to-pgsql --source=/var/www/pilot-academy/database/database.sqlite
 */
class CopyToPgsql extends Command
{
    protected $signature = 'db:copy-to-pgsql
        {--source= : Path to the old database.sqlite (defaults to database/database.sqlite)}
        {--truncate : Empty the target tables first. Never use this on production data.}';

    protected $description = 'Copy every row from the old SQLite database into the current connection';

    /**
     * Parents before children, so foreign keys always resolve. Anything not
     * listed here is deliberately left behind: sessions, cache, jobs and
     * password resets are all transient, and users simply sign in again.
     */
    private const TABLES = [
        'companies',
        'products',
        'users',
        'product_user',
        'media_items',
        'courses',
        'lessons',
        'questions',
        'options',
        'course_final_questions',
        'quiz_attempts',
        'certificates',
        'activity_events',
        'lesson_user',
    ];

    private const CHUNK = 500;

    /** Connection name for the old file — deliberately not 'sqlite'. */
    private const SOURCE = 'copy_source';

    public function handle(): int
    {
        $source = $this->option('source') ?: database_path('database.sqlite');

        if (! is_file($source)) {
            $this->error("No SQLite file at {$source}");

            return self::FAILURE;
        }

        // Register the old file under a name of its own. Re-pointing the
        // shared "sqlite" connection would drag whatever else is using it —
        // the test suite runs on sqlite :memory: — along with it.
        config(['database.connections.'.self::SOURCE => [
            'driver' => 'sqlite',
            'database' => $source,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge(self::SOURCE);

        $from = DB::connection(self::SOURCE);
        $to = DB::connection();

        if ($to->getDriverName() === 'sqlite' && $to->getConfig('database') === $source) {
            $this->error('Source and target are the same database.');

            return self::FAILURE;
        }

        $this->line("Source: {$source}");
        $this->line('Target: '.$to->getName().' ('.$to->getDriverName().')');
        $this->newLine();

        if (! $this->prepareTarget()) {
            return self::FAILURE;
        }

        $counts = [];

        foreach (self::TABLES as $table) {
            if (! Schema::connection(self::SOURCE)->hasTable($table)) {
                $this->warn("  {$table}: not in the source, skipped");

                continue;
            }

            $counts[$table] = $this->copyTable($from, $to, $table);
        }

        $this->newLine();
        $this->resetSequences($to, array_keys($counts));

        return $this->report($from, $to, $counts);
    }

    /** Refuse to run unless the target has been migrated and is empty. */
    private function prepareTarget(): bool
    {
        $missing = array_values(array_filter(
            self::TABLES,
            fn (string $table): bool => ! Schema::hasTable($table),
        ));

        if ($missing !== []) {
            $this->error('Target is missing tables: '.implode(', ', $missing));
            $this->line('Run `php artisan migrate --force` against the new database first.');

            return false;
        }

        $occupied = array_values(array_filter(
            self::TABLES,
            fn (string $table): bool => DB::table($table)->exists(),
        ));

        if ($occupied === []) {
            return true;
        }

        if (! $this->option('truncate')) {
            $this->error('Target already holds data in: '.implode(', ', $occupied));
            $this->line('Copy into an empty database, or pass --truncate off production.');

            return false;
        }

        // Children first, so foreign keys never block the delete.
        foreach (array_reverse(self::TABLES) as $table) {
            DB::table($table)->delete();
        }

        $this->warn('Target tables emptied (--truncate).');
        $this->newLine();

        return true;
    }

    /** Copy one table in chunks, casting each row to the target's column types. */
    private function copyTable($from, $to, string $table): int
    {
        $booleans = $this->booleanColumns($table);
        $copied = 0;

        $to->transaction(function () use ($from, $to, $table, $booleans, &$copied): void {
            $from->table($table)->orderBy('id')->chunk(self::CHUNK, function ($rows) use ($to, $table, $booleans, &$copied): void {
                $batch = [];

                foreach ($rows as $row) {
                    $values = (array) $row;

                    // SQLite hands back 0/1; Postgres will not accept an
                    // integer in a boolean column. JSON and timestamps are
                    // already valid text and go across untouched.
                    foreach ($booleans as $column) {
                        if (array_key_exists($column, $values) && $values[$column] !== null) {
                            $values[$column] = (bool) $values[$column];
                        }
                    }

                    $batch[] = $values;
                }

                $to->table($table)->insert($batch);
                $copied += count($batch);
            });
        });

        $this->line(sprintf('  %-24s %6d rows', $table, $copied));

        return $copied;
    }

    /**
     * Read the boolean columns off the target schema rather than hard-coding
     * them. A hand-maintained list goes stale the moment a migration adds or
     * drops a flag, and the failure is silent.
     *
     * @return list<string>
     */
    private function booleanColumns(string $table): array
    {
        return collect(Schema::getColumns($table))
            ->filter(fn (array $column): bool => in_array(
                strtolower($column['type_name']),
                ['bool', 'boolean', 'tinyint'],
                true,
            ))
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * Rows arrive with their original ids, which leaves each sequence still at
     * 1. Without this the next insert collides on the primary key.
     */
    private function resetSequences($to, array $tables): void
    {
        if ($to->getDriverName() !== 'pgsql') {
            $this->line('Target is not Postgres — no sequences to reset.');

            return;
        }

        foreach ($tables as $table) {
            $to->statement(
                "SELECT setval(pg_get_serial_sequence(?, 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1))",
                [$table],
            );
        }

        $this->line('Sequences reset to MAX(id).');
    }

    /** Compare every table row for row, and fail the command on any mismatch. */
    private function report($from, $to, array $counts): int
    {
        $rows = [];
        $mismatched = 0;

        foreach ($counts as $table => $copied) {
            $before = $from->table($table)->count();
            $after = $to->table($table)->count();
            $ok = $before === $after;
            $mismatched += $ok ? 0 : 1;

            $rows[] = [$table, $before, $after, $ok ? 'ok' : 'MISMATCH'];
        }

        $this->newLine();
        $this->table(['Table', 'SQLite', 'Target', ''], $rows);

        if ($mismatched > 0) {
            $this->error("{$mismatched} table(s) did not match. The target is not trustworthy — investigate before going live.");

            return self::FAILURE;
        }

        $this->info('All '.count($rows).' tables match.');

        return self::SUCCESS;
    }
}
