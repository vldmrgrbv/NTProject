<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\User;

use App\Models\User;
use App\MoonShine\Resources\User\Pages\UserDetailPage;
use App\MoonShine\Resources\User\Pages\UserFormPage;
use App\MoonShine\Resources\User\Pages\UserIndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Switcher;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<User, UserIndexPage, UserFormPage, UserDetailPage>
 */
class UserResource extends ModelResource
{
    protected string $model = User::class;

    protected string $title = 'Пользователи';

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Имя', 'name')
                ->sortable()
                ->searchable(),
            Text::make('Email', 'email')
                ->sortable()
                ->searchable(),
            Text::make('Телефон', 'phone')
                ->sortable()
                ->searchable(),
            Date::make('Дата рождения', 'birthday')
                ->sortable(),
            Select::make('Пол', 'gender')
                ->options([
                    'male' => 'Мужской',
                    'female' => 'Женский',
                ])
                ->nullable()
                ->sortable(),
            Switcher::make('Маркетинговое согласие', 'marketing_agree'),
            Switcher::make('Согласие с политикой', 'privacy_agree'),
            Switcher::make('Авторизован', 'is_authorized'),
            Switcher::make('Whitelist', 'is_whitelisted'),
        ];
    }

    protected function pages(): array
    {
        return [
            UserIndexPage::class,
            UserFormPage::class,
            UserDetailPage::class,
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::CREATE, Action::DELETE, Action::UPDATE);
    }
}
