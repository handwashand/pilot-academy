<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Descript's ids are not all UUIDs: a job is "project-media-import-<uuid>".
 * The columns were uuid, which PostgreSQL enforces and SQLite does not, so on
 * production every video translation stopped the moment its import job id was
 * saved (Sentry PILOT-ACADEMY-3, 2026-11-25) while the suite passed. They are
 * plain strings now. No data changes: a UUID reads the same as text.
 */
return new class extends Migration
{
    /** @var array<string, array<int, string>> */
    private array $columns = [
        'descript_imports' => ['project_id', 'job_id'],
        'video_translations' => ['job_id', 'composition_id'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->string($column)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $columns) {
            foreach ($columns as $column) {
                // A uuid column cannot hold the job ids saved since: clear them
                // first, or the rollback itself fails on PostgreSQL.
                if (DB::getDriverName() === 'pgsql') {
                    DB::table($table)
                        ->whereNotNull($column)
                        ->whereRaw("{$column} !~* '^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$'")
                        ->update([$column => null]);
                    DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE uuid USING {$column}::uuid");
                } else {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->uuid($column)->nullable()->change());
                }
            }
        }
    }
};
