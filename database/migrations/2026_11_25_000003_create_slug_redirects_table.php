<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Old addresses of courses and lessons. A slug that is changed is remembered
 * here, and a link that still uses it is sent on to the new address
 * (App\Models\Concerns\RemembersOldSlugs) instead of a 404 — shared links,
 * bookmarks and emails keep working after a rename (owner, 2026-11-25).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('model_type', 20); // course | lesson
            $table->unsignedBigInteger('model_id');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['model_type', 'slug']);
            $table->index(['model_type', 'model_id']);
        });

        // The introduction course was live as "pilot-introduction" for a while
        // on 2026-11-25 before going back to "pilot-monitoring": links shared
        // in between follow it. Nothing is added where neither slug exists.
        $course = DB::table('courses')->where('slug', 'pilot-monitoring')->value('id');

        if ($course && ! DB::table('courses')->where('slug', 'pilot-introduction')->exists()) {
            DB::table('slug_redirects')->insert([
                'model_type' => 'course',
                'model_id' => $course,
                'slug' => 'pilot-introduction',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
