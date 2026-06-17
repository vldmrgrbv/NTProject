<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\ReceiptPhoto;

use App\Models\ReceiptPhoto;
use App\MoonShine\Resources\Receipt\ReceiptResource;
use App\MoonShine\Resources\ReceiptPhoto\Pages\ReceiptPhotoDetailPage;
use App\MoonShine\Resources\ReceiptPhoto\Pages\ReceiptPhotoFormPage;
use App\MoonShine\Resources\ReceiptPhoto\Pages\ReceiptPhotoIndexPage;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Fields\Relationships\BelongsTo;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;

/**
 * @extends ModelResource<ReceiptPhoto, ReceiptPhotoIndexPage, ReceiptPhotoFormPage, ReceiptPhotoDetailPage>
 */
class ReceiptPhotoResource extends ModelResource
{
    protected string $model = ReceiptPhoto::class;

    protected string $title = 'Фотографии чеков';

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Чек', 'receipt', resource: ReceiptResource::class)
                ->sortable()
                ->searchable(),
            Image::make('Фотография', 'path')
                ->disk('s3')
                ->dir('receipt_photos'),
        ];
    }

    protected function pages(): array
    {
        return [
            ReceiptPhotoIndexPage::class,
            ReceiptPhotoFormPage::class,
            ReceiptPhotoDetailPage::class,
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::CREATE, Action::DELETE);
    }
}
