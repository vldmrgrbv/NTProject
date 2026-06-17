<?php

namespace App\Jobs;

use App\Enums\ReceiptResponseKey;
use App\Enums\ReceiptStatus;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use App\Services\Contracts\NTApiServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RegisterNTJob implements ShouldQueue
{
    use Queueable, ReceiptJobRetries;

    public function __construct(public Receipt $receipt) {}

    public function handle(NTApiServiceInterface $ntService): void
    {
        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            Log::warning('RegisterNTJob. Receipt status is REJECTED');

            return;
        }

        $registrationResult = $ntService->registerReceipt($this->receipt);

        if (isset($registrationResult['status']) && $registrationResult['status'] === 'success') {
            $this->receipt->addResponse(ReceiptResponseKey::REGISTRATION, $registrationResult);
            Log::info('RegisterNTJob. NT registration receipt success: '.json_encode($registrationResult));
            $this->receipt->update([
                'nt_number' => $registrationResult['data.number'] ?? null,
                'reason_failed' => null,
            ]);
        } else {
            $this->receipt->addResponse(ReceiptResponseKey::REGISTRATION_FAILED, $registrationResult);
            Log::error('RegisterNTJob. NT registration receipt error: '.json_encode($registrationResult));
            $errorMsg = data_get($registrationResult, 'message') ?? 'Не удалось зарегистрировать чек';
            $this->receipt->update([
                'reason_failed' => $errorMsg,
            ]);
            throw new \Exception("Failed register new receipt with ID = {$this->receipt->id}: ".$errorMsg);
        }
    }
}
