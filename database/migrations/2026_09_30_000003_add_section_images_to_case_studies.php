<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pictures that belong to one step of a case study, keyed by the step: a
 * screenshot of the GeoZone being drawn sits under "Step-by-step
 * configuration", not at the top of the page with the cover.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_studies', function (Blueprint $table): void {
            $table->json('section_images')->nullable()->after('adaptation');
        });
    }

    public function down(): void
    {
        Schema::table('case_studies', function (Blueprint $table): void {
            $table->dropColumn('section_images');
        });
    }
};
