<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserEvent\Pages;

use App\Enums\MaxBot\BotButton;
use App\Enums\UserEventType;
use App\Models\User;
use App\Models\UserEvent;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\UserEvent\UserEventResource;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\DateRange;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Preview;
use Throwable;

/**
 * @extends IndexPage<UserEventResource>
 */
class UserEventIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
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
            Date::make('Дата', 'created_at')->format('d.m.Y')->sortable(),
        ];
    }

    /**
     * @return ListOf<ActionButtonContract>
     */
    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    /**
     * @return list<FieldContract>
     */
    protected function filters(): iterable
    {
        return [
            BelongsTo::make('Клиент', 'user', function (User $user) {
                return $user->name ? $user->phone.' | '.$user->name : $user->phone;
            }, UserResource::class)
                ->nullable(),
            Enum::make('Тип события', 'event_type')
                ->attach(UserEventType::class)
                ->nullable(),
            DateRange::make('Период', 'created_at'),
        ];
    }

    /**
     * @return list<QueryTag>
     */
    protected function queryTags(): array
    {
        return [];
    }

    /**
     * @return list<Metric>
     */
    protected function metrics(): array
    {
        return [];
    }

    /**
     * @param  TableBuilder  $component
     * @return TableBuilder
     */
    protected function modifyListComponent(ComponentContract $component): ComponentContract
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
