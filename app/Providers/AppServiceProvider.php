<?php

namespace App\Providers;

use App\Services\NTApiService;
use App\Services\NTCheckService;
use App\Services\Contracts\NTApiServiceInterface;
use App\Services\Contracts\FnsServiceInterface;
use App\Services\Contracts\ReceiptRecognitionServiceInterface;
use App\Services\FnsService;
use App\Services\NotifyService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            ReceiptRecognitionServiceInterface::class,
            NTCheckService::class
        );

        $this->app->singleton(
            NTApiServiceInterface::class,
            NTApiService::class
        );

        $this->app->singleton(
            FnsServiceInterface::class,
            FnsService::class
        );

        $this->app->singleton('notify.service', function () {
            return new NotifyService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('viewApiDocs', fn ($user = null) => app()->environment(['local', 'staging']));
    }
}
