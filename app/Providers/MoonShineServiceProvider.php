<?php

declare(strict_types=1);

namespace App\Providers;

use App\MoonShine\Pages\AnalyticsPage;
use App\MoonShine\Resources\BotSetting\BotSettingResource;
use App\MoonShine\Resources\Log\LogResource;
use App\MoonShine\Resources\MoonShineUser\MoonShineUserResource;
use App\MoonShine\Resources\MoonShineUserRole\MoonShineUserRoleResource;
use App\MoonShine\Resources\Receipt\ReceiptResource;
use App\MoonShine\Resources\ReceiptPhoto\ReceiptPhotoResource;
use App\MoonShine\Resources\User\UserResource;
use App\MoonShine\Resources\UserEvent\UserEventResource;
use Illuminate\Support\ServiceProvider;
use MoonShine\Contracts\Core\DependencyInjection\CoreContract;
use MoonShine\Laravel\DependencyInjection\MoonShineConfigurator;

class MoonShineServiceProvider extends ServiceProvider
{
    /**
     * @param  CoreContract<MoonShineConfigurator>  $core
     */
    public function boot(CoreContract $core): void
    {
        $core
            ->resources([
                MoonShineUserResource::class,
                MoonShineUserRoleResource::class,
                ReceiptResource::class,
                ReceiptPhotoResource::class,
                UserResource::class,
                BotSettingResource::class,
                LogResource::class,
                UserEventResource::class,
            ])
            ->pages([
                AnalyticsPage::class,
                ...$core->getConfig()->getPages(),
            ]);
    }
}
