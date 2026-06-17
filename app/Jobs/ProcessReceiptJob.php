<?php

namespace App\Jobs;

use App\Enums\ReceiptResponseKey;
use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use App\Facades\Notify;
use App\Jobs\Traits\ReceiptJobRetries;
use App\Models\Receipt;
use App\Services\Contracts\ReceiptRecognitionServiceInterface;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, ReceiptJobRetries, SerializesModels;

    public function __construct(public Receipt $receipt) {}

    public function handle(ReceiptRecognitionServiceInterface $recognitionService): void
    {
        $this->receipt->update(['status' => ReceiptStatus::PROCESSING]);
        NotifyUserJob::dispatch($this->receipt);

        if ($this->receipt->source === ReceiptSource::UPLOAD) {
            // 1. Отправка на распознавание фото
            $recognitionResult = $recognitionService->sendToRecognition($this->receipt);
            $recognitionResultStr = json_encode($recognitionResult);

            Log::info('ProcessReceiptJob. Recognition result: '.$recognitionResultStr);
            $this->receipt->addResponse(ReceiptResponseKey::RECOGNITION, $recognitionResult);

            if ($recognitionResult['status_code'] !== 200) {
                $this->failReceipt('Ошибка распознавания: '.($recognitionResult['message'] ?? 'Unknown error'));
                throw new \Exception("NTCheckService sendToRecognition(): . $recognitionResultStr");
            }

            $body = $recognitionResult['body'];

            // 2. Обновление данных чека
            $this->receipt->update([
                'fn' => $body['fn'] ?? $this->receipt->fn,
                'fd' => $body['fd'] ?? $this->receipt->fd,
                'fp' => $body['fp'] ?? $this->receipt->fp,
                'sum' => $body['sum'] ?? $this->receipt->sum,
                'dt' => $body['date'] ?? $this->receipt->dt,
            ]);
        }

        // 3. Проверка даты (не более 30 дней)
        if ($this->receipt->dt) {
            try {
                $receiptDate = Carbon::parse($this->receipt->dt);
                if ($receiptDate->diffInDays(Carbon::now()) > 30) {
                    $this->failReceipt('Чек старше 30 дней');
                    Notify::setNotifyMessage($this->receipt->user, __('bot.notifications.limit_days', ['num' => formatWithLeadingZeros($this->receipt->id)]));
                    return;
                }
            } catch (\Exception $e) {
                Log::warning('ProcessReceiptJob. Could not parse receipt date: '.$this->receipt->dt);
            }
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ProcessReceiptJob. Job failed: '.$exception->getMessage());
    }

    protected function failReceipt(string $reason): void
    {
        Log::warning('ProcessReceiptJob. Recognition error: '.$reason);

        $reasonFailed = $this->receipt->reason_failed ? $this->receipt->reason_failed . ' | ' . $reason : $reason;
        $this->receipt->update([
            'status' => ReceiptStatus::REJECTED,
            'reason_failed' => $reasonFailed,
        ]);
    }
}
