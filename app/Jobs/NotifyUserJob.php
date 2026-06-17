<?php

namespace App\Jobs;

use App\Enums\ReceiptStatus;
use App\Facades\Notify;
use App\Models\Receipt;
use App\Services\MaxBot\MaxBotApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyUserJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Receipt $receipt,
        public string $message = ''
    ) {}

    public function handle(MaxBotApiService $maxBotApi): void
    {
        $user = $this->receipt->user;
        $chatId = $user->maxUser?->chat_id;

        if (! $chatId) {
            Log::warning("NotifyUserJob. User {$user->id} has no chat_id. Cannot send notification.");

            return;
        }

        $status = $this->receipt->status;
        $message = $this->message;
        $message = match ($status) {
            ReceiptStatus::PROCESSING => __('bot.receipt.processing', ['num' => formatWithLeadingZeros($this->receipt->id)]),
            ReceiptStatus::ACCEPTED => __('bot.receipt.accepted', ['num' => formatWithLeadingZeros($this->receipt->id)]),
            ReceiptStatus::REJECTED => __('bot.notifications.failed', ['num' => formatWithLeadingZeros($this->receipt->id)]),
            default => $message,
        };

        Log::info("NotifyUserJob. Sending notification to user {$user->phone} (ID: {$user->id}) about receipt {$this->receipt->id}. Status: {$status->value}. Message: {$message}");

        if ($this->receipt->reason_failed) {
            Log::warning("NotifyUserJob. Sending notification to user. Failed procession receipt. Reason: {$this->receipt->reason_failed}");
            $message = __('bot.notifications.failed', ['num' => formatWithLeadingZeros($this->receipt->id)]);
        }

        if (! is_null($user->notify_message)) {
            $message = $user->notify_message;
        }

        Notify::resetNotifyMessage($user);

        $maxBotApi->sendMessage($chatId, $message);
    }
}
