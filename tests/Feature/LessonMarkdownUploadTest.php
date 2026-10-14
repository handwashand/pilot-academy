<?php

namespace Tests\Feature;

use App\Actions\ReadLessonMarkdown;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A lesson's text filled from a Markdown file, from the button on the lesson
 * form's text box.
 */
class LessonMarkdownUploadTest extends TestCase
{
    use RefreshDatabase;

    private const FILE = "# Creating a contract\n\nOpen the **Account** tab.\n\n## Steps\n\n- Click *Add New*\n- Fill in the fields\n\n[Docs](https://example.com/docs)\n";

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function file(string $contents, string $name = 'lesson.md'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function upload(): TestAction
    {
        return TestAction::make('importMarkdown')->schemaComponent('content');
    }

    public function test_the_first_heading_is_the_title_and_the_rest_is_formatted_text(): void
    {
        $lesson = app(ReadLessonMarkdown::class)->handle("\xEF\xBB\xBF".str_replace("\n", "\r\n", self::FILE));

        $this->assertSame('Creating a contract', $lesson['title']);
        $this->assertStringNotContainsString('Creating a contract', $lesson['html']);
        $this->assertStringContainsString('<strong>Account</strong>', $lesson['html']);
        $this->assertStringContainsString('<h2>Steps</h2>', $lesson['html']);
        $this->assertStringContainsString('<li>Click <em>Add New</em></li>', $lesson['html']);
        $this->assertStringContainsString('<a href="https://example.com/docs">Docs</a>', $lesson['html']);
    }

    public function test_raw_html_and_unsafe_links_in_the_file_do_not_reach_the_lesson(): void
    {
        $lesson = app(ReadLessonMarkdown::class)->handle("Hello <script>alert(1)</script> <img src=x onerror=alert(1)>\n\n[click](javascript:alert(1))");

        $this->assertStringNotContainsString('<script', $lesson['html']);
        $this->assertStringNotContainsString('onerror', $lesson['html']);
        $this->assertStringNotContainsString('javascript:', $lesson['html']);
    }

    public function test_a_file_that_is_not_text_is_refused(): void
    {
        $this->assertNull(app(ReadLessonMarkdown::class)->handle("PK\x03\x04\x00\x00binary\xFF\xFE"));
    }

    public function test_uploading_on_a_new_lesson_fills_the_text_the_title_and_the_address_and_it_saves(): void
    {
        Livewire::actingAs($this->admin())
            ->test(CreateLesson::class)
            ->callAction($this->upload(), ['file' => $this->file(self::FILE)])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['title' => 'Creating a contract', 'slug' => 'creating-a-contract'])
            ->call('create')
            ->assertHasNoFormErrors();

        $lesson = Lesson::where('slug', 'creating-a-contract')->firstOrFail();

        $this->assertStringContainsString('<strong>Account</strong>', $lesson->content);
        $this->assertStringContainsString('Steps', $lesson->content);
        $this->assertStringContainsString('Fill in the fields', $lesson->content);
    }

    public function test_uploading_on_an_existing_lesson_replaces_the_text_and_keeps_its_title(): void
    {
        $course = Course::create(['title' => 'Course A', 'slug' => 'course-a', 'level' => 'beginner']);
        $lesson = $course->lessons()->create(['title' => 'My own title', 'slug' => 'my-own', 'content' => '<p>Old text.</p>']);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction($this->upload(), ['file' => $this->file(self::FILE, 'lesson.txt')])
            ->assertHasNoActionErrors()
            ->assertSchemaStateSet(['title' => 'My own title', 'slug' => 'my-own'])
            ->call('save')
            ->assertHasNoFormErrors();

        $lesson->refresh();

        $this->assertSame('My own title', $lesson->title);
        $this->assertStringNotContainsString('Old text', $lesson->content);
        $this->assertStringContainsString('<strong>Account</strong>', $lesson->content);
    }

    public function test_nothing_is_stored_until_the_lesson_is_saved(): void
    {
        $course = Course::create(['title' => 'Course A', 'slug' => 'course-a', 'level' => 'beginner']);
        $lesson = $course->lessons()->create(['title' => 'My own title', 'slug' => 'my-own', 'content' => '<p>Old text.</p>']);

        Livewire::actingAs($this->admin())
            ->test(EditLesson::class, ['record' => $lesson->getRouteKey()])
            ->callAction($this->upload(), ['file' => $this->file(self::FILE)]);

        $this->assertSame('<p>Old text.</p>', $lesson->fresh()->content);
    }
}
