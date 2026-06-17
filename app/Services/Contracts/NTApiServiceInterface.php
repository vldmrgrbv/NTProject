<?php

namespace App\Services\Contracts;

use App\Models\Receipt;

interface NTApiServiceInterface
{
    public function sendCode(string $phone): array;

    public function checkUser(string $phone): array;

    public function registerUser(array $data): array;

    public function auth(string $phone): array;

    public function integrationRegSend(string $phone): array;

    public function integrationAuthSend(string $phone): array;

    public function getScores(string $phone): array;

    /**
     * Зарегистрировать чек во внутреннем API NT.
     */
    public function registerReceipt(Receipt $receipt): array;

    public function updateStatusCheck(
        Receipt $receipt,
        string $status = 'processed',
        array $productsUnrecognized = [],
        string $errorText = ''
    ): array;

    public function ntGetOrders(string $phone): array;

    public function ntGetOrder(string $phone, string|int $orderId): array;

    public function ntGetReceipts(string $phone): array;

    public function ntGetReceipt(string $phone, string|int $receiptId): array;
}
