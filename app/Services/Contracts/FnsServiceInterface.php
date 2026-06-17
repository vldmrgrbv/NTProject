<?php

namespace App\Services\Contracts;

use App\Models\Receipt;

interface FnsServiceInterface
{
    /**
     * Проверить чек в ФНС.
     */
    public function checkReceipt(Receipt $receipt): array;
}
