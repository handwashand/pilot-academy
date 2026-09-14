<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasColumn('notifications', 'data')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement("alter table notifications alter column data type json using data::json"),
            default => null,
        };
    }

    public function down(): void
    {
        if (! Schema::hasTable('notifications') || ! Schema::hasColumn('notifications', 'data')) {
            return;
        }

        match (DB::getDriverName()) {
            'pgsql' => DB::statement("alter table notifications alter column data type text using data::text"),
            default => null,
        };
    }
};
