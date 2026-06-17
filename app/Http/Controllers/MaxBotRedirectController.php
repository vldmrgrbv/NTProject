<?php

namespace App\Http\Controllers;

use App\Enums\MaxBot\BotButton;
use App\Enums\UserEventType;
use App\Models\UserEvent;
use App\Services\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;

class MaxBotRedirectController extends Controller
{
    public function __invoke(Request $request, string $token, TokenService $tokenService): RedirectResponse|Redirector
    {
        try {
            $data = $tokenService->decrypt($token);
        } catch (\Exception $e) {
            Log::warning('MAX Bot Redirect failed. Invalid token: '.$e->getMessage());
            abort(403);
        }

        $userId = $data['user_id'] ?? null;
        $buttonValue = $data['button_value'] ?? null;

        $button = BotButton::tryFrom($buttonValue);

        if (! $button || ! $button->responseUrl()) {
            Log::warning('MAX Bot Redirect request failed. Invalid BotButton: '.($buttonValue ?? 'null').' or no response url: '.$button->responseUrl());
            abort(403);
        }

        if ($userId) {
            UserEvent::create([
                'user_id' => $userId,
                'event_type' => UserEventType::BUTTON_CLICK,
                'payload' => ['button' => $button->value],
            ]);
        }

        Log::info("MAX Bot Redirect request user_id: $userId, button: {$button->value}, response url: {$button->responseUrl()}");

        return redirect($button->responseUrl());
    }
}
