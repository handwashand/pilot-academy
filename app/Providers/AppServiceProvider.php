<?php

namespace App\Providers;

use App\Services\Translator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Translator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $translator = app(Translator::class);

            $view->with('locale', [
                'current' => app()->getLocale(),
                'direction' => $translator->direction(),
                'available' => $translator->activeLanguages(),
                'default' => $translator->defaultCode(),
            ]);
        });
    }
}
