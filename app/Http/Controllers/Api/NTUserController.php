<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserEventType;
use App\Http\Controllers\Controller;
use App\Models\UserEvent;
use App\Services\NTApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NTUserController extends Controller
{
    public function __construct(
        protected NTApiService $ntApi
    ) {}

    /**
     * Получить баланс пользователя
     */
    public function getScores(Request $request): JsonResponse
    {
        $user = $request->user();
        $phone = $user->phone;

        if (! $phone) {
            return response()->json([
                'success' => false,
                'message' => 'К сожалению, мне не удалось ничего найти. Ты уверен, что номер правильный? Попробуй ввести его еще раз. Лучше всего делать это в формате: 79ХХХХХХХХХ.',
            ]);
        }

        $response = $this->ntApi->getScores($phone);
        if (data_get($response, 'status') === 'errors') {
            return response()->json([
                'success' => data_get($response, 'status'),
                'message' => data_get($response, 'message'),
            ]);
        }

        return response()->json([
            'success' => true,
            'scores' => data_get($response, 'data'),
        ]);
    }
}
