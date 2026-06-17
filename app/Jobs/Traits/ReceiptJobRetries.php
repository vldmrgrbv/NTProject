<?php

namespace App\Jobs\Traits;

trait ReceiptJobRetries
{
    /**
     * Количество попыток выполнения задачи.
     */
    public function tries(): int
    {
        return config('nt.queue.tries', 5);
    }

    /**
     * Количество секунд ожидания перед повторной попыткой.
     */
    public function backoff(): int
    {
        return config('nt.queue.backoff', 43200);
    }
}
