<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MaxInitDataValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $initData = $request->header('X-MAX-Init-Data') ?: $request->input('init_data');

        if (! $initData) {
            return response()->json(['error' => 'Missing MAX init data'], 401);
        }

        if (! $this->validateInitData($initData)) {
            return response()->json(['error' => 'Invalid MAX init data'], 401);
        }

        // Парсим данные и добавляем их в запрос для дальнейшего использования
        $parsedData = $this->parseInitData($initData);
        $request->merge(['max_user' => $parsedData['user'] ?? null]);

        return $next($request);
    }

    /**
     * Валидация данных согласно документации MAX
     * https://dev.max.ru/docs/webapps/validation
     */
    private function validateInitData(string $initData): bool
    {
        $token = config('nt.max-bot.token');
        if (! $token) {
            Log::error('MAXBOT_ACCESS_TOKEN not set in .env');

            return false;
        }

        $params = [];
        parse_str($initData, $params);

        if (! isset($params['hash'])) {
            return false;
        }

        $hash = $params['hash'];
        unset($params['hash']);

        ksort($params);

        $dataCheckString = '';
        foreach ($params as $key => $value) {
            $dataCheckString .= "$key=$value\n";
        }
        $dataCheckString = rtrim($dataCheckString, "\n");

        $secretKey = hash_hmac('sha256', $token, 'WebAppData', true);
        $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        return hash_equals($hash, $calculatedHash);
    }

    private function parseInitData(string $initData): array
    {
        $params = [];
        parse_str($initData, $params);

        if (isset($params['user'])) {
            $params['user'] = json_decode($params['user'], true);
        }

        return $params;
    }
}
