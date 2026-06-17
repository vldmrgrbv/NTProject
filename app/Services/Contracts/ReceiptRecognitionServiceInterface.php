<?php

namespace App\Services\Contracts;

use App\Models\Receipt;

interface ReceiptRecognitionServiceInterface
{
    /**
     * Отправить фото чека на распознавание.
     *
     * @return array Результат ответа от API
     */
    public function sendToRecognition(Receipt $receipt): array;
}
