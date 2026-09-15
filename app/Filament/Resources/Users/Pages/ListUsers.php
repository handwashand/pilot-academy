<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * Staff and students share one table, so give each role its own view.
     * "Learners" is what every report counts, which makes it the tab to check
     * when a number looks wrong.
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__t('admin_people.users.all_users'))
                ->badge(fn (): int => User::count()),

            'admins' => Tab::make(__t('admin_people.users.admins'))
                ->badge(fn (): int => User::where('role', User::ROLE_ADMIN)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', User::ROLE_ADMIN)),

            'creators' => Tab::make(__t('admin_people.users.creators'))
                ->badge(fn (): int => User::where('role', User::ROLE_CREATOR)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', User::ROLE_CREATOR)),

            'learners' => Tab::make(__t('admin_people.users.learners'))
                ->badge(fn (): int => User::learners()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->learners()),
        ];
    }
}
