<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Course;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        if (! auth()->user()?->isAdmin()) {
            return $schema->components([]);
        }

        return $schema->components([
            Section::make(__t('admin_widgets.filters.heading'))
                ->description(__t('admin_widgets.filters.description'))
                ->compact()
                ->columnSpanFull()
                ->schema([
                    DatePicker::make('start_date')
                        ->label(__t('admin_widgets.filters.start_date'))
                        ->default(now()->subDays(29)->toDateString())
                        ->maxDate(now()),
                    DatePicker::make('end_date')
                        ->label(__t('admin_widgets.filters.end_date'))
                        ->default(now()->toDateString())
                        ->maxDate(now()),
                    Select::make('company_id')
                        ->label(__t('admin_widgets.filters.partner'))
                        ->placeholder(__t('admin_widgets.filters.all_partners'))
                        ->options(fn (): array => Company::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Select::make('product_id')
                        ->label(__t('admin_widgets.filters.product'))
                        ->placeholder(__t('admin_widgets.filters.all_products'))
                        ->options(fn (): array => Product::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload(),
                    Select::make('course_id')
                        ->label(__t('admin_widgets.filters.course'))
                        ->placeholder(__t('admin_widgets.filters.all_courses'))
                        ->options(fn (): array => Course::query()->orderBy('title')->pluck('title', 'id')->all())
                        ->searchable()
                        ->preload(),
                ])
                ->columns(['default' => 1, 'sm' => 2, 'xl' => 5]),
        ]);
    }
}
