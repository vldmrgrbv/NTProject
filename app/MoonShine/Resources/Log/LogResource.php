<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Log;

use App\Models\Log;
use App\MoonShine\Resources\Log\Pages\LogDetailPage;
use App\MoonShine\Resources\Log\Pages\LogFormPage;
use App\MoonShine\Resources\Log\Pages\LogIndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<Log, LogIndexPage, LogFormPage, LogDetailPage>
 */
class LogResource extends ModelResource
{
    protected string $model = Log::class;

    protected string $title = 'Логи';

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Сообщение', 'message'),
            Text::make('Канал', 'channel'),
            Text::make('Уровень', 'level_name'),
            Text::make('Дата', 'datetime'),
            Json::make('Контекст', 'context'),
            Json::make('Дополнительно', 'extra'),
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::CREATE, Action::UPDATE, Action::DELETE);
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            LogIndexPage::class,
            LogFormPage::class,
            LogDetailPage::class,
        ];
    }
}
