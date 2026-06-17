<?php

namespace App\Services;

use App\Models\Receipt;
use App\Services\Contracts\NTApiServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NTApiService implements NTApiServiceInterface
{
    protected string $apiUrl;

    protected string $apiToken;

    protected string $api2Url;

    protected string $api2Token;

    public function __construct()
    {
        $this->apiUrl = config('nt.api_url');
        $this->apiToken = config('nt.token');
        $this->api2Url = config('nt.api2.url');
        $this->api2Token = config('nt.api2.token');
    }

    /**
     * Отправка кода через внешний API NT.
     */
    public function sendCode(string $phone): array
    {
        $url = "{$this->apiUrl}chatbot/sendCode/";

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
            ]);

            Log::info("NTApi sendCode response for {$phone}: ".$response->body());

            $responseJson = $response->json();
            $errors = $this->handleErrors($responseJson);

            return $errors ?? $response->json();

        } catch (\Exception $e) {
            Log::error("NTApi sendCode error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Проверка пользователя во внешнем API.
     */
    public function checkUser(string $phone): array
    {
        $url = "{$this->apiUrl}chatbot/checkUser/";

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
                'chatId' => "{$phone}@c.us",
                'botId' => 6,
                'setRelation' => 0,
            ]);

            $responseJson = $response->json();
            $errors = $this->handleErrors($responseJson);

            return $errors ?? $response->json();
        } catch (\Exception $e) {
            Log::error("NTApi checkUser error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Регистрация пользователя во внешнем API.
     */
    public function registerUser(array $data): array
    {
        $url = "{$this->apiUrl}chatbot/registerUser/";

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, array_merge([
                'name' => '',
                'phone' => '',
                'email' => '',
                'last_name' => '',
                'password' => '',
                'gender' => '',
            ], $data));

            Log::info("NTApi registerUser response for {$data['phone']}: ".$response->body());

            $responseJson = $response->json();
            $errors = $this->handleErrors($responseJson);

            return $errors ?? $response->json();

        } catch (\Exception $e) {
            Log::error('NTApi registerUser error: '.$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function auth(string $phone): array
    {
        $url = "{$this->apiUrl}chatbot/checkUser/";

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
                'chatId' => "{$phone}@c.us",
                'botId' => 6,
                'setRelation' => 1,
            ]);

            Log::info("NTApi auth (checkUser setRelation=1) response for {$phone}: ".$response->body());

            $responseJson = $response->json();
            $errors = $this->handleErrors($responseJson);

            return $errors ?? $response->json();

        } catch (\Exception $e) {
            Log::error("NTApi auth error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function integrationRegSend(string $phone): array
    {
        $url = "{$this->api2Url}external/users/messenger/connection/set/";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'NTIntegration token="'.$this->api2Token.'"',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
            ]);

            Log::info("NTApi integrationRegSend response for {$phone}: ", [$response->json()]);

            return $response->json() ?? ['status' => 'success'];

        } catch (\Exception $e) {
            Log::error("NTApi integrationRegSend error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function integrationAuthSend(string $phone): array
    {
        $url = "{$this->api2Url}external/integration/user/external_accrual/";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'NTIntegration token="'.$this->api2Token.'"',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
                'type' => 'auth',
            ]);

            Log::info("NTApi integrationAuthSend response for {$phone}: ", [$response->json()]);

            return $response->json() ?? ['status' => 'success'];

        } catch (\Exception $e) {
            Log::error("NTApi integrationAuthSend error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function getScores(string $phone): array
    {
        $url = "{$this->apiUrl}chatbot/getScoreByUser/";

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'chatId' => "{$phone}@c.us",
                'botId' => 6,
            ]);

            $responseJson = $response->json();
            $errors = $this->handleErrors($responseJson);

            return $errors ?? $response->json();

        } catch (\Exception $e) {
            Log::error("NTApi getScores error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Регистрация чека во внешнем API NT.
     */
    public function registerReceipt(Receipt $receipt): array
    {
        $url = "{$this->apiUrl}chatbot/addPointsByCheck/";

        try {
            $user = $receipt->user;
            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, [
                'id' => (int) $receipt->id,
                'fn' => (string) $receipt->fn,
                'fd' => (string) $receipt->fd,
                'fpd' => (string) $receipt->fp,
                'sum' => (int) $receipt->sum,
                'date' => (string) $receipt->dt,
                'phone' => (string) $user->phone,
                'salesChannel' => null,
                'inn' => $receipt->inn,
                'points' => 0,
                'status' => 'new',
            ]);

            $responseJson = $response->json();
            Log::info("NTApi registerReceipt response for receipt {$receipt->id}: ".$response->body());
            $errors = $this->handleErrors($responseJson);

            return $errors ?? $responseJson;

        } catch (\Exception $e) {
            Log::error("NTApi registerReceipt error for receipt {$receipt->id}: ".$e->getMessage());

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateStatusCheck(
        Receipt $receipt,
        string $status = 'processed',
        array $productsUnrecognized = [],
        string $errorText = ''
    ): array {
        $url = "{$this->apiUrl}chatbot/updateCheck/";

        try {
            $payload = [
                'id' => (int) $receipt->id,
                'status' => $status,
                'products' => $receipt->skus ?? [],
                'products_unrecognized' => $productsUnrecognized,
                'inn' => $receipt->inn,
                'items' => data_get($receipt->responses, 'fns.data.content.items') ?? [],
                'error_text' => $errorText,
            ];

            $response = Http::withHeaders([
                'Authorization' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            $responseJson = $response->json();
            Log::info("NTApi updateStatusCheck payload for receipt {$receipt->id}: ".json_encode($payload));
            Log::info("NTApi updateStatusCheck response for receipt {$receipt->id}: ".$response->body());

            $errors = $this->handleErrors($responseJson);

            return $errors ?? $responseJson;

        } catch (\Exception $e) {
            Log::error("NTApi updateStatusCheck error for receipt {$receipt->id}: ".$e->getMessage());

            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function ntGetOrders(string $phone): array
    {
        $url = "{$this->api2Url}external/orders/list/";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'NTIntegration token="'.$this->api2Token.'"',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
            ]);

            return $response->json() ?? [];

        } catch (\Exception $e) {
            Log::error("NTApi ntGetOrders error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function ntGetOrder(string $phone, string|int $orderId): array
    {
        $url = "{$this->api2Url}external/orders/detail/";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'NTIntegration token="'.$this->api2Token.'"',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
                'orderId' => $orderId,
            ]);

            return $response->json() ?? [];

        } catch (\Exception $e) {
            Log::error("NTApi ntGetOrder error for {$phone}, order {$orderId}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function ntGetReceipts(string $phone): array
    {
        $url = "{$this->api2Url}external/integration/check/extra/list_by_phone/";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'NTIntegration token="'.$this->api2Token.'"',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
            ]);

            return $response->json() ?? [];

        } catch (\Exception $e) {
            Log::error("NTApi ntGetReceipts error for {$phone}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function ntGetReceipt(string $phone, string|int $receiptId): array
    {
        $url = "{$this->api2Url}external/integration/check/extra/list_by_phone/{$receiptId}/";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'NTIntegration token="'.$this->api2Token.'"',
                'Content-Type' => 'application/json',
            ])->post($url, [
                'phone' => $phone,
            ]);

            return $response->json() ?? [];

        } catch (\Exception $e) {
            Log::error("NTApi ntGetReceipt error for {$phone}, receipt {$receiptId}: ".$e->getMessage());

            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function handleErrors(array $response): ?array
    {
        if (data_get($response, 'status') !== 'error') {
            return null;
        }

        return [
            'status' => false,
            'message' => data_get($response, 'errors.0.message'),
        ];
    }
}
