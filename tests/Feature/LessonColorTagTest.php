<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\Lesson;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LessonColorTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_color_tag_options_are_red_yellow_and_blue(): void
    {
        $this->assertSame(
            ['red', 'yellow', 'blue'],
            array_keys(Lesson::colorTagLabels()),
        );
        $this->assertSame(
            ['Red', 'Yellow', 'Blue'],
            array_values(Lesson::colorTagLabels()),
        );
    }

    public function test_lesson_form_labels_the_field_as_a_color_tag(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'color-tag-admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(LessonResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Color tag')
            ->assertSee('Red')
            ->assertSee('Yellow')
            ->assertSee('Blue')
            ->assertDontSee('Readiness');
    }

    public function test_lesson_list_shows_small_color_dots_instead_of_text_selectors(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'color-dot-admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
        $lesson = Lesson::create([
            'title' => 'Tagged lesson',
            'slug' => 'tagged-lesson',
            'content' => '<p>Body.</p>',
            'color_tag' => Lesson::COLOR_TAG_RED,
        ]);
        Lesson::create([
            'title' => 'Yellow tagged lesson',
            'slug' => 'yellow-tagged-lesson',
            'content' => '<p>Body.</p>',
            'color_tag' => Lesson::COLOR_TAG_YELLOW,
        ]);
        Lesson::create([
            'title' => 'Blue tagged lesson',
            'slug' => 'blue-tagged-lesson',
            'content' => '<p>Body.</p>',
            'color_tag' => Lesson::COLOR_TAG_BLUE,
        ]);

        Livewire::actingAs($admin)
            ->test(ListLessons::class)
            ->assertTableColumnStateSet('color_tag', Lesson::COLOR_TAG_RED, $lesson)
            ->assertSee('#dc2626')
            ->assertSee('#eab308')
            ->assertSee('#2563eb')
            ->assertSee('Red');
    }

    public function test_admin_can_tag_multiple_lessons_from_the_lessons_list(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'color-tag-bulk-admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
        $first = Lesson::create([
            'title' => 'First selected lesson',
            'slug' => 'first-selected-lesson',
            'content' => '<p>Body.</p>',
        ]);
        $second = Lesson::create([
            'title' => 'Second selected lesson',
            'slug' => 'second-selected-lesson',
            'content' => '<p>Body.</p>',
            'color_tag' => Lesson::COLOR_TAG_RED,
        ]);
        $unselected = Lesson::create([
            'title' => 'Unselected lesson',
            'slug' => 'unselected-lesson',
            'content' => '<p>Body.</p>',
        ]);

        Livewire::actingAs($admin)
            ->test(ListLessons::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(
                TestAction::make('tagColor')->table()->bulk(),
                ['color_tag' => Lesson::COLOR_TAG_YELLOW],
            );

        $this->assertSame(Lesson::COLOR_TAG_YELLOW, $first->fresh()->color_tag);
        $this->assertSame(Lesson::COLOR_TAG_YELLOW, $second->fresh()->color_tag);
        $this->assertNull($unselected->fresh()->color_tag);
    }

    public function test_bulk_color_picker_uses_compact_icon_only_color_choices(): void
    {
        $admin = User::create([
            'name' => 'Pilot Admin',
            'email' => 'color-tag-picker-admin@pilot.local',
            'password' => bcrypt('password'),
            'role' => User::ROLE_ADMIN,
        ]);
        $lesson = Lesson::create([
            'title' => 'Picker lesson',
            'slug' => 'picker-lesson',
            'content' => '<p>Body.</p>',
        ]);

        $page = Livewire::actingAs($admin)
            ->test(ListLessons::class)
            ->selectTableRecords([$lesson->id])
            ->mountAction(TestAction::make('tagColor')->table()->bulk());

        $page->assertFormFieldExists('color_tag', function (ToggleButtons $field): bool {
            return $field->areButtonLabelsHidden()
                && $field->isInline()
                && $field->isRequired()
                && $field->getOptions() === Lesson::colorTagLabels()
                && $field->getTooltips() === Lesson::colorTagLabels()
                && $field->getIcons() === [
                    Lesson::COLOR_TAG_RED => 'heroicon-o-swatch',
                    Lesson::COLOR_TAG_YELLOW => 'heroicon-o-swatch',
                    Lesson::COLOR_TAG_BLUE => 'heroicon-o-swatch',
                ]
                && $field->getColors() === [
                    Lesson::COLOR_TAG_RED => 'danger',
                    Lesson::COLOR_TAG_YELLOW => 'warning',
                    Lesson::COLOR_TAG_BLUE => 'info',
                ];
        });
    }

    public function test_existing_green_color_tags_are_migrated_to_yellow(): void
    {
        $lesson = Lesson::create([
            'title' => 'Color tag migration',
            'slug' => 'color-tag-migration',
            'content' => '<p>Body.</p>',
            'color_tag' => 'green',
        ]);

        $migration = require database_path('migrations/2026_10_27_000002_replace_green_lesson_color_tags_with_yellow.php');
        $migration->up();

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'color_tag' => 'yellow',
        ]);
    }
}
