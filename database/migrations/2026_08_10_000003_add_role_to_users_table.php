<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the is_admin flag with a three-way role, so product owners can be
     * given content rights without full platform administration.
     *
     * Nobody's access changes at deploy time: today's admins stay admins and
     * everyone else becomes a learner, which is exactly what is_admin = false
     * already meant. Creators are only ever assigned by hand afterwards.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('learner')->after('company_id');
        });

        DB::table('users')->where('is_admin', true)->update(['role' => 'admin']);
        DB::table('users')->where('is_admin', false)->orWhereNull('is_admin')->update(['role' => 'learner']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        DB::table('users')->update(['is_admin' => false]);
        DB::table('users')->where('role', 'admin')->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
