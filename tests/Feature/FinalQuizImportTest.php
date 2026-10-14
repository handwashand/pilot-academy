<?php

namespace Tests\Feature;

use App\Actions\ImportFinalQuizQuestions;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\RelationManagers\FinalQuestionsRelationManager;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/**
 * A course's final quiz uploaded as one Excel file: every row a question, all
 * of them saved or none.
 */
class FinalQuizImportTest extends TestCase
{
    use RefreshDatabase;

    private const HEADINGS = ['Question', 'Answer A', 'Answer B', 'Answer C', 'Answer D', 'Answer E', 'Answer F', 'Right answer(s)'];

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@pilot.local'], [
            'name' => 'Pilot Admin',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    private function course(string $language = 'en'): Course
    {
        return Course::create([
            'title' => 'Course A',
            'slug' => 'course-a',
            'level' => 'beginner',
            'language' => $language,
            'status' => Course::STATUS_PUBLISHED,
        ]);
    }

    /** An .xlsx file with the heading row and these rows under it. */
    private function sheet(array ...$rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'quiz-test').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(self::HEADINGS));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }

        $writer->close();

        return $path;
    }

    private function import(Course $course, string $path): array
    {
        return app(ImportFinalQuizQuestions::class)->handle($course, $path);
    }

    public function test_each_row_becomes_a_course_only_question_in_the_bank(): void
    {
        $course = $this->course('fr');

        $result = $this->import($course, $this->sheet(
            ['Which is a colour?', 'Blue', 'Chair', '', '', '', '', 'a'],
            ['Which are colours?', 'Blue', 'Chair', 'Green', '', '', '', 'A, C'],
        ));

        $this->assertSame(['added' => 2, 'skipped' => 0, 'errors' => []], $result);

        $bank = $course->finalQuestions()->with('options')->get();
        $this->assertSame(['Which is a colour?', 'Which are colours?'], $bank->pluck('prompt')->all(), 'In the order of the file.');
        $this->assertSame([Question::TYPE_SINGLE, Question::TYPE_MULTIPLE], $bank->pluck('type')->all());
        $this->assertSame([null, null], $bank->pluck('lesson_id')->all());
        $this->assertSame(['fr', 'fr'], $bank->pluck('language')->all(), 'Written in the course’s language.');
        $this->assertSame(['Blue', 'Green'], $bank[1]->options->where('is_correct', true)->pluck('text')->all());
        $this->assertSame(['Chair'], $bank[1]->options->where('is_correct', false)->pluck('text')->all());
    }

    public function test_one_bad_row_saves_nothing_and_every_mistake_is_named_by_its_row(): void
    {
        $course = $this->course();

        $result = $this->import($course, $this->sheet(
            ['A good question', 'Yes', 'No', '', '', '', '', 'A'],
            ['', 'Yes', 'No', '', '', '', '', 'A'],
            ['Only one answer', 'Yes', '', '', '', '', '', 'A'],
            ['No right answer', 'Yes', 'No', '', '', '', '', ''],
            ['Right answer is an empty box', 'Yes', 'No', '', '', '', '', 'C'],
            ['An answer too long', 'Yes', str_repeat('x', 256), '', '', '', '', 'A'],
        ));

        $this->assertSame(0, $result['added']);
        $this->assertCount(5, $result['errors']);
        $this->assertStringStartsWith('Row 3:', $result['errors'][0]);
        $this->assertStringStartsWith('Row 7:', $result['errors'][4]);
        $this->assertSame(0, Question::count());
    }

    public function test_questions_already_in_the_bank_are_left_out_so_a_file_can_be_uploaded_twice(): void
    {
        $course = $this->course();
        $path = $this->sheet(
            ['Which is a colour?', 'Blue', 'Chair', '', '', '', '', 'A'],
            ['which is a  colour?', 'Blue', 'Chair', '', '', '', '', 'A'],
        );

        $this->assertSame(['added' => 1, 'skipped' => 1, 'errors' => []], $this->import($course, $path));
        $this->assertSame(['added' => 0, 'skipped' => 2, 'errors' => []], $this->import($course, $path));
        $this->assertSame(1, $course->finalQuestions()->count());
    }

    public function test_a_number_in_a_cell_is_read_as_the_text_a_student_sees(): void
    {
        $course = $this->course();

        $this->import($course, $this->sheet(['How many wheels?', 4, 6, '', '', '', '', 'A']));

        $this->assertSame(['4', '6'], $course->finalQuestions()->first()->options->pluck('text')->all());
    }

    public function test_a_file_that_is_not_a_workbook_or_has_no_questions_is_refused(): void
    {
        $course = $this->course();
        $notExcel = tempnam(sys_get_temp_dir(), 'quiz-test').'.xlsx';
        file_put_contents($notExcel, 'not a workbook');

        $this->assertCount(1, $this->import($course, $notExcel)['errors']);
        $this->assertCount(1, $this->import($course, $this->sheet())['errors']);
        $this->assertSame(0, Question::count());
    }

    public function test_the_example_file_uploads_as_it_is(): void
    {
        $course = $this->course();
        $importer = app(ImportFinalQuizQuestions::class);

        $result = $importer->handle($course, $importer->template());

        $this->assertSame(['added' => 2, 'skipped' => 0, 'errors' => []], $result);
        $this->assertSame([Question::TYPE_SINGLE, Question::TYPE_MULTIPLE], $course->finalQuestions()->pluck('type')->all());
    }

    public function test_an_editor_uploads_the_file_from_the_final_questions_tab(): void
    {
        $course = $this->course();
        $file = UploadedFile::fake()->createWithContent(
            'quiz.xlsx',
            file_get_contents($this->sheet(['Which is a colour?', 'Blue', 'Chair', '', '', '', '', 'A'])),
        );

        Livewire::actingAs($this->admin())
            ->test(FinalQuestionsRelationManager::class, ['ownerRecord' => $course, 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('importFinalQuestions')->table(), ['file' => $file])
            ->assertHasNoActionErrors();

        $this->assertSame(['Which is a colour?'], $course->finalQuestions()->pluck('prompt')->all());
    }

    public function test_the_example_file_downloads_from_the_final_questions_tab(): void
    {
        Livewire::actingAs($this->admin())
            ->test(FinalQuestionsRelationManager::class, ['ownerRecord' => $this->course(), 'pageClass' => EditCourse::class])
            ->callAction(TestAction::make('downloadFinalQuestionsExample')->table())
            ->assertFileDownloaded('final-quiz-example.xlsx');
    }
}
