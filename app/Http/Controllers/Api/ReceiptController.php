<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\SubmitManualRequest;
use App\Http\Requests\Api\SubmitQrRequest;
use App\Http\Requests\Api\UploadPhotoRequest;
use App\Http\Resources\ReceiptResource;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * Загрузка фото чека.
     */
    public function uploadPhoto(UploadPhotoRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->receiptService->checkLimits($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Ты можешь отсканировать и сфотографировать не более 2 чеков в день и не более 4 в месяц. Общая сумма баллов по чекам за текущий квартал не должна превышать 2000 баллов',
            ], 403);
        }

        // TODO. Поменять на Storage s3
        $path = $request->file('photo')->store('receipts', 'public');

        $receipt = $this->receiptService->createTemporaryReceipt($user);
        $this->receiptService->addPhoto($receipt, $path);
        $this->receiptService->submitForProcessing($receipt);

        return response()->json([
            'success' => true,
            'message' => 'Чек принят в обработку',
            'receipt_id' => $receipt->id,
        ]);
    }

    /**
     * Загрузка через QR-строку.
     */
    public function submitQr(SubmitQrRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->receiptService->checkLimits($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Ты можешь отсканировать и сфотографировать не более 2 чеков в день и не более 4 в месяц. Общая сумма баллов по чекам за текущий квартал не должна превышать 2000 баллов',
            ], 403);
        }

        $parsed = $this->receiptService->parseQrString($request->qr_string);
        if (! $parsed) {
            return response()->json([
                'success' => false,
                'message' => 'Не удалось распознать QR-строку. Убедитесь что QR отсканирован корректно',
            ], 403);
        }

        $receipt = $user->receipts()->create([
            'source' => ReceiptSource::QR,
            'qr_string' => $request->qr_string,
            'sum' => data_get($parsed, 'sum'),
            'dt' => data_get($parsed, 'dt'),
            'fn' => data_get($parsed, 'fn'),
            'fd' => data_get($parsed, 'fd'),
            'fp' => data_get($parsed, 'fp'),
            'status' => ReceiptStatus::PENDING,
        ]);

        $this->receiptService->submitForProcessing($receipt);

        return response()->json([
            'success' => true,
            'message' => 'Чек принят в обработку',
            'receipt_id' => $receipt->id,
        ]);
    }

    /**
     * Ручной ввод реквизитов.
     */
    public function submitManual(SubmitManualRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! $this->receiptService->checkLimits($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Ты можешь отсканировать и сфотографировать не более 2 чеков в день и не более 4 в месяц. Общая сумма баллов по чекам за текущий квартал не должна превышать 2000 баллов',
            ], 403);
        }

        $receipt = $user->receipts()->create([
            'source' => ReceiptSource::MANUAL,
            'fn' => $request->fn,
            'fd' => $request->fd,
            'fp' => $request->fp,
            'sum' => $request->sum,
            'dt' => $request->dt,
            'status' => ReceiptStatus::PENDING,
        ]);

        $this->receiptService->submitForProcessing($receipt);

        return response()->json([
            'success' => true,
            'message' => 'Чек принят в обработку',
            'receipt_id' => $receipt->id,
        ]);
    }

    /**
     * Получить список чеков пользователя.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $receipts = $user->receipts()
            ->orderBy('created_at', 'desc')
            ->get();

        return ReceiptResource::collection($receipts);
    }

    /**
     * Получить статус конкретного чека.
     */
    public function show(Request $request, int $id): ReceiptResource|JsonResponse
    {
        $user = $request->user();
        $receipt = $user->receipts()->find($id);

        if (! $receipt) {
            return response()->json([
                'success' => false,
                'message' => 'Чек не найден',
            ], 404);
        }

        return new ReceiptResource($receipt);
    }
}
