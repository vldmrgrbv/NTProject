<?php

declare(strict_types=1);

namespace App\MoonShine\Resources\BotSetting;

use App\Models\BotSetting;
use MoonShine\Contracts\UI\FieldContract;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\Support\Enums\Action;
use MoonShine\Support\ListOf;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Image;
use MoonShine\UI\Fields\Text;
use MoonShine\UI\Fields\Textarea;

/**
 * @extends ModelResource<BotSetting>
 */
class BotSettingResource extends ModelResource
{
    protected string $model = BotSetting::class;

    protected string $title = 'Настройки бота';

    protected string $column = 'key';

    /**
     * @return list<FieldContract>
     */
    protected function fields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Ключ', 'key')->readonly(),
            Textarea::make('Значение', 'value')->required(),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),
            Text::make('Ключ', 'key')->readonly(),
            Text::make('Значение', 'value')->copy(),
            Image::make('Изображение', 'image_path')
                ->disk('s3')
                ->dir('bot-settings'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function formFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Ключ', 'key')->readonly(),
            Textarea::make('Значение', 'value')->required(),
            Image::make('Изображение', 'image_path')
                ->disk('s3')
                ->dir('bot-settings'),
        ];
    }

    /**
     * @return list<FieldContract>
     */
    protected function detailFields(): iterable
    {
        return [
            ID::make(),
            Text::make('Ключ', 'key'),
            Textarea::make('Значение', 'value'),
            Image::make('Изображение', 'image_path')
                ->disk('s3')
                ->dir('bot-settings'),
        ];
    }

    public function rules(mixed $item): array
    {
        return [
            'value' => ['required', 'string'],
        ];
    }

    protected function activeActions(): ListOf
    {
        return parent::activeActions()
            ->except(Action::CREATE, Action::DELETE);
    }
}
