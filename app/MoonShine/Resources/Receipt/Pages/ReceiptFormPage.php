<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\Receipt\Pages;

use App\Models\Receipt;
use App\Models\User;
use App\MoonShine\Resources\Receipt\ReceiptResource;
use App\MoonShine\Resources\ReceiptPhoto\ReceiptPhotoResource;
use App\MoonShine\Resources\User\UserResource;
use MoonShine\Contracts\Core\TypeCasts\DataWrapperContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Pages\Crud\FormPage;
use MoonShine\Support\ListOf;
use MoonShine\UI\Components\Badge;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Column;
use MoonShine\UI\Components\Layout\Grid;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Json;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;
use Throwable;

/**
 * @extends FormPage<ReceiptResource>
 */
class ReceiptFormPage extends FormPage
{
    /**
     * @return list<ComponentContract|FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            Box::make([
                ID::make()->sortable(),
                Grid::make([
                    Column::make(
                        [
                            Grid::make([
                                Column::make(
                                    [
                                        Preview::make('Статус', 'status', fn(Receipt $item) => Badge::make(
                                            $item->status->toString(),
                                            $item->status->getColor()
                                        )),
                                    ],
                                    colSpan: 6,
                                    adaptiveColSpan: 6
                                ),
                                Column::make(
                                    [
                                        Preview::make('Источник', 'source', fn(Receipt $item) => Badge::make(
                                            $item->source->toString(),
                                            $item->source->getColor()
                                        )),
                                    ],
                                    colSpan: 6,
                                    adaptiveColSpan: 6
                                ),
                            ]),
                            BelongsTo::make('Клиент', 'user',
                                function (User $user) {
                                    return $user->getNamePhoneStr();
                            },
                                UserResource::class
                            ),
                            Text::make('ИНН', 'inn'),
                            Text::make('Номер NT', 'nt_number'),
                            Textarea::make('Причина отказа', 'reason_failed')->readonly(),
                        ],
                        colSpan: 6,
                        adaptiveColSpan: 6
                    ),
                    Column::make(
                        [
                            Text::make('ФН', 'fn'),
                            Text::make('ФД', 'fd'),
                            Text::make('ФП', 'fp'),
                            Text::make('Сумма', 'sum')->sortable(),
                            Text::make('Дата', 'dt')->sortable(),
                        ],
                        colSpan: 6,
                        adaptiveColSpan: 6
                    ),
                    Column::make(
                        [
                            Text::make('Создан', 'created_at'),
                        ],
                        colSpan: 6,
                        adaptiveColSpan: 6
                    ),
                    Column::make(
                        [
                            Text::make('Обновлен', 'updated_at'),
                        ],
                        colSpan: 6,
                        adaptiveColSpan: 6
                    ),

                ]),
                Json::make('SKUs', 'skus')
                    ->fields([
                        Text::make('SKU', 'SKU'),
                        Text::make('Название', 'name'),
                        Text::make('Цена', 'price'),
                        Text::make('Количество', 'quantity'),
                        Text::make('Сумма', 'sum'),
                    ])
                    ->reorderable(false)
                    ->filterMode(),
                Preview::make(
                    'Отвeты API',
                    'responses_pretty',
                    fn (Receipt $item): string => $this->jsonPreview($item->responses_pretty)
                ),
                Preview::make(
                    'Ответ matching/LLM',
                    'matching_result_pretty',
                    fn (Receipt $item): string => $this->jsonPreview($item->matching_result_pretty)
                ),
                HasMany::make('Фотографии', 'photos', resource: ReceiptPhotoResource::class)
                    ->fields([
                        Image::make('Фото', 'path'),
                    ]),
            ]),
        ];
    }

    protected function buttons(): ListOf
    {
        return parent::buttons();
    }

    protected function formButtons(): ListOf
    {
        return parent::formButtons();
    }

    private function jsonPreview(string $value): string
    {
        return '<pre style="white-space: pre-wrap; word-break: break-word; max-height: 520px; overflow: auto; padding: 12px; border-radius: 8px; background: #f8fafc; border: 1px solid #e5e7eb;">'
            . e($value)
            . '</pre>';
    }

    protected function rules(DataWrapperContract $item): array
    {
        return [];
    }

    /**
     * @param  FormBuilder  $component
     * @return FormBuilder
     */
    protected function modifyFormComponent(FormBuilderContract $component): FormBuilderContract
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
