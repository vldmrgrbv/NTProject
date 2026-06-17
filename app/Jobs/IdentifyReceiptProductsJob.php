<?php

namespace App\Jobs;

use App\Enums\ReceiptStatus;
use App\Facades\Notify;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use App\Services\ProductMatchingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IdentifyReceiptProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ReceiptJobRetries, SerializesModels;

    public function __construct(public Receipt $receipt) {}

    public function handle(ProductMatchingService $matchingService): void
    {
        if ($this->receipt->status === ReceiptStatus::REJECTED) {
            Log::warning("IdentifyReceiptProductsJob. Receipt ID {$this->receipt->id} status is REJECTED");
            return;
        }

        // Если уже обработано
        if ($this->receipt->is_network) {
            return;
        }

        // Если еще не отправляли
        if (! $this->receipt->network_id) {
            $networkId = $matchingService->matchReceiptItems($this->receipt);

            if (! $networkId) {
                // Если не удалось отправить, пробуем позже через ретрай джобы
                Log::warning("IdentifyReceiptProductsJob. Failed to send receipt {$this->receipt->id} to matching service");
                throw new \Exception("Failed to send receipt {$this->receipt->id} to matching service");
            }

            $this->receipt->update(['network_id' => $networkId]);

            // Откладываем выполнение, чтобы дать сервису время обработать
            $this->release(60);
            return;
        }

        // Если network_id есть, проверяем результат
        $result = $matchingService->getMatchingResult($this->receipt, $this->receipt->network_id);

        if (! $result) {
            Log::warning("IdentifyReceiptProductsJob. Failed to get matching result for receipt {$this->receipt->id}, network_id: {$this->receipt->network_id}");
            throw new \Exception("Failed to get matching result for receipt {$this->receipt->id}, network_id: {$this->receipt->network_id}");
        }

        $status = $result['status'] ?? 'Unknown';

        if (in_array($status, ['Suspended', 'Idle', 'Running'])) {
            // Еще в процессе обработки
            $this->release(60);
            return;
        }

        if ($status === 'Finished') {
            $skus = $matchingService->processMatchingResult($this->receipt, $result);

            if (empty($skus)) {
                Notify::setNotifyMessage($this->receipt->user, __('bot.notifications.nt_not_found', ['num' => formatWithLeadingZeros($this->receipt->id)]));
            }

            $this->receipt->update([
                'skus' => $skus,
                'is_network' => true,
            ]);

            Log::info('IdentifyReceiptProductsJob. Successfully identified '.count($skus)." company products for receipt {$this->receipt->id}");
            return;
        }

        // Если статус какой-то другой
        Log::error("IdentifyReceiptProductsJob. Matching service returned status {$status} for receipt {$this->receipt->id}");
        $errText = 'При попытках получить SKUS по чеку, через NETWORK_URL, получили статус: '.$status;
        $reasonFailed = $this->receipt->reason_failed ? $this->receipt->reason_failed . ' | ' . $errText : $errText;
        $this->receipt->update([
            'is_network' => true, // Помечаем как обработанный, чтобы не зациклиться
            'reason_failed' => $reasonFailed,
        ]);
    }
}
