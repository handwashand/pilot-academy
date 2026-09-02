<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Pilot Academy')
            // Filament's own brand API rather than a custom view: it applies the
            // height as an inline style and swaps the dark lockup through its
            // fi-logo-light/fi-logo-dark classes. The panel stylesheet carries no
            // Tailwind utility layer, so h-*/dark:* classes inside a custom view
            // are inert — the logo was falling back to Filament's default 1.5rem
            // box with both lockups stacked inside it.
            //
            // A missing file gives null, which makes Filament print the brand name.
            ->brandLogo(fn (): ?string => self::brandAsset('img/pilot-logo.png'))
            ->darkModeBrandLogo(fn (): ?string => self::brandAsset('img/pilot-logo-white.png'))
            // Sized for the job it is doing. In the panel chrome the logo is a
            // wayfinding mark beside the navigation and should stay quiet; on the
            // sign-in screen it is the only brand element on the page, and at
            // sidebar size it sat below the 24px "Sign in" heading in the visual
            // hierarchy — the utility label out-ranking the brand.
            ->brandLogoHeight(fn (): string => request()->routeIs('filament.*.auth.*') ? '3rem' : '1.75rem')
            ->favicon(asset('img/pilot-mark.svg'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // The dashboard shows the academy, not the panel. Filament's
            // account and version cards are deliberately left off: signing out
            // belongs in the profile menu, top right, where people look for it.
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /** Absolute URL for a brand image, or null when that file has not been added. */
    private static function brandAsset(string $path): ?string
    {
        return is_file(public_path($path)) ? asset($path) : null;
    }
}
