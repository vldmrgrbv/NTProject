<?php

namespace App\Services\MaxBot;

use App\Enums\MaxBot\BotButton;
use App\Enums\UserEventType;
use App\Models\BotSetting;
use App\Models\MaxUser;
use App\Models\User;
use App\Models\UserEvent;
use BushlanovDev\MaxMessengerBot\Laravel\MaxBotManager;
use BushlanovDev\MaxMessengerBot\Models\Attachments\Buttons\Inline\CallbackButton;
use BushlanovDev\MaxMessengerBot\Models\Attachments\Buttons\Inline\LinkButton;
use BushlanovDev\MaxMessengerBot\Models\Attachments\Requests\InlineKeyboardAttachmentRequest;
use BushlanovDev\MaxMessengerBot\Models\Attachments\Requests\PhotoAttachmentRequest;
use BushlanovDev\MaxMessengerBot\Models\Updates\MessageCallbackUpdate;
use BushlanovDev\MaxMessengerBot\Models\Updates\MessageCreatedUpdate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MaxBotWebhookService
{
    public function __construct(
        private MaxBotApiService $apiService,
        private MaxBotHandleService $handleService,
    ) {}

    /**
     * Register handlers for MAX Bot Manager.
     */
    public function registerHandlers(MaxBotManager $botManager): void
    {
        // Handle /start command
        $botManager->onCommand('/start', function (MessageCreatedUpdate $update) {
            $this->sendStartMessage($update);
        });

        // Handle bot start
        $botManager->onBotStarted(function (MessageCreatedUpdate $update) {
            $this->sendStartMessage($update);
        });

        // Handle button callbacks
        $botManager->onMessageCallback(function (MessageCallbackUpdate $update) {
            $this->sendCallbackMessage($update);
        });

        // Handle plain text messages (that are not commands)
        $botManager->onMessageCreated(function (MessageCreatedUpdate $update) {
            $this->sendCreatedMessage($update);
        });
    }

    private function sendStartMessage(MessageCreatedUpdate $update): void
    {
        $chatId = $update->message->recipient?->chatId;
        $maxUser = $update->message->sender;
        $maxUserId = $maxUser?->userId;
        Log::info('MAX Bot API (/start or botStarted): ', ['chat_id' => $chatId]);
        if ($chatId && $maxUser) {
            $this->findOrCreateUser([
                'user_id' => $maxUser->userId,
                'chat_id' => $chatId,
                'first_name' => $maxUser->firstName,
                'last_name' => $maxUser->lastName,
                'username' => $maxUser->username,
            ]);
        }

        $text = BotSetting::getValue('start_message', __('bot.responses.main.text'));
        Log::info('MAX Bot API (sendStartMessage): ', ['chat_id' => $chatId, 'text' => $text]);

        $user = null;
        if ($maxUserId) {
            $user = User::whereHas('maxUser', fn ($query) => $query->where('max_id', $maxUserId))->first();
            if ($user) {
                UserEvent::create([
                    'user_id' => $user->id,
                    'event_type' => UserEventType::START,
                ]);
            }
        }

        $buttonKeys = BotButton::MAIN->responseButtons();
        $buttons = $this->getButtonsByKeys($buttonKeys, $user?->id);

        $attachments = [];
        if (! empty($buttons)) {
            $attachments[] = new InlineKeyboardAttachmentRequest($buttons);
        }

        $botSetting = BotSetting::where('key', 'start_message')->first();
        if ($botSetting && Storage::disk('s3')->exists($botSetting->image_path)) {
            $imageUrl = Storage::disk('s3')->url($botSetting->image_path);
            $attachments[] = PhotoAttachmentRequest::fromUrl($imageUrl);
        }

        $this->apiService->sendMessage($chatId, $text, $attachments);
    }

    private function sendCallbackMessage(MessageCallbackUpdate $update): void
    {
        $chatId = $update->message?->recipient?->chatId;
        $maxUser = $update->message?->recipient;
        $payload = $update->callback->payload;

        Log::info('MAX Bot API (onMessageCallback): ', [
            'chat_id' => $chatId,
            'max_user_id' => $maxUser->userId,
            'payload' => $payload,
        ]);

        if ($maxUser) {
            $this->findOrCreateUser([
                'user_id' => $maxUser->userId,
                'chat_id' => $chatId,
            ]);
        }

        if ($chatId && $payload) {
            $this->handleButtonAction($chatId, $payload, $maxUser->userId);
        }
    }

    private function sendCreatedMessage(MessageCreatedUpdate $update): void
    {
        $text = $update->message->body?->text;
        $chatId = $update->message->recipient?->chatId;
        $maxUser = $update->message->sender;

        Log::info('MAX Bot API (onMessageCreated): ', [
            'chat_id' => $chatId,
            'max_user_id' => $maxUser->userId,
            'text' => $text,
        ]);

        if ($maxUser) {
            $this->findOrCreateUser([
                'user_id' => $maxUser->userId,
                'chat_id' => $chatId,
                'first_name' => $maxUser->firstName,
                'last_name' => $maxUser->lastName,
                'username' => $maxUser->username,
            ]);
        }

        if (! $chatId || ! $text) {
            return;
        }

        // Handle text input that matches button labels
        foreach (BotButton::cases() as $button) {
            if ($text === $button->label()) {
                $this->sendButtonResponse($chatId, $button, $maxUser->userId);

                return;
            }
        }

        // Default response for any other text
        $this->sendDefaultResponse($chatId, $maxUser->userId);
    }

    private function handleButtonAction(string|int $chatId, string $action, ?int $maxUserId = null): void
    {
        Log::info('MAX Bot API (handleButtonAction): ', ['chat_id' => $chatId, 'action' => $action]);
        $button = BotButton::tryFrom($action);
        if ($button) {
            $this->sendButtonResponse($chatId, $button, $maxUserId);
        }
    }

    private function sendButtonResponse(string|int $chatId, BotButton $button, ?int $maxUserId = null): void
    {
        Log::info('MAX Bot API (sendButtonResponse): ', ['chat_id' => $chatId, 'button' => $button->value, 'max_user_id' => $maxUserId]);
        // Логирование события нажатия кнопки
        $user = null;
        if ($maxUserId) {
            $user = User::whereHas('maxUser', fn ($query) => $query->where('max_id', $maxUserId))->first();
            if ($user) {
                UserEvent::create([
                    'user_id' => $user->id,
                    'event_type' => UserEventType::BUTTON_CLICK,
                    'payload' => ['button' => $button->value],
                ]);
            }
        }

        // Проверка авторизации для защищенных кнопок
        $protectedButtons = [BotButton::UPLOAD_RECEIPT, BotButton::SCORE_BALANCE, BotButton::EXCHANGE_POINTS];
        $authUser = null;
        if ($maxUserId) {
            $authUser = User::where('is_authorized', true)
                ->whereHas('maxUser', fn ($query) => $query->where('max_id', $maxUserId))
                ->first();
        }

        if (in_array($button, $protectedButtons) && ! $authUser) {
            $text = __('bot.responses.not_authorized.text');
            $buttonKeys = __('bot.responses.not_authorized.buttons');
        } else {
            $text = $button->responseText() ?? '';
            $buttonKeys = $button->responseButtons();

            if ($button === BotButton::SCORE_BALANCE && $authUser) {
                $scores = formatScores($authUser->getScores());
                $text = BotButton::SCORE_BALANCE_LESS->responseText() ?? '';
                $buttonKeys = BotButton::SCORE_BALANCE_LESS->responseButtons();
                if ($scores >= config('nt.max-bot.score_balance_limit')) {
                    $text = BotButton::SCORE_BALANCE_MORE->responseText() ?? '';
                    $buttonKeys = BotButton::SCORE_BALANCE_MORE->responseButtons();
                }
                $text = str_replace(':scores', $scores, $text);
            }
        }

        $url = $button->responseUrl();
        Log::info('MAX Bot API (sendButtonResponse): ', [
            'chat_id' => $chatId,
            'button' => $button->value,
            'text' => $text,
            'url' => $url,
            'buttons' => $buttonKeys,
        ]);

        $attachments = [];
        // Если есть вложенные кнопки
        if (! empty($buttonKeys)) {
            $buttons = $this->getButtonsByKeys($buttonKeys, $user?->id);
            if (! empty($buttons)) {
                $attachments[] = new InlineKeyboardAttachmentRequest($buttons);
            }
        }

        $this->apiService->sendMessage($chatId, $text ?: $button->label(), $attachments);
    }

    /**
     * @param  array<string>  $keys
     * @return array<array<LinkButton|CallbackButton>>
     */
    private function getButtonsByKeys(array $keys, ?int $userId): array
    {
        $rows = [];
        foreach ($keys as $key) {
            $enumButton = BotButton::tryFrom($key);
            if (! $enumButton) {
                continue;
            }

            $url = $enumButton->responseUrl();

            // Если у кнопки есть URL - это LinkButton, иначе CallbackButton для открытия подменю или показа текста
            if ($url) {
                $url = $this->handleService->generateRedirectUrl([
                    'user_id' => $userId,
                    'button_value' => $enumButton->value,
                ]);
                $rows[] = [new LinkButton($enumButton->label(), $url)];
            } else {
                $rows[] = [new CallbackButton($enumButton->label(), $enumButton->value)];
            }
        }

        return $rows;
    }

    private function sendDefaultResponse(string|int $chatId, ?int $maxUserId = null): void
    {
        Log::info('MAX Bot API (sendDefaultResponse): ', ['chat_id' => $chatId]);
        $this->sendButtonResponse($chatId, BotButton::ASK_QUESTION, $maxUserId);
    }

    private function findOrCreateUser(array $maxUserData): User
    {
        $maxId = $maxUserData['user_id'];
        $maxUser = MaxUser::where('max_id', $maxId)->first();

        if ($maxUser) {
            return $maxUser->user;
        }

        try {
            $user = User::create();
            MaxUser::create([
                'user_id' => $user->id,
                'max_id' => $maxId,
                'chat_id' => $maxUserData['chat_id'] ?? null,
                'first_name' => $maxUserData['first_name'] ?? null,
                'second_name' => $maxUserData['last_name'] ?? null,
                'username' => $maxUserData['username'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('MAX Bot API (findOrCreateUser): '.$e->getMessage());
            throw $e;
        }

        return $user;
    }
}
