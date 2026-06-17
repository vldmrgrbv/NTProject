<?php

namespace App\Http\Controllers;

use App\Services\MaxBot\MaxBotWebhookService;
use BushlanovDev\MaxMessengerBot\Laravel\MaxBotManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class MaxBotWebhookController extends Controller
{
    public function __construct(
        private MaxBotWebhookService $webhookService
    ) {}

    /**
     * Handle incoming webhook from MAX Bot.
     */
    public function __invoke(Request $request, MaxBotManager $botManager): JsonResponse|Response
    {
        Log::info('MAX Bot Webhook Received: ', ['payload' => $request->all(), 'headers' => $request->headers->all()]);
        try {
            $this->webhookService->registerHandlers($botManager);

            return $botManager->handleWebhook($request);
        } catch (Throwable $e) {
            Log::error('MAX Bot Webhook Error: '.$e->getMessage(), [
                'exception' => $e,
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 200);
        }
    }
}
