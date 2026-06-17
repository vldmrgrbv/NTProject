<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Jobs\CheckDuplicateJob;
use App\Jobs\CheckFnsJob;
use App\Models\Receipt;
use App\Services\Contracts\FnsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptProcessingJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_duplicate_job_rejects_duplicate(): void
    {
        Receipt::factory()->create([
            'fn' => '123',
            'fd' => '456',
            'fp' => '789',
            'status' => ReceiptStatus::ACCEPTED,
        ]);

        $receipt = Receipt::factory()->create([
            'fn' => '123',
            'fd' => '456',
            'fp' => '789',
            'status' => ReceiptStatus::PROCESSING,
        ]);

        $job = new CheckDuplicateJob($receipt);
        $job->handle();

        $this->assertEquals(ReceiptStatus::REJECTED, $receipt->fresh()->status);
        $this->assertEquals('Дубликат чека', $receipt->fresh()->reason_failed);
    }

    public function test_check_fns_job_rejects_on_error(): void
    {
        $receipt = Receipt::factory()->create(['status' => ReceiptStatus::PROCESSING]);

        $fnsService = $this->createMock(FnsServiceInterface::class);
        $fnsService->expects($this->once())
            ->method('checkReceipt')
            ->willReturn(['status_code' => 400, 'body' => ['message' => 'Bad Request']]);

        $job = new CheckFnsJob($receipt);
        $job->handle($fnsService);

        $this->assertEquals(ReceiptStatus::REJECTED, $receipt->fresh()->status);
        $this->assertStringContainsString('Не прошел ФНС', $receipt->fresh()->reason_failed);
    }
}
