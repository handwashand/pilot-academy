<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cover_media_item_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->foreignId('diagram_media_item_id')->nullable()->constrained('media_items')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('short_problem')->nullable();
            $table->string('industry')->nullable();
            $table->json('features_used')->nullable();
            $table->string('difficulty')->default('intermediate');
            $table->string('implementation_time')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('scenario_problem')->nullable();
            $table->text('desired_outcome')->nullable();
            $table->text('prerequisites')->nullable();
            $table->text('pilot_features')->nullable();
            $table->text('configuration_steps')->nullable();
            $table->text('testing_verification')->nullable();
            $table->text('expected_results')->nullable();
            $table->text('troubleshooting')->nullable();
            $table->text('adaptation')->nullable();
            $table->json('related_lesson_ids')->nullable();
            $table->json('related_links')->nullable();
            $table->text('source_note')->nullable();
            $table->text('performance_claim_note')->nullable();
            $table->boolean('is_anonymized')->default(true);
            $table->boolean('is_customer_approved')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'sort_order']);
            $table->index(['industry', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_studies');
    }
};
