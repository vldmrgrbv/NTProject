<?php

namespace App\Jobs;

use App\Enums\ReceiptResponseKey;
use App\Enums\ReceiptStatus;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use App\Services\Contracts\FnsServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckFnsJob implements ShouldQueue
{
    use Queueable, ReceiptJobRetries;

    public function __construct(public Receipt $receipt) {}

    public function handle(FnsServiceInterface $fnsService): void
    {
        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            Log::warning('CheckFnsJob. Receipt status is REJECTED');

            return;
        }

        $fnsResult = $fnsService->checkReceipt($this->receipt);
        Log::info('CheckFnsJob. FNS check result: '.json_encode($fnsResult));

        $statusCode = $fnsResult['status_code'] ?? 500;

        if ($statusCode === 200) {
            $this->receipt->addResponse(ReceiptResponseKey::FNS, $fnsResult['body']);
            $this->receipt->update([
                'inn' => data_get($fnsResult, 'body.data.content.userInn'),
            ]);

            return;
        }

        $retryCodes = [
            404, 406,
            452, 453, 454, 455,
            503,
            527, 528, 529, 530, 531, 532, 533,
        ];

        if (in_array($statusCode, $retryCodes)) {
            Log::warning("CheckFnsJob. FNS service returned retryable status code: {$statusCode}");
            throw new \Exception("FNS service returned retryable status code: {$statusCode}");
        }

        $errText = 'Не прошел ФНС: '.($fnsResult['body']['message'] ?? $fnsResult['message'] ?? 'Unknown error');
        $reasonFailed = $this->receipt->reason_failed ? $this->receipt->reason_failed . ' | ' . $errText : $errText;
        $this->receipt->update([
            'status' => ReceiptStatus::REJECTED,
            'reason_failed' => $reasonFailed,
        ]);
    }
}
