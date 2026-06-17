<?php

namespace App\Jobs;

use App\Enums\ReceiptResponseKey;
use App\Enums\ReceiptStatus;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use App\Services\Contracts\NTApiServiceInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateStatusCheckJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ReceiptJobRetries, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Receipt $receipt) {}

    /**
     * Execute the job.
     */
    public function handle(NTApiServiceInterface $ntService): void
    {
        Log::info("UpdateStatusCheckJob. Starting UpdateStatusCheckJob for receipt {$this->receipt->id}");

        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            Log::warning('UpdateStatusCheckJob. Receipt status is REJECTED');

            return;
        }

        $status = 'processed';
        $productsUnrecognized = [];
        $errorText = '';

        if (empty($this->receipt->skus)) {
            $status = 'to_check';
            $productsUnrecognized = $this->productsUnrecognized();
            $errorText = 'Не распознан товар в чеке';

            $errText = 'Не найдены skus NT';
            $reasonFailed = $this->receipt->reason_failed ? $this->receipt->reason_failed.' | '.$errText : $errText;
            $this->receipt->update([
                'status' => ReceiptStatus::REJECTED,
                'reason_failed' => $reasonFailed,
            ]);
            Log::warning('UpdateStatusCheckJob. Receipt SKUS for NT is empty, sending to_check to NT API');
        }

        $result = $ntService->updateStatusCheck($this->receipt, $status, $productsUnrecognized, $errorText);

        if (isset($result['status']) && $result['status'] === false) {
            Log::warning("UpdateStatusCheckJob. Failed to update status for receipt {$this->receipt->id}: ".($result['message'] ?? 'Unknown error'));
            throw new \Exception("Failed to update status for receipt {$this->receipt->id}: ".($result['message'] ?? 'Unknown error'));
        }

        $this->receipt->addResponse(ReceiptResponseKey::UPDATE_STATUS_CHECK, $result);
        $this->receipt->update([
            'nt_number' => data_get($result, 'data.number') ?? ($result['number'] ?? null),
            'reason_failed' => $status === 'processed' ? null : $this->receipt->reason_failed,
        ]);

        Log::info("UpdateStatusCheckJob. Successfully updated status for receipt {$this->receipt->id} in NT API");
    }

    private function productsUnrecognized(): array
    {
        $items = data_get($this->receipt->responses, 'fns.data.content.items') ?? [];

        return array_map(static fn (array $item): array => [
            'brand' => (string) ($item['name'] ?? ''),
            'quantity' => (int) ($item['quantity'] ?? 0),
        ], $items);
    }
}
