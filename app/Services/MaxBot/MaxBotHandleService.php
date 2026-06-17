<?php

namespace App\Services\MaxBot;

use App\Services\TokenService;

class MaxBotHandleService
{
    public function __construct(protected TokenService $tokenService) {}

    public function generateRedirectUrl(array $parameters): string
    {
        $token = $this->tokenService->encrypt($parameters);

        return config('app.url').'/max-bot/redirect/'.$token;
    }
}
