<?php

declare(strict_types=1);

namespace App\MoonShine\Handlers;

use MoonShine\ImportExport\ExportHandler;
use MoonShine\UI\Exceptions\ActionButtonException;
use Symfony\Component\HttpFoundation\Response;

class CleanupExportHandler extends ExportHandler
{
    /**
     * @throws ActionButtonException
     */
    public function handle(): Response
    {
        $response = parent::handle();

        // Регистрируем удаление файла после отправки ответа
        register_shutdown_function(function () use ($response) {
            $file = $response->getFile();
            if ($file && $file->isFile()) {
                unlink($file->getPathname()); // Удаляем физический файл
            }
        });

        return $response;
    }
}
