<?php

namespace App\Jobs;

use App\Enums\ReceiptStatus;
use App\Facades\Notify;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckBlacklistInnJob implements ShouldQueue
{
    use Queueable, ReceiptJobRetries;

    public function __construct(public Receipt $receipt) {}

    public function handle(): void
    {
        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            return;
        }

        $inn = $this->receipt->inn;
        $blacklist = config('nt.blacklist_inn');

        if (!$inn) {
            $this->receiptRejected('Отсутствует ИНН в чеке');
            throw new \Exception("CheckBlacklistInnJob. Failed check inn receipt for blacklist. Receipt ID: {$this->receipt->id}, Inn: {$inn}");
        }

        if (in_array($inn, $blacklist)) {
            Log::error("CheckBlacklistInnJob. Inn receipt is on the blacklist. Receipt ID: {$this->receipt->id}. Inn: {$inn}. Blacklist: ", [$blacklist]);
            $errTxt = 'Чек находится в черном списке организаций';
            $this->receiptRejected($errTxt);
            Notify::setNotifyMessage($this->receipt->user, __('bot.notifications.blacklist', ['num' => formatWithLeadingZeros($this->receipt->id)]));
            return;
        }

        $this->receiptProcessed();
    }

    protected function receiptRejected(string $errTxt): void
    {
        $reasonFailed = $this->receipt->reason_failed ? $this->receipt->reason_failed . ' | ' . $errTxt : $errTxt;
        $this->receipt->update([
            'status' => ReceiptStatus::REJECTED,
            'reason_failed' => $reasonFailed,
        ]);
    }

    protected function receiptProcessed(): void
    {
        $this->receipt->update([
            'status' => ReceiptStatus::PROCESSING,
        ]);
    }
}
