<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\NTCheckStatus;
use App\Enums\ReceiptResponseKey;
use App\Enums\ReceiptStatus;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use App\Services\Contracts\NTApiServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CheckNTIntegrationJob implements ShouldQueue
{
    use Queueable, ReceiptJobRetries;

    public function __construct(
        public Receipt $receipt
    ) {}

    public function handle(NTApiServiceInterface $ntService): void
    {
        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            Log::warning("CheckNTIntegrationJob. Receipt ID {$this->receipt->id} status is REJECTED");
            return;
        }

        if (! $this->receipt->nt_number) {
            Log::warning("CheckNTIntegrationJob. Receipt ID {$this->receipt->id} has no nt_number");
            return;
        }

        $phone = $this->receipt->user->phone;
        $receiptId = $this->receipt->nt_number;

        $result = $ntService->ntGetReceipt($phone, $receiptId);
        $resultStr = json_encode($result, JSON_UNESCAPED_UNICODE);

        $this->receipt->addResponse(ReceiptResponseKey::INTEGRATION_CHECK, $result);

        $status = data_get($result, 'status');
        $checkStatus = data_get($result, 'data.check.status');

        if ($checkStatus === NTCheckStatus::PROCESSED->value) {
            Log::info("CheckNTIntegrationJob. Receipt ID {$this->receipt->id} status is NEW");
            $this->receipt->update([
                'status' => ReceiptStatus::ACCEPTED,
                'reason_failed' => null,
            ]);

            return;
        }

        if ($checkStatus === NTCheckStatus::NEW->value) {
            Log::warning("CheckNTIntegrationJob. Receipt ID {$this->receipt->id} failed. Status: {$status}. Response: {$resultStr}");
            throw new \Exception("Integration check failed for receipt ID {$this->receipt->id}. Status: {$status}. Response: {$resultStr}");
        }

        Log::error("CheckNTIntegrationJob. Receipt ID {$this->receipt->id} failed. Status: {$status}. Response: {$resultStr}");
        throw new \Exception("Integration check failed for receipt ID {$this->receipt->id}. Status: {$status}. Response: {$resultStr}");
    }
}
