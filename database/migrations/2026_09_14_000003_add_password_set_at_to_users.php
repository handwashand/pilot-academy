<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When the account's password became one its owner actually knows.
 *
 * Students who join through an invite link get a random password nobody knows
 * and sign in with their personal link. The profile page must not ask them for
 * a "current password" they never had, so it needs to tell the two apart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('password_set_at')->nullable()->after('password');
        });

        // Existing accounts, best evidence available. Staff passwords are set
        // by an admin, and a student without a personal-link token registered
        // with a password or was given one. A student *with* a token may have
        // joined by link and never had a password — left unset, so they are
        // offered "Set a password" rather than locked out by a current-password
        // check. They are already signed in, so this grants nothing new.
        DB::table('users')
            ->where(fn ($query) => $query->whereIn('role', ['admin', 'creator'])->orWhereNull('login_token'))
            ->update(['password_set_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_set_at');
        });
    }
};
