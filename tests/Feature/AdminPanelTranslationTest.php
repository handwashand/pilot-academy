<?php

namespace Tests\Feature;

use App\Actions\NotifyContentOwners;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\QuizAttempts\QuizAttemptResource;
use App\Filament\Widgets\StudentProgressOverview;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\LanguageSeeder;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The admin panel is in the admin's language: French and Russian trainers
 * work in it. Filament's own buttons are translated by Filament; everything
 * this app writes goes through __t().
 */
class AdminPanelTranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LanguageSeeder::class);
    }

    private function admin(string $locale): User
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => "admin-{$locale}@pilot.local",
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $admin->forceFill(['locale' => $locale])->save();

        return $admin;
    }

    public function test_the_menu_groups_and_items_follow_the_admins_language(): void
    {
        $this->actingAs($this->admin('ru'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Контент')
            ->assertSee('Люди')
            ->assertSee('Результаты')
            ->assertSee('Курсы')
            ->assertSee('Уроки')
            ->assertSee('Сертификаты')
            ->assertSee('Состояние контента');
    }

    public function test_an_english_admin_keeps_the_english_menu(): void
    {
        $this->actingAs($this->admin('en'))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Content')
            ->assertSee('Courses')
            ->assertSee('Content health')
            ->assertDontSee('Контент');
    }

    public function test_record_names_and_tabs_follow_the_language(): void
    {
        $course = Course::create(['title' => 'Pilot basics', 'slug' => 'pilot-basics', 'level' => 'beginner']);
        $admin = $this->admin('fr');

        $this->actingAs($admin)
            ->get(CourseResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Cours');

        $this->actingAs($admin)
            ->get(CourseResource::getUrl('edit', ['record' => $course]))
            ->assertOk()
            ->assertSee('Questions du quiz final')
            ->assertSee('Leçons');
    }

    public function test_the_dashboard_figures_are_in_the_admins_language(): void
    {
        $this->actingAs($this->admin('ru'));

        // Widgets load after the page, in their own Livewire request, where
        // SetLocale has already chosen the language.
        App::setLocale('ru');

        Livewire::test(StudentProgressOverview::class)
            ->assertSee('Активные обучающиеся')
            ->assertSee('Выдано сертификатов')
            ->assertDontSee('Active students');
    }

    public function test_columns_and_values_in_a_list_are_translated(): void
    {
        Course::create(['title' => 'Pilot basics', 'slug' => 'pilot-basics', 'level' => 'beginner']);

        $this->actingAs($this->admin('ru'))
            ->get(CourseResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Название')
            ->assertSee('Уровень')
            // The stored value "draft", named in Russian.
            ->assertSee('Черновик')
            ->assertDontSee('>Draft<', false);
    }

    public function test_a_french_trainer_writes_a_lesson_in_a_french_form(): void
    {
        $this->actingAs($this->admin('fr'))
            ->get(LessonResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Texte de la leçon')
            ->assertSee('Transcription de la vidéo')
            ->assertSee('Écrit en')
            ->assertDontSee('Video transcript')
            ->assertDontSee('Doc links');
    }

    public function test_headings_keep_a_record_name_in_lower_case(): void
    {
        $this->actingAs($this->admin('ru'));
        App::setLocale('ru');

        // Page headings.
        $this->get(QuizAttemptResource::getUrl('index'))->assertSee('Попытки тестов')->assertDontSee('Попытки Тестов');
        $this->get(CourseResource::getUrl('create'))->assertSee('Создать курс')->assertDontSee('Создать Курс');

        // Filament's own dialogs, which Title-Case the name unless told not to.
        foreach ([
            [CreateAction::make()->modelLabel('вопрос'), 'вопрос'],
            [AttachAction::make()->modelLabel('вопрос'), 'вопрос'],
            [DeleteBulkAction::make()->pluralModelLabel('курсы'), 'курсы'],
        ] as [$action, $label]) {
            $this->assertStringContainsString($label, (string) $action->getModalHeading());
            $this->assertStringNotContainsString(mb_convert_case($label, MB_CASE_TITLE), (string) $action->getModalHeading());
        }
    }

    public function test_a_broken_course_is_reported_to_its_owner_in_the_owners_language(): void
    {
        $owner = $this->admin('ru');
        $course = Course::create([
            'title' => 'Pilot basics',
            'slug' => 'pilot-basics',
            'level' => 'beginner',
            'status' => Course::STATUS_PUBLISHED,
        ]);

        app(NotifyContentOwners::class)->handle($course->id);

        $alert = $owner->notifications()->first();

        $this->assertNotNull($alert);
        $this->assertSame('Опубликованный курс без опубликованных уроков', $alert->data['title']);
        // The request's own language is put back afterwards.
        $this->assertSame('en', App::getLocale());
    }

    /**
     * A label written as English text in a panel class never changes language.
     * They go through __t() — see lang/en/admin_*.php.
     */
    public function test_no_panel_label_is_written_as_fixed_english_text(): void
    {
        $calls = 'label|helperText|placeholder|heading|description|title|body|tooltip|trueLabel|falseLabel'
            .'|modalHeading|modalDescription|modalSubmitActionLabel|modalCancelActionLabel|addActionLabel'
            .'|emptyStateHeading|emptyStateDescription';

        $offenders = [];

        foreach (File::allFiles(app_path('Filament')) as $file) {
            foreach (preg_split('/\R/', $file->getContents()) as $number => $line) {
                // An empty label counts too: Filament shows the field name instead.
                if (preg_match("/->({$calls})\\(\\s*['\"][A-Za-z]|->label\\(\\s*(''|\"\")\\s*\\)|(Section|Tab|Stat)::make\\(\\s*['\"][A-Z]/", $line)) {
                    $offenders[] = $file->getRelativePathname().':'.($number + 1).'  '.trim($line);
                }
            }
        }

        $this->assertSame([], $offenders);
    }
}
