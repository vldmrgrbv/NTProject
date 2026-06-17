<?php

namespace App\Services\MaxBot;

use BushlanovDev\MaxMessengerBot\Api;
use BushlanovDev\MaxMessengerBot\Enums\MessageFormat;
use BushlanovDev\MaxMessengerBot\Models\Message;
use Illuminate\Support\Facades\Log;
use Throwable;

class MaxBotApiService
{
    public function __construct(
        private Api $api
    ) {}

    /**
     * Send a message to a chat.
     */
    public function sendMessage(string|int $chatId, string $text, array $attachments = []): ?Message
    {
        try {
            $message = $this->api->sendChatMessage(
                chatId: (int) $chatId,
                text: $text,
                attachments: $attachments,
                format: MessageFormat::Markdown
            );

            Log::info('MAX Bot API (sendMessage): ', [
                'chat_id' => $chatId,
                'text' => $text,
                'attachments' => $attachments,
            ]);

            return $message;
        } catch (Throwable $e) {
            Log::error('MAX Bot API Error (sendMessage): '.$e->getMessage(), [
                'chat_id' => $chatId,
                'text' => $text,
                'attachments' => $attachments,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Register webhook URL.
     */
    public function subscribe(string $url): bool
    {
        try {
            $this->api->subscribe($url);

            Log::info('MAX Bot API (subscribe) success!');

            return true;
        } catch (Throwable $e) {
            Log::error('MAX Bot API Error (subscribe): '.$e->getMessage(), [
                'url' => $url,
                'exception' => $e,
            ]);

            return false;
        }
    }
}
