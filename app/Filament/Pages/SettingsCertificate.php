<?php

namespace App\Filament\Pages;

use App\Actions\FillCertificateText;
use App\Actions\IssueCertificate;
use App\Filament\Clusters\Settings;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Language;
use App\Models\Translation;
use App\Models\User;
use App\Services\Translator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;

/**
 * Settings → Certificate: the standard certificate every course uses — its
 * background, its words, and an optional signature.
 *
 * The background is the only file in Certificate::STANDARD_DIR, as before.
 * The words are the five mail.certificate_pdf.* lines, saved per language as
 * rows in the translations table (the same rows Settings → Translations
 * edits); an empty box deletes the row, so the shipped text comes back. The
 * signer's name and title are rows of their own, and the signature image is
 * the only file in FillCertificateText::SIGNATURE_DIR. A signature is printed
 * only where a line uses :signature (owner, 2026-11-25).
 *
 * The preview draws the certificate's own view with the text being typed, in
 * the panel's language, before anything is saved.
 */
class SettingsCertificate extends Page
{
    protected static ?string $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 40;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_settings.certificate.nav');
    }

    public function getTitle(): string
    {
        return __t('admin_settings.certificate.nav');
    }

    public function getSubheading(): ?string
    {
        return __t('admin_settings.certificate.subheading');
    }

    public function mount(): void
    {
        $saved = $this->savedRows();
        $default = app(Translator::class)->defaultCode();
        $text = [];

        foreach ($this->languages() as $language) {
            foreach (FillCertificateText::LINES as $line) {
                $text[$language->code][$line] = $saved["{$language->code}|mail.certificate_pdf.{$line}"] ?? '';
            }
        }

        $this->form->fill([
            'background' => Certificate::standardTemplate(),
            'text' => $text,
            'signature_name' => $saved[$default.'|'.FillCertificateText::SIGNATURE_NAME] ?? '',
            'signature_title' => $saved[$default.'|'.FillCertificateText::SIGNATURE_TITLE] ?? '',
            'signature_image' => FillCertificateText::signatureImage(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'xl' => 2])->schema([
                    // Left: what can be changed.
                    Flex::make([
                        Section::make(fn (): string => __t('admin_settings.certificate.background_section'))
                            ->compact()
                            ->schema([
                                FileUpload::make('background')
                                    ->label(fn (): string => __t('admin_settings.certificate.background'))
                                    ->helperText(fn (): string => __t('admin_settings.certificate.background_help'))
                                    ->image()
                                    ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                    ->disk('public')
                                    ->directory(Certificate::STANDARD_DIR)
                                    ->visibility('public')
                                    ->maxSize(8192),
                            ]),

                        $this->textSection(),

                        Section::make(fn (): string => __t('admin_settings.certificate.signature_section'))
                            ->description(fn (): string => __t('admin_settings.certificate.signature_help'))
                            ->compact()
                            ->columns(['default' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('signature_name')
                                    ->label(fn (): string => __t('admin_settings.certificate.signature_name'))
                                    ->maxLength(120)
                                    ->live(onBlur: true),
                                TextInput::make('signature_title')
                                    ->label(fn (): string => __t('admin_settings.certificate.signature_title'))
                                    ->maxLength(120)
                                    ->live(onBlur: true),
                                FileUpload::make('signature_image')
                                    ->label(fn (): string => __t('admin_settings.certificate.signature_image'))
                                    ->helperText(fn (): string => __t('admin_settings.certificate.signature_image_help'))
                                    ->image()
                                    ->acceptedFileTypes(['image/png'])
                                    ->disk('public')
                                    ->directory(FillCertificateText::SIGNATURE_DIR)
                                    ->visibility('public')
                                    ->maxSize(2048)
                                    ->live()
                                    ->columnSpanFull(),
                            ]),
                    ])->from('default')->extraAttributes(['style' => 'flex-direction: column']),

                    // Right: the certificate, as typed.
                    Section::make(fn (): string => __t('admin_settings.certificate.preview'))
                        ->description(fn (): string => __t('admin_settings.certificate.preview_help'))
                        ->compact()
                        ->schema([
                            View::make('filament.certificate-preview')
                                ->viewData(fn (Get $get): array => ['html' => $this->previewHtml($get)]),
                        ]),
                ]),
            ])
            ->statePath('data');
    }

    /** The five lines, one tab per language, and the placeholders to copy into them. */
    private function textSection(): Section
    {
        $placeholders = array_map(
            fn (string $name): Text => Text::make(':'.$name)
                ->fontFamily('mono')
                ->badge()
                ->color('warning')
                ->tooltip(fn (): string => __t("admin_settings.certificate.placeholder.{$name}"))
                ->copyable(),
            FillCertificateText::PLACEHOLDERS,
        );

        return Section::make(fn (): string => __t('admin_settings.certificate.text_section'))
            ->description(fn (): string => __t('admin_settings.certificate.text_help'))
            ->compact()
            ->headerActions([$this->resetAction()])
            ->schema([
                Tabs::make('languages')->tabs(
                    $this->languages()->map(fn (Language $language): Tab => Tab::make($language->native_name)
                        ->schema(array_map(fn (string $line) => $this->lineField($language->code, $line), FillCertificateText::LINES)))
                        ->all(),
                ),
                Text::make(fn (): string => __t('admin_settings.certificate.placeholders')),
                Flex::make($placeholders)->from('default')->extraAttributes(['style' => 'flex-wrap: wrap; gap: .4rem']),
            ]);
    }

    private function lineField(string $code, string $line): TextInput|Textarea
    {
        $field = $line === 'achievement_course'
            ? Textarea::make("text.{$code}.{$line}")->rows(3)
            : TextInput::make("text.{$code}.{$line}");

        return $field
            ->label(fn (): string => __t("admin_settings.certificate.lines.{$line}"))
            // The shipped text shows through an empty box, so it is clear what
            // "empty" means.
            ->placeholder(fn (): string => (string) Lang::get("mail.certificate_pdf.{$line}", [], $code))
            ->maxLength($line === 'achievement_course' ? 600 : 200)
            ->live(onBlur: true);
    }

    /** Clear every language's own text, after a confirmation: the shipped text comes back. */
    private function resetAction(): Action
    {
        return Action::make('resetText')
            ->label(fn (): string => __t('admin_settings.certificate.reset'))
            ->link()
            ->requiresConfirmation()
            ->modalHeading(fn (): string => __t('admin_settings.certificate.reset_heading'))
            ->modalDescription(fn (): string => __t('admin_settings.certificate.reset_description'))
            ->action(function (): void {
                Translation::query()
                    ->whereIn('key', array_map(fn (string $line): string => "mail.certificate_pdf.{$line}", FillCertificateText::LINES))
                    ->get()
                    ->each->delete();

                $this->mount();

                Notification::make()->title(__t('admin_settings.certificate.reset_done'))->success()->send();
            });
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([
                    Action::make('save')
                        ->label(fn (): string => __t('admin_settings.overlay.save'))
                        ->submit('save'),
                ])]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        // One standard background and one signature image at a time.
        $this->keepOnly(Certificate::STANDARD_DIR, $state['background'] ?? null);
        $this->keepOnly(FillCertificateText::SIGNATURE_DIR, $state['signature_image'] ?? null);

        foreach ($this->languages() as $language) {
            foreach (FillCertificateText::LINES as $line) {
                $this->saveRow($language, "mail.certificate_pdf.{$line}", $state['text'][$language->code][$line] ?? null);
            }
        }

        // The signer is one person: saved for the default language, which
        // every other language falls back to.
        $default = $this->languages()->firstWhere('code', app(Translator::class)->defaultCode());

        if ($default) {
            $this->saveRow($default, FillCertificateText::SIGNATURE_NAME, $state['signature_name'] ?? null);
            $this->saveRow($default, FillCertificateText::SIGNATURE_TITLE, $state['signature_title'] ?? null);
        }

        $this->mount();

        Notification::make()->title(__t('admin_settings.overlay.saved'))->success()->send();
    }

    /** Typed text becomes the row; an emptied box removes it, so the shipped text shows again. */
    private function saveRow(Language $language, string $key, ?string $value): void
    {
        $value = trim((string) $value);
        $row = Translation::firstOrNew(['key' => $key, 'language_id' => $language->id]);

        if ($value === '') {
            if ($row->exists) {
                $row->delete();
            }

            return;
        }

        if ($row->value === $value) {
            return;
        }

        $row->fill([
            'value' => $value,
            'created_by' => $row->created_by ?? auth()->id(),
            'updated_by' => auth()->id(),
        ])->save();
    }

    private function keepOnly(string $directory, ?string $kept): void
    {
        foreach (Storage::disk('public')->files($directory) as $file) {
            if ($file !== $kept) {
                Storage::disk('public')->delete($file);
            }
        }
    }

    /** The preview, with whatever is typed for the panel's own language. */
    private function previewHtml(Get $get): string
    {
        $code = app()->getLocale();
        $typed = (array) ($get('text.'.$code) ?? []);
        $typed['signature_name'] = $get('signature_name');
        $typed['signature_title'] = $get('signature_title');

        $image = $get('signature_image');
        $image = is_array($image) ? (reset($image) ?: null) : $image;
        $signatureUrl = is_string($image) && Storage::disk('public')->exists($image) ? Storage::disk('public')->url($image) : null;

        return app(IssueCertificate::class)->previewHtml($this->sample(), $typed, $signatureUrl);
    }

    /** A certificate nobody holds, for the preview only: never saved. */
    private function sample(): Certificate
    {
        $course = Course::query()->published()->orderBy('sort_order')->first()
            ?? new Course(['title' => __t('admin_settings.certificate.sample_course')]);

        $certificate = new Certificate([
            'number' => 'PA-0-'.now()->year.'-SAMPLE',
            'name' => __t('admin_settings.certificate.sample_name'),
            'score_percent' => 92,
            'issued_at' => now(),
        ]);

        return $certificate
            ->setRelation('course', $course)
            ->setRelation('user', new User(['name' => __t('admin_settings.certificate.sample_name')]));
    }

    /** @return Collection<int, Language> */
    private function languages()
    {
        return app(Translator::class)->activeLanguages();
    }

    /** @return array<string, string> "code|key" => saved value, for the lines and the signer. */
    private function savedRows(): array
    {
        $keys = [
            ...array_map(fn (string $line): string => "mail.certificate_pdf.{$line}", FillCertificateText::LINES),
            FillCertificateText::SIGNATURE_NAME,
            FillCertificateText::SIGNATURE_TITLE,
        ];

        return Translation::query()
            ->with('language')
            ->whereIn('key', $keys)
            ->whereNotNull('value')
            ->get()
            ->mapWithKeys(fn (Translation $row): array => [($row->language?->code ?? '').'|'.$row->key => (string) $row->value])
            ->all();
    }
}
