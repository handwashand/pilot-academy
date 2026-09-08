<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Exercises db:copy-to-pgsql against a throwaway SQLite file standing in for
 * the old production database. That covers the ordering, chunking, boolean
 * casting, JSON passthrough and count check. The Postgres-only part — resetting
 * the id sequences — is skipped by the command on a non-Postgres target and is
 * verified by hand during the cut-over instead.
 */
class CopyToPgsqlTest extends TestCase
{
    use RefreshDatabase;

    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = tempnam(sys_get_temp_dir(), 'legacy').'.sqlite';
        touch($this->source);

        config(['database.connections.legacy' => [
            'driver' => 'sqlite',
            'database' => $this->source,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
    }

    protected function tearDown(): void
    {
        @unlink($this->source);
        parent::tearDown();
    }

    /** Build the old database: same schema, then a few rows of real content. */
    private function seedLegacyDatabase(): void
    {
        $this->artisan('migrate', ['--database' => 'legacy', '--force' => true])->run();

        $legacy = DB::connection('legacy');

        $legacy->table('products')->insert([
            'id' => 1, 'name' => 'GARM', 'slug' => 'garm',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacy->table('users')->insert([
            'id' => 1, 'name' => 'Creator', 'email' => 'creator@pilot.local',
            'password' => 'x', 'role' => User::ROLE_CREATOR,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacy->table('product_user')->insert([
            'id' => 1, 'product_id' => 1, 'user_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacy->table('courses')->insert([
            'id' => 1, 'product_id' => 1, 'title' => 'Understanding GARM',
            'slug' => 'understanding-garm', 'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
            'final_quiz_enabled' => 1,      // SQLite boolean: an integer
            'pass_percent' => 80, 'sort_order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacy->table('lessons')->insert([
            'id' => 1, 'course_id' => 1, 'title' => 'Lesson one',
            'slug' => 'lesson-one', 'status' => Lesson::STATUS_PUBLISHED,
            'doc_links' => '[{"title":"Manual","url":"https://example.com"}]',
            'sort_order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacy->table('questions')->insert([
            'id' => 1, 'lesson_id' => 1, 'prompt' => 'Which one?',
            'sort_order' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $legacy->table('options')->insert([
            ['id' => 1, 'question_id' => 1, 'text' => 'Right', 'is_correct' => 1, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'question_id' => 1, 'text' => 'Wrong', 'is_correct' => 0, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $legacy->table('quiz_attempts')->insert([
            'id' => 1, 'user_id' => 1, 'course_id' => 1,
            'question_ids' => '[1]', 'status' => 'passed',
            'started_at' => now(), 'submitted_at' => now(),
            'score' => 1, 'total' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_it_copies_every_table_and_reports_matching_counts(): void
    {
        $this->seedLegacyDatabase();

        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])
            ->expectsOutputToContain('All 14 tables match.')
            ->assertSuccessful();

        $this->assertSame(1, Product::count());
        $this->assertSame(1, User::count());
        $this->assertSame(1, Course::count());
        $this->assertSame(2, DB::table('options')->count());
        $this->assertSame(1, DB::table('product_user')->count());
    }

    public function test_booleans_survive_as_booleans(): void
    {
        $this->seedLegacyDatabase();
        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])->assertSuccessful();

        // Read through Eloquent, which is how the app sees them.
        $this->assertTrue(Course::find(1)->final_quiz_enabled);
        $this->assertTrue((bool) DB::table('options')->find(1)->is_correct);
        $this->assertFalse((bool) DB::table('options')->find(2)->is_correct);
    }

    public function test_json_columns_are_not_double_encoded(): void
    {
        $this->seedLegacyDatabase();
        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])->assertSuccessful();

        // The array cast only decodes cleanly if the value was stored once.
        $this->assertSame(
            [['title' => 'Manual', 'url' => 'https://example.com']],
            Lesson::find(1)->doc_links,
        );
        $this->assertSame([1], DB::table('quiz_attempts')->find(1)->question_ids
            ? json_decode(DB::table('quiz_attempts')->find(1)->question_ids, true)
            : []);
    }

    public function test_creator_product_assignments_survive(): void
    {
        $this->seedLegacyDatabase();
        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])->assertSuccessful();

        // The pivot is the one table whose loss would be silent.
        $creator = User::find(1);
        $this->assertTrue($creator->isCreator());
        $this->assertTrue($creator->ownsProduct(Product::find(1)));
        $this->assertTrue($creator->canManageCourse(Course::find(1)));
    }

    public function test_it_refuses_to_run_against_a_database_that_already_has_data(): void
    {
        $this->seedLegacyDatabase();
        Product::create(['name' => 'Existing', 'slug' => 'existing']);

        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])
            ->expectsOutputToContain('Target already holds data')
            ->assertFailed();

        // Nothing was copied on top of it.
        $this->assertSame(1, Product::count());
    }

    public function test_truncate_allows_a_repeat_run(): void
    {
        $this->seedLegacyDatabase();
        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])->assertSuccessful();

        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source, '--truncate' => true])
            ->assertSuccessful();

        $this->assertSame(1, Course::count());
    }

    public function test_it_fails_when_the_source_file_is_missing(): void
    {
        $this->artisan('db:copy-to-pgsql', ['--source' => '/nope/missing.sqlite'])
            ->expectsOutputToContain('No SQLite file at')
            ->assertFailed();
    }

    public function test_it_fails_when_the_target_has_not_been_migrated(): void
    {
        $this->seedLegacyDatabase();
        Schema::drop('product_user');

        $this->artisan('db:copy-to-pgsql', ['--source' => $this->source])
            ->expectsOutputToContain('Target is missing tables')
            ->assertFailed();
    }
}
