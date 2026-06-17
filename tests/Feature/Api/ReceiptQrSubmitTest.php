<?php

namespace Tests\Feature\Api;

use App\Enums\ReceiptSource;
use App\Enums\ReceiptStatus;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceiptQrSubmitTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_qr_creates_receipt_when_qr_string_is_valid(): void
    {
        Bus::fake();

        $user = User::factory()->create(['is_whitelisted' => true]);
        Sanctum::actingAs($user);

        $qrString = 't=20260603T102100&s=755.30&fn=7384440901041561&i=10845&fp=1607076862&n=1';

        $response = $this->postJson('/api/nt/receipts/submit-qr', [
            'qr_string' => $qrString,
        ]);

        $response
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('receipts', [
            'user_id' => $user->id,
            'source' => ReceiptSource::QR->value,
            'qr_string' => $qrString,
            'sum' => '755.30',
            'dt' => '2026-06-03 10:21:00',
            'fn' => '7384440901041561',
            'fd' => '10845',
            'fp' => '1607076862',
            'status' => ReceiptStatus::PENDING->value,
        ]);
    }

    public function test_submit_qr_returns_error_when_qr_string_cannot_be_parsed(): void
    {
        Bus::fake();

        $user = User::factory()->create(['is_whitelisted' => true]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/nt/receipts/submit-qr', [
            'qr_string' => 'not-a-fiscal-qr',
        ]);

        $response
            ->assertForbidden()
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount((new Receipt)->getTable(), 0);
    }
}
