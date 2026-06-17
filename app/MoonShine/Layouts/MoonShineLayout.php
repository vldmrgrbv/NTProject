<?php

declare(strict_types=1);

namespace App\MoonShine\Layouts;

use App\MoonShine\Pages\AnalyticsPage;
use App\MoonShine\Resources\BotSetting\BotSettingResource;
use App\MoonShine\Resources\Log\LogResource;
use App\MoonShine\Resources\Receipt\ReceiptResource;
use App\MoonShine\Resources\ReceiptPhoto\ReceiptPhotoResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\UserEvent\UserEventResource;
use MoonShine\ColorManager\ColorManager;
use MoonShine\ColorManager\Palettes\GrayPalette;
use MoonShine\Contracts\ColorManager\ColorManagerContract;
use MoonShine\Contracts\ColorManager\PaletteContract;
use MoonShine\Crud\Components\Fragment;
use MoonShine\Laravel\Layouts\AppLayout;
use MoonShine\MenuManager\MenuGroup;
use MoonShine\MenuManager\MenuItem;
use MoonShine\UI\Components\Layout\Body;
use MoonShine\UI\Components\Layout\Content;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\Flash;
use MoonShine\UI\Components\Layout\Html;
use MoonShine\UI\Components\Layout\Layout;
use MoonShine\UI\Components\Layout\Wrapper;
use MoonShine\UI\Components\When;

final class MoonShineLayout extends AppLayout
{
    /**
     * @var null|class-string<PaletteContract>
     */
    protected ?string $palette = GrayPalette::class;

    protected function assets(): array
    {
        return [
            ...parent::assets(),
        ];
    }

    protected function menu(): array
    {
        return [
            //            ...parent::menu(),
            MenuItem::make(UserResource::class, 'Пользователи'),
            MenuGroup::make('Чеки', [
                MenuItem::make(ReceiptResource::class, 'Чеки'),
                MenuItem::make(ReceiptPhotoResource::class, 'Фотографии чеков'),
            ]),
            MenuGroup::make('Чат-бот', [
                MenuItem::make(UserEventResource::class, 'События'),
                MenuItem::make(AnalyticsPage::class, 'Аналитика'),
                MenuItem::make(BotSettingResource::class, 'Настройки'),
            ]),
            //            MenuItem::make(LogResource::class, 'Логи'),
        ];
    }

    /**
     * @param  ColorManager  $colorManager
     */
    protected function colors(ColorManagerContract $colorManager): void
    {
        parent::colors($colorManager);

        // $colorManager->primary('#00000');
    }

    public function build(): Layout
    {
        return Layout::make([
            Html::make([
                $this->getHeadComponent(),
                Body::make([
                    Wrapper::make([
                        When::make(
                            fn (): bool => $this->topBar,
                            fn (): array => [
                                $this->getTopBarComponent(),
                            ]
                        ),

                        When::make(
                            fn (): bool => $this->sidebar,
                            fn (): array => [
                                $this->getSidebarComponent(),
                            ]
                        ),

                        When::make(
                            fn (): bool => $this->secondBar,
                            fn (): array => [
                                $this->getSecondBarComponent(),
                            ]
                        ),

                        When::make(
                            fn (): bool => $this->mobileMode || $this->bottomBar,
                            fn (): array => [
                                $this->getBottomBarComponent(),
                            ]
                        ),

                        Div::make([
                            Fragment::make([
                                Flash::make(),

                                $this->getHeaderComponent(),

                                Content::make($this->getContentComponents()),

                                //                                $this->getFooterComponent(),
                            ])->class(['layout-page', 'layout-page-simple' => $this->contentSimpled])->name(self::CONTENT_FRAGMENT_NAME),
                        ])->class(['layout-main', 'layout-main-centered' => $this->contentCentered])->customAttributes(['id' => self::CONTENT_ID]),
                    ]),
                ]),
            ])
                ->customAttributes([
                    'lang' => $this->getHeadLang(),
                ])
                ->withAlpineJs()
                ->when(
                    $this->hasThemes() || $this->isAlwaysDark(),
                    fn (Html $html): Html => $html->withThemes($this->isAlwaysDark())
                ),
        ]);
    }
}
