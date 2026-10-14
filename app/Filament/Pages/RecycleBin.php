<?php

namespace App\Filament\Pages;

use App\Filament\Clusters\Settings;
use App\Models\CaseStudy;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\MediaItem;
use App\Models\Product;
use App\Models\Question;
use App\Models\Tutorial;
use App\Models\Webinar;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;

/**
 * Deleted content, and the way back for it.
 *
 * Deleting a course, a lesson or any other piece of content hides it instead
 * of removing it. This page lists what is hidden, one kind at a time, with a
 * Restore button. There is no "delete for good" here on purpose: that is the
 * one thing nobody can undo, so it stays a job for whoever runs the database.
 *
 * Users and partners are not here — they are really deleted, so that a person
 * who asks for their data to be erased has it erased.
 */
class RecycleBin extends Page implements HasTable
{
    use InteractsWithTable;

    /**
     * What can be restored: the model, the column that names a record, and
     * the admin_nav key its plural name is under.
     */
    private const TYPES = [
        'courses' => [Course::class, 'title', 'courses'],
        'lessons' => [Lesson::class, 'title', 'lessons'],
        'questions' => [Question::class, 'prompt', 'questions'],
        'case_studies' => [CaseStudy::class, 'title', 'case_studies'],
        'tutorials' => [Tutorial::class, 'title', 'tutorials'],
        'webinars' => [Webinar::class, 'title', 'webinars'],
        'products' => [Product::class, 'name', 'products'],
        'media_items' => [MediaItem::class, 'name', 'media'],
    ];

    protected static ?string $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?int $navigationSort = 60;

    protected string $view = 'filament.pages.recycle-bin';

    /** Which kind of content is listed. Each tab is a link that sets it. */
    #[Url]
    public string $type = 'courses';

    public static function getNavigationLabel(): string
    {
        return __t('admin_pages.recycle_bin.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_pages.recycle_bin.nav');
    }

    public function getSubheading(): ?string
    {
        return __t('admin_pages.recycle_bin.subheading');
    }

    /** Admins an admin gave the right to — being an admin is not enough. */
    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->canUseRecycleBin();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * The tab strip: every kind, how many of it are deleted, and its link.
     *
     * @return array<string, array{label: string, count: int, url: string}>
     */
    public function tabs(): array
    {
        return collect(self::TYPES)
            ->map(fn (array $type, string $key): array => [
                'label' => Str::ucfirst(__t("admin_nav.{$type[2]}.many")),
                'count' => $this->deleted($key)->count(),
                'url' => static::getUrl(['type' => $key]),
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        $title = $this->current()[1];

        return $table
            ->query(fn (): Builder => $this->deleted($this->type))
            ->defaultSort('deleted_at', 'desc')
            ->columns([
                TextColumn::make($title)
                    ->label(__t('admin_common.name'))
                    ->limit(90)
                    ->wrap()
                    // LOWER … LIKE, as the student search does: PostgreSQL's
                    // LIKE minds the case and SQLite's does not.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereRaw("LOWER({$title}) LIKE ?", ['%'.Str::lower($search).'%'])),

                TextColumn::make('deleted_at')
                    ->label(__t('admin_pages.recycle_bin.deleted'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('restore')
                    ->label(__t('admin_pages.recycle_bin.restore'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading(__t('admin_pages.recycle_bin.restore_heading'))
                    ->modalDescription(__t('admin_pages.recycle_bin.restore_description'))
                    ->action(function (Model $record): void {
                        $record->restore();

                        Notification::make()->title(__t('admin_pages.recycle_bin.restored'))->success()->send();
                    }),
            ])
            ->emptyStateHeading(__t('admin_pages.recycle_bin.empty'))
            ->emptyStateDescription(__t('admin_pages.recycle_bin.empty_description'));
    }

    /**
     * The entry for the chosen kind. The value comes from the address bar, so
     * anything that is not one of ours falls back to courses.
     *
     * @return array{0: class-string<Model>, 1: string, 2: string}
     */
    private function current(): array
    {
        return self::TYPES[$this->type] ?? self::TYPES['courses'];
    }

    /** The deleted records of one kind. */
    private function deleted(string $type): Builder
    {
        $model = (self::TYPES[$type] ?? self::TYPES['courses'])[0];
        $query = $model::onlyTrashed();

        // A deleted lesson takes its questions with it, and restoring the
        // lesson brings them back; listing each of them here as well would
        // bury the questions somebody deleted one at a time.
        if ($model === Question::class) {
            $query->whereDoesntHave('lesson', fn (Builder $lesson): Builder => $lesson->onlyTrashed());
        }

        return $query;
    }
}
