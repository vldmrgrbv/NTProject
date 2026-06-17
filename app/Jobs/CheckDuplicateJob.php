<?php

namespace App\Jobs;

use App\Enums\ReceiptStatus;
use App\Facades\Notify;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckDuplicateJob implements ShouldQueue
{
    use Queueable, ReceiptJobRetries;

    public function __construct(public Receipt $receipt) {}

    public function handle(): void
    {
        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            return;
        }

        $duplicate = Receipt::where('id', '!=', $this->receipt->id)
            ->where('fn', $this->receipt->fn)
            ->where('fd', $this->receipt->fd)
            ->where('fp', $this->receipt->fp)
            ->whereIn('status', [ReceiptStatus::ACCEPTED, ReceiptStatus::PROCESSING])
            ->first();

        if ($duplicate) {
            Log::error("CheckDuplicateJob. Duplicate receipt found for receipt {$this->receipt->id}. Duplicate: ", [$duplicate->toArray()]);
            $this->failReceipt('Такой чек уже существует');
            Notify::setNotifyMessage($this->receipt->user, __('bot.notifications.duplicate', ['num' => formatWithLeadingZeros($this->receipt->id)]));
        }
    }

    protected function failReceipt(string $reason): void
    {
        Log::warning('CheckDuplicateJob. Recognition error: '.$reason);

        $reasonFailed = $this->receipt->reason_failed ? $this->receipt->reason_failed . ' | ' . $reason : $reason;
        $this->receipt->update([
            'status' => ReceiptStatus::REJECTED,
            'reason_failed' => $reasonFailed,
        ]);
    }
}
