<?php

namespace Tests\Feature\Api;

use App\Enums\ReceiptStatus;
use App\Jobs\CheckDuplicateJob;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReceiptApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Bus::fake();
    }

    public function test_upload_photo_success(): void
    {
        $user = User::create(['phone' => '79991234567']);
        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->postJson('/api/nt/receipts/upload-photo', [
            'phone' => '79991234567',
            'photo' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('receipts', [
            'user_id' => $user->id,
            'source' => 'upload',
        ]);

        $receipt = Receipt::first();
        $this->assertCount(1, $receipt->photos);
        Storage::disk('s3')->assertExists($receipt->photos->first()->path);

        Bus::assertDispatched(CheckDuplicateJob::class);
    }

    public function test_submit_qr_success(): void
    {
        $user = User::create(['phone' => '79991234567']);
        $qrString = 't=20260518T2100&s=123.45&fn=123&i=456&fp=789';

        $response = $this->postJson('/api/nt/receipts/submit-qr', [
            'phone' => '79991234567',
            'qr_string' => $qrString,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('receipts', [
            'user_id' => $user->id,
            'source' => 'qr',
            'qr_string' => $qrString,
        ]);

        Bus::assertDispatched(CheckDuplicateJob::class);
    }

    public function test_submit_manual_success(): void
    {
        $user = User::create(['phone' => '79991234567']);

        $response = $this->postJson('/api/nt/receipts/submit-manual', [
            'phone' => '79991234567',
            'fn' => '111',
            'fd' => '222',
            'fp' => '333',
            'sum' => 1000.50,
            'dt' => '2026-05-18T12:00:00',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('receipts', [
            'user_id' => $user->id,
            'source' => 'manual',
            'fn' => '111',
            'sum' => 1000.50,
        ]);

        Bus::assertDispatched(CheckDuplicateJob::class);
    }

    public function test_check_limits_fails(): void
    {
        $user = User::create(['phone' => '79991234567']);

        // Создаем 2 чека сегодня
        for ($i = 0; $i < 2; $i++) {
            Receipt::create([
                'user_id' => $user->id,
                'status' => ReceiptStatus::ACCEPTED,
                'source' => 'manual',
                'sum' => 100,
                'dt' => now()->toDateTimeString(),
            ]);
        }

        $response = $this->postJson('/api/nt/receipts/submit-qr', [
            'phone' => '79991234567',
            'qr_string' => 'some-qr',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'Превышен лимит загрузки чеков']);
    }
}
