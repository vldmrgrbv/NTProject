<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Receipt;

use App\Models\Receipt;
use App\MoonShine\Handlers\CleanupExportHandler;
use App\MoonShine\Resources\Receipt\Pages\ReceiptDetailPage;
use App\MoonShine\Resources\Receipt\Pages\ReceiptFormPage;
use App\MoonShine\Resources\Receipt\Pages\ReceiptIndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Crud\Handlers\Handler;
use MoonShine\ImportExport\Contracts\HasImportExportContract;
use MoonShine\ImportExport\Traits\ImportExportConcern;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<Receipt, ReceiptIndexPage, ReceiptFormPage, ReceiptDetailPage>
 */
class ReceiptResource extends ModelResource  implements HasImportExportContract
{
    use ImportExportConcern;

    protected string $model = Receipt::class;

    protected string $title = 'Чеки';

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [];
    }

    protected function pages(): array
    {
        return [
            ReceiptIndexPage::class,
            ReceiptFormPage::class,
            ReceiptDetailPage::class,
        ];
    }

    protected function exportFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Телефон клиента', 'phone_user'),
            Text::make('Статус', 'status_str'),
            Text::make('Источник', 'source_str'),
            Text::make('ФН', 'fn'),
            Text::make('ФД', 'fd'),
            Text::make('ФП', 'fp'),
            Text::make('Сумма', 'sum'),
            Text::make('Дата', 'dt'),
            Text::make('Номер NT', 'nt_number'),
            Text::make('Причина отказа', 'reason_failed'),
            Json::make('SKUs', 'skus')
                ->fields([
                    Text::make('SKU', 'SKU'),
                    Text::make('Название', 'name'),
                    Text::make('Цена', 'price'),
                    Text::make('Количество', 'quantity'),
                    Text::make('Сумма', 'sum'),
                ]),
            Textarea::make('Ответы API', 'responses_str'),
        ];
    }

    protected function export(): ?Handler
    {
        return CleanupExportHandler::make(__('moonshine::ui.export'))
            ->filename('receipts_export_' . date('Y-m-d_H-i-s'));
    }

    protected function importFields(): iterable
    {
        return [];
    }

    protected function import(): ?Handler
    {
        return null;
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::CREATE);
    }

}
