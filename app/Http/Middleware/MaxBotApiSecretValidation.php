<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaxBotApiSecretValidation
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->header('X-Max-Bot-Api-Secret');
        $expectedSecret = config('nt.max-bot.api_secret');

        if (! $expectedSecret || $secret !== $expectedSecret) {
            abort(403);
        }

        return $next($request);
    }
}
