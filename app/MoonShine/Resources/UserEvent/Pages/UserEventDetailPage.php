<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserEvent\Pages;

use App\Enums\MaxBot\BotButton;
use App\Models\User;
use App\Models\UserEvent;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\UserEvent\UserEventResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use Throwable;

/**
 * @extends DetailPage<UserEventResource>
 */
class UserEventDetailPage extends DetailPage
{
    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make(),
            BelongsTo::make('Пользователь', 'user',
                function (User $user) {
                    return $user->getNamePhoneStr();
                },
                UserResource::class
            ),
            Preview::make('Тип', 'event_type_str')
                ->badge('secondary'),
            Preview::make('Действие', 'payload_str')
                ->badge(fn($value, $field) => match ($value) {
                    'Успех' => 'green',
                    'Отказ' => 'red',
                    default => 'gray'
                }),
            Date::make('Дата', 'created_at')->format('d.m.Y'),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @param  TableBuilder  $component
     * @return TableBuilder
     */
    protected function modifyDetailComponent(ComponentContract $component): ComponentContract
    {
        return $component;
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function topLayer(): array
    {
        return [
            ...parent::topLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        return [
            ...parent::mainLayer(),
        ];
    }

    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function bottomLayer(): array
    {
        return [
            ...parent::bottomLayer(),
        ];
    }
}
