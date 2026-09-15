<?php

namespace App\Providers;

use App\Services\Translator;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachBulkAction;
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
        $this->sentenceCaseModalHeadings();

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

    /**
     * Filament Title-Cases the record name inside these modal headings —
     * "Attach Question", "Удалить выбранные Курсы" — which is English style and
     * wrong in Russian or French. Same Filament strings, plain label. Runs after
     * each action's own setUp, and a heading set on the action itself still wins.
     * Resource page headings are handled by HasSentenceCaseLabels.
     */
    private function sentenceCaseModalHeadings(): void
    {
        CreateAction::configureUsing(fn (CreateAction $action) => $action->modalHeading(
            fn (CreateAction $action): string => __('filament-actions::create.single.modal.heading', ['label' => $action->getModelLabel()]),
        ));

        AttachAction::configureUsing(fn (AttachAction $action) => $action->modalHeading(
            fn (AttachAction $action): string => __('filament-actions::attach.single.modal.heading', ['label' => $action->getModelLabel()]),
        ));

        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => $action->modalHeading(
            fn (DeleteBulkAction $action): string => __('filament-actions::delete.multiple.modal.heading', ['label' => $action->getPluralModelLabel()]),
        ));

        DetachBulkAction::configureUsing(fn (DetachBulkAction $action) => $action->modalHeading(
            fn (DetachBulkAction $action): string => __('filament-actions::detach.multiple.modal.heading', ['label' => $action->getPluralModelLabel()]),
        ));
    }
}
