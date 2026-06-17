<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Jobs\CheckDuplicateJob;
use App\Jobs\CheckFnsJob;
use App\Jobs\NotifyUserJob;
use App\Jobs\ProcessReceiptJob;
use App\Jobs\RegisterNTJob;
use App\Models\Receipt;
use App\Models\ReceiptPhoto;
use App\Models\User;
use App\Services\Contracts\NTApiServiceInterface;
use App\Services\Contracts\FnsServiceInterface;
use App\Services\Contracts\ReceiptRecognitionServiceInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessReceiptJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_chain_processing_success(): void
    {
        // 1. Arrange
        $user = User::create(['phone' => '79991234567']);
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'status' => ReceiptStatus::PENDING,
            'source' => 'upload',
            'skus' => [['SKU' => 'TEST']],
        ]);
        ReceiptPhoto::create([
            'receipt_id' => $receipt->id,
            'path' => 'receipts/test.jpg',
        ]);

        $recognitionService = $this->mock(ReceiptRecognitionServiceInterface::class);
        $recognitionService->shouldReceive('sendToRecognition')
            ->once()
            ->andReturn([
                'status_code' => 200,
                'body' => [
                    'uuid' => 'ext-uuid-123',
                    'fn' => '123456',
                    'fd' => '789',
                    'fs' => '456',
                    'sum' => '1000',
                    'receipt_date' => Carbon::now()->format('Y-m-d\TH:i:s'),
                ],
            ]);

        $fnsService = $this->mock(FnsServiceInterface::class);
        $fnsService->shouldReceive('checkReceipt')
            ->once()
            ->andReturn(['status_code' => 200, 'body' => ['status' => 'success']]);

        $ntService = $this->mock(NTApiServiceInterface::class);
        $ntService->shouldReceive('registerReceipt')
            ->once()
            ->andReturn([
                'status' => 'success',
                'number' => 'REG-123',
                'scores' => 50,
            ]);

        // 2. Act
        (new ProcessReceiptJob($receipt))->handle($recognitionService);
        (new CheckDuplicateJob($receipt))->handle();
        (new CheckFnsJob($receipt))->handle($fnsService);
        (new RegisterNTJob($receipt))->handle($ntService);
        (new NotifyUserJob($receipt))->handle();

        // 3. Assert
        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'status' => ReceiptStatus::ACCEPTED->value,
            'nt_number' => 'REG-123',
            'scores' => 50,
        ]);
    }

    public function test_process_receipt_rejected_old_date(): void
    {
        // 1. Arrange
        $user = User::create(['phone' => '79991234567']);
        $receipt = Receipt::create([
            'user_id' => $user->id,
            'status' => ReceiptStatus::PENDING,
            'source' => 'upload',
        ]);
        ReceiptPhoto::create([
            'receipt_id' => $receipt->id,
            'path' => 'receipts/test.jpg',
        ]);

        $recognitionService = $this->mock(ReceiptRecognitionServiceInterface::class);
        $recognitionService->shouldReceive('sendToRecognition')
            ->once()
            ->andReturn([
                'status_code' => 200,
                'body' => [
                    'uuid' => 'ext-uuid-123',
                    'fn' => '123456',
                    'fd' => '789',
                    'fs' => '456',
                    'sum' => '1000',
                    'receipt_date' => Carbon::now()->subDays(40)->format('Y-m-d\TH:i:s'),
                ],
            ]);

        $ntService = $this->mock(NTApiServiceInterface::class);
        $ntService->shouldNotReceive('registerReceipt');

        // 2. Act
        (new ProcessReceiptJob($receipt))->handle($recognitionService);

        // 3. Assert
        $this->assertDatabaseHas('receipts', [
            'id' => $receipt->id,
            'status' => ReceiptStatus::REJECTED->value,
            'reason_failed' => 'Чек старше 30 дней',
        ]);
    }
}
