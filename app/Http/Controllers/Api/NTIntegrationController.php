<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Contracts\NTApiServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NTIntegrationController extends Controller
{
    public function __construct(
        protected NTApiServiceInterface $ntService
    ) {}

    /**
     * Получить список чеков.
     */
    public function getReceipts(Request $request): JsonResponse
    {
        $phone = $request->user()->phone;
        $result = $this->ntService->ntGetReceipts($phone);

        return response()->json($result);
    }

    /**
     * Получить детали чека.
     */
    public function getReceipt(Request $request, $receiptId): JsonResponse
    {
        $phone = $request->user()->phone;
        $result = $this->ntService->ntGetReceipt($phone, $receiptId);

        return response()->json($result);
    }
}
