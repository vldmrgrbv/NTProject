<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Receipt\Pages;

use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use App\Models\Receipt;
use App\Models\User;
use App\MoonShine\Resources\Receipt\ReceiptResource;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Contracts\UI\ActionButtonContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use MoonShine\Laravel\QueryTags\QueryTag;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Components\Metrics\Wrapped\Metric;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Enum;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use Throwable;

/**
 * @extends IndexPage<ReceiptResource>
 */
class ReceiptIndexPage extends IndexPage
{
    protected bool $isLazy = true;

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            BelongsTo::make('Клиент', 'user',
                function (User $user) {
                    return $user->getNamePhoneStr();
                },
                UserResource::class
            ),
            Preview::make('Статус', 'status', fn (Receipt $item) => Badge::make(
                $item->status->toString(),
                $item->status->getColor()
            )),
            Preview::make('Источник', 'source', fn(Receipt $item) => Badge::make(
                $item->source->toString(),
                $item->source->getColor()
            )),
            Text::make('ФН', 'fn'),
            Text::make('ФД', 'fd'),
            Text::make('ФП', 'fp'),
            Text::make('Сумма', 'sum')->sortable(),
            Text::make('Дата', 'dt')->sortable(),
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
            BelongsTo::make('Клиент', 'user', 'phone', UserResource::class)
                ->nullable(),
            Enum::make('Статус', 'status')
                ->attach(ReceiptStatus::class)
                ->nullable(),
            Enum::make('Источник', 'source')
                ->attach(ReceiptSource::class)
                ->nullable(),
            Text::make('ФН', 'fn'),
            Text::make('ФД', 'fd'),
            Text::make('ФП', 'fp'),
            Text::make('Сумма', 'sum'),
            Text::make('Дата', 'dt'),
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
