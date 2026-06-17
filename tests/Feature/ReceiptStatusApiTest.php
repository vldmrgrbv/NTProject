<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['phone' => '79991234567']);
    }

    public function test_can_list_user_receipts(): void
    {
        Receipt::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/nt/receipts?phone={$this->user->phone}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'status', 'sum', 'dt', 'reason_failed', 'created_at'],
                ],
            ]);
    }

    public function test_can_show_receipt_status(): void
    {
        $receipt = Receipt::factory()->create([
            'user_id' => $this->user->id,
            'status' => ReceiptStatus::ACCEPTED,
            'sum' => 100.50,
            'nt_number' => 'NT-123',
            'scores' => 10,
        ]);

        $response = $this->getJson("/api/nt/receipts/{$receipt->id}?phone={$this->user->phone}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $receipt->id,
                    'status' => ReceiptStatus::ACCEPTED->value,
                    'sum' => 100.50,
                    'number' => 'NT-123',
                    'scores' => 10,
                ],
            ]);
    }

    public function test_cannot_see_other_user_receipt(): void
    {
        $otherUser = User::factory()->create(['phone' => '78880000000']);
        $receipt = Receipt::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/nt/receipts/{$receipt->id}?phone={$this->user->phone}");

        $response->assertStatus(404);
    }
}
