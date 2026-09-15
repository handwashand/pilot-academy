<?php

namespace App\Filament\Resources\Certificates;

use App\Filament\Resources\Certificates\Pages\ListCertificates;
use App\Filament\Resources\Certificates\Tables\CertificatesTable;
use App\Models\Certificate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.results');
    }

    public static function getNavigationLabel(): string
    {
        return __t('admin_nav.certificates.nav');
    }

    public static function getModelLabel(): string
    {
        return __t('admin_nav.certificates.one');
    }

    public static function getPluralModelLabel(): string
    {
        return __t('admin_nav.certificates.many');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user.company', 'course']);
    }

    // No navigation badge. It showed the total number of valid certificates,
    // which nobody acts on; a badge in this sidebar means something is waiting
    // for a person.

    public static function table(Table $table): Table
    {
        return CertificatesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
        ];
    }
}
