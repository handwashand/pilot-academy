<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Who may use a saved token. A provider keeps one shared row (its switch, its
 * model, and optionally a shared token for all admins or for everyone with the
 * right) plus one personal row per admin who added a token just for themselves.
 *
 * Existing rows are shared with everyone, which is how they worked before.
 * A personal row goes with its owner's account; a shared row stays when the
 * admin who added it is deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropUnique(['provider']);
        });

        Schema::table('ai_providers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('provider')->constrained()->cascadeOnDelete();
            $table->string('scope', 20)->default('everyone')->after('user_id');
            $table->foreignId('added_by')->nullable()->after('scope')->constrained('users')->nullOnDelete();

            // One personal row per person per provider. Shared rows have no
            // user_id, and NULLs never clash in a unique index, so AiProvider
            // keeps those to one per provider itself.
            $table->unique(['provider', 'user_id']);
        });
    }

    public function down(): void
    {
        // Personal tokens have nowhere to go in the old one-row-per-provider shape.
        DB::table('ai_providers')->whereNotNull('user_id')->delete();

        Schema::table('ai_providers', function (Blueprint $table) {
            $table->dropUnique(['provider', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('added_by');
            $table->dropColumn('scope');
        });

        Schema::table('ai_providers', function (Blueprint $table) {
            $table->unique('provider');
        });
    }
};
