<?php

namespace App\Filament\Clusters;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * The academy's own setup, as one sidebar entry with a tab strip across the
 * top — Profile, Integrations, Mail, Translations, Languages — instead of
 * five separate sidebar links. Each member page/resource is unchanged: it
 * keeps its own route, access rules and tests; only its $cluster property
 * (and, for pages, a dropped getNavigationGroup()) moved it here. See
 * App\Filament\Pages\SettingsProfile, Integrations, MailCheck and
 * App\Filament\Resources\{Languages,Translations}\…Resource.
 *
 * Opening the cluster itself (clicking "Settings") redirects to the first
 * member by $navigationSort — SettingsProfile, sort 5 — which is Filament's
 * own Cluster::mount() behaviour, not something built here.
 */
class Settings extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationLabel(): string
    {
        // Distinct from the group heading below it, which reads "Settings" too.
        return __t('admin_nav.settings.nav');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __t('admin_nav.groups.settings');
    }
}
