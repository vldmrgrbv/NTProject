<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\UserEvent;

use App\Models\UserEvent;
use App\MoonShine\Handlers\CleanupExportHandler;
use App\MoonShine\Resources\UserEvent\Pages\UserEventDetailPage;
use App\MoonShine\Resources\UserEvent\Pages\UserEventFormPage;
use App\MoonShine\Resources\UserEvent\Pages\UserEventIndexPage;
use MoonShine\Contracts\Core\PageContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\Text;

/**
 * @extends ModelResource<UserEvent, UserEventIndexPage, UserEventFormPage, UserEventDetailPage>
 */
class UserEventResource extends ModelResource implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = UserEvent::class;

    protected string $title = 'События';

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::CREATE, Action::UPDATE, Action::DELETE);
    }

    protected function import(): ?Handler
    {
        return null;
    }

    protected function exportFields(): iterable
    {
        return [
            Text::make('Пользователь', 'user_str'),
            Text::make('Тип', 'event_type_str'),
            Text::make('Действие', 'payload_str'),
            Text::make('Дата', 'created_at_str'),
        ];
    }

    protected function export(): ?Handler
    {
        return CleanupExportHandler::make(__('moonshine::ui.export'))
            ->filename('user_events_export_'.date('Y-m-d_H-i-s'));
    }

    /**
     * @return list<class-string<PageContract>>
     */
    protected function pages(): array
    {
        return [
            UserEventIndexPage::class,
            UserEventFormPage::class,
            UserEventDetailPage::class,
        ];
    }
}
