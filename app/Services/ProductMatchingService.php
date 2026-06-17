<?php

namespace App\Services;

use App\Enums\ReceiptResponseKey;
use App\Models\Receipt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductMatchingService
{
    protected string $url;

    protected string $user;

    protected string $password;

    public function __construct()
    {
        $this->url = rtrim(config('nt.product_api.url'), '/').'/';
        $this->user = config('nt.product_api.user');
        $this->password = config('nt.product_api.password');
    }

    /**
     * Получить токен аутентификации.
     */
    protected function getToken(): ?string
    {
        return Cache::remember('network_api_token', 3600, function () {
            $response = Http::post($this->url.'Authentication/Token', [
                'userName' => $this->user,
                'password' => $this->password,
                'isPersistent' => true,
            ]);

            if ($response->successful()) {
                return $response->json('accessToken');
            }

            Log::error('Network API Auth failed: '.$response->body());

            return null;
        });
    }

    /**
     * Отправить товары чека на сопоставление.
     */
    public function matchReceiptItems(Receipt $receipt): ?string
    {
        $token = $this->getToken();
        if (! $token) {
            return null;
        }

        $items = data_get($receipt->responses, 'fns.data.content.items') ?? [];
        $inn = trim($receipt->inn ?? '');

        if (empty($items)) {
            Log::warning("No items found in receipt {$receipt->id} for matching");

            return null;
        }

        $tasks = [];
        foreach ($items as $item) {
            if (isset($item['name'])) {
                $tasks[] = [
                    'line' => $item['name'],
                    'tin' => $inn,
                ];
            }
        }

        $response = Http::withToken($token)
            ->post($this->url.'LineMatching/MatchWithTin', [
                'tasks' => $tasks,
            ]);

        $receipt->addResponse(ReceiptResponseKey::MATCHING_SENT, $response->json());

        if ($response->successful()) {
            return $response->json('workflowInstanceId');
        }

        Log::error("Network API MatchWithTin failed for receipt {$receipt->id}: ".$response->body());

        return null;
    }

    /**
     * Получить результат сопоставления.
     */
    public function getMatchingResult(Receipt $receipt, string $networkId): ?array
    {
        $token = $this->getToken();
        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->get($this->url.'LineMatching/GetResult/'.$networkId);

        if ($response->successful()) {
            $result = $response->json();
            Log::info("Network API. Success for receipt ID {$receipt->id}: ", [$response->json()]);
            $receipt->addResponse(ReceiptResponseKey::MATCHING_RESULT, $result);

            return $result;
        }

        Log::error("Network API. Failed for network ID {$networkId}: ".$response->body());

        return null;
    }

    /**
     * Обработать результаты сопоставления и найти SKU.
     */
    public function processMatchingResult(Receipt $receipt, array $matchingResult): array
    {
        $items = data_get($receipt->responses, 'fns.data.content.items') ?? [];
        $results = data_get($matchingResult, 'matchingResult') ?? [];
        Log::info("Processing matching result for receipt {$receipt->id}: ", [$items, $results]);

        $skus = [];

        foreach ($results as $res) {
            if (! empty($res['skuCode'])) {
                $found = $this->findItemByName($items, $res['receipt']);
                if ($found) {
                    $item = $found['item'];
                    $items = $found['remaining_items'];

                    $skus[] = [
                        'SKU' => $res['skuCode'],
                        'errorCode' => $res['errorCode'] ?? null,
                        'name' => $item['name'],
                        'price' => $item['price'] ?? 0,
                        'quantity' => $item['quantity'] ?? 0,
                        'sum' => $item['sum'] ?? 0,
                        'paymentType' => $item['paymentType'] ?? null,
                    ];
                }
            }
        }

        return $skus;
    }

    protected function findItemByName(array $items, string $name): ?array
    {
        foreach ($items as $key => $item) {
            if (isset($item['name']) && str_contains($item['name'], $name)) {
                $foundItem = $item;
                unset($items[$key]);

                return [
                    'item' => $foundItem,
                    'remaining_items' => array_values($items),
                ];
            }
        }

        return null;
    }
}
