<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\AnalyticsDTO;
use App\Enums\MaxBot\BotButton;
use App\Enums\UserEventType;
use App\Models\Receipt;
use App\Models\UserEvent;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    public function getAnalytics(?Carbon $dateFrom = null, ?Carbon $dateTo = null): AnalyticsDTO
    {
        $query = UserEvent::query();

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom->copy()->startOfDay());
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo->copy()->endOfDay());
        }

        // Количество чеков отсканированных через чат-бот
        $receiptsQuery = Receipt::query()
            ->whereHas('user.maxUser');
        if ($dateFrom) {
            $receiptsQuery->where('created_at', '>=', $dateFrom->copy()->startOfDay());
        }
        if ($dateTo) {
            $receiptsQuery->where('created_at', '<=', $dateTo->copy()->endOfDay());
        }
        $receiptUploadsCount = $receiptsQuery->count();

        $data = new AnalyticsDTO;
        $data->receiptUploadsCount = $receiptUploadsCount;

        $eventsQuery = $query->with('user')
            ->select('event_type', 'payload', 'user_id');

        // Количество авторизованных пользователей
        $eventsAuthQuery = clone $eventsQuery;
        $eventAuthCount = $eventsAuthQuery
            ->where('event_type', UserEventType::AUTH->value)
            ->whereHas('user', fn ($query) => $query->where('is_authorized', true))
            ->whereJsonContains('payload', true)
            ->distinct('user_id')
            ->get()
            ->count();
        $data->authorizedUsersCount = $eventAuthCount;

        foreach ($eventsQuery->get() as $event) {
            $type = $event->event_type;
            $payload = $event->payload;

            // Количество заходов в бот
            if ($type === UserEventType::START) {
                $data->startBotCount++;
            }

            // Клики по кнопкам
            if ($type === UserEventType::BUTTON_CLICK && isset($payload['button'])) {
                $button = $payload['button'];

                match ($button) {
                    BotButton::CLUB_PRIVILEGES->value => $data->clubPrivilegesClicks++,
                    BotButton::ABOUT_PRODUCTS->value => $data->aboutProductsClicks++,
                    BotButton::WHERE_TO_BUY->value => $data->whereToBuyClicks++,
                    BotButton::CHECK_EYESIGHT->value => $data->checkEyesightClicks++,
                    BotButton::ASK_QUESTION->value => $data->askQuestionClicks++,
                    BotButton::TRY_LENSES->value => $data->tryLensesClicks++,
                    BotButton::STICKER_PACK->value => $data->stickerPackClicks++,
                    default => null,
                };
            }
        }

        return $data;
    }
}
