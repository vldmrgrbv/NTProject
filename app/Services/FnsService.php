<?php

namespace App\Services;

use App\Models\Receipt;
use App\Services\Contracts\FnsServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FnsService implements FnsServiceInterface
{
    protected string $baseUrl;

    protected string $user;

    public function __construct()
    {
        $this->baseUrl = config('nt.fns_api.base_url');
        $this->user = config('nt.fns_api.user');
    }

    public function checkReceipt(Receipt $receipt): array
    {
        if (! $receipt->fn || ! $receipt->fd || ! $receipt->fp || ! $receipt->sum || ! $receipt->dt) {
            return ['status' => 'error', 'message' => 'Missing required fields for FNS check'];
        }

        try {
            $data = [
                's' => (int) ($receipt->sum * 100),
                't' => Carbon::parse($receipt->dt)->format('Y-m-d\TH:i:s'),
                'fn' => $receipt->fn,
                'n' => 1, // TypeOperation
                'i' => $receipt->fd,
                'fp' => $receipt->fp,
            ];

            Log::info("FNS check request for receipt {$receipt->id}. Send data: ".json_encode($data));

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->get($this->baseUrl, $data);

            Log::info("FNS check response for receipt {$receipt->id}. Response: ".$response->body());

            return [
                'status_code' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("FNS check error for receipt {$receipt->id}: ".$e->getMessage());

            return ['status_code' => 500, 'message' => $e->getMessage()];
        }
    }
}
