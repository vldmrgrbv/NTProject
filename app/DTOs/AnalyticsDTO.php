<?php

declare(strict_types=1);

namespace App\DTOs;

class AnalyticsDTO
{
    public function __construct(
        public int $startBotCount = 0,
        public int $authorizedUsersCount = 0,
        public int $clubPrivilegesClicks = 0,
        public int $aboutProductsClicks = 0,
        public int $whereToBuyClicks = 0,
        public int $checkEyesightClicks = 0,
        public int $askQuestionClicks = 0,
        public int $tryLensesClicks = 0,
        public int $stickerPackClicks = 0,
        public int $receiptUploadsCount = 0,
    ) {}

    public function toArray(): array
    {
        return [
            ['metric' => 'Количество заходов в бот (Start)', 'value' => $this->startBotCount],
            ['metric' => 'Количество авторизованных пользователей', 'value' => $this->authorizedUsersCount],
            ['metric' => 'Клики на кнопку «Клуб привилегий»', 'value' => $this->clubPrivilegesClicks],
            ['metric' => 'Клики на кнопку «О продуктах»', 'value' => $this->aboutProductsClicks],
            ['metric' => 'Клики на кнопку «Где купить»', 'value' => $this->whereToBuyClicks],
            ['metric' => 'Клики на кнопку «Записаться на проверку зрения»', 'value' => $this->checkEyesightClicks],
            ['metric' => 'Клики на кнопку «Задать вопрос»', 'value' => $this->askQuestionClicks],
            ['metric' => 'Клики на кнопку «Примерить цветные линзы»', 'value' => $this->tryLensesClicks],
            ['metric' => 'Клики на кнопку «Стикерпак»', 'value' => $this->stickerPackClicks],
            ['metric' => 'Количество загрузок чеков', 'value' => $this->receiptUploadsCount],
        ];
    }

    public function toExportArray(): array
    {
        return collect($this->toArray())->map(function($item) {
            return [
                'Показатель' => $item['metric'],
                'Значение' => $item['value'],
            ];
        })->toArray();
    }
}
