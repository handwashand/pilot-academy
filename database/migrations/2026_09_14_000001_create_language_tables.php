<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('name');
            $table->string('native_name');
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->string('direction', 3)->default('ltr');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX languages_one_default ON languages (is_default) WHERE is_default = true');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX languages_one_default ON languages (is_default) WHERE is_default = 1');
        }

        Schema::create('translations', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->string('module')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['key', 'language_id']);
        });

        Schema::create('translation_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('translation_id')->constrained()->cascadeOnDelete();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('content_translations', function (Blueprint $table): void {
            $table->id();
            $table->morphs('translatable');
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->longText('value');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['translatable_type', 'translatable_id', 'language_id', 'field'], 'content_translations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_translations');
        Schema::dropIfExists('translation_revisions');
        Schema::dropIfExists('translations');
        Schema::dropIfExists('languages');
    }
};
