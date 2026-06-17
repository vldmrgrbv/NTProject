<?php

namespace Tests\Feature;

use App\Enums\ReceiptStatus;
use App\Jobs\CheckFnsJob;
use App\Models\Receipt;
use App\Services\Contracts\FnsServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckFnsRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_fns_job_retries_on_specific_status_codes(): void
    {
        $retryCodes = [404, 406, 452, 453, 454, 455, 503, 527, 528, 529, 530, 531, 532, 533];

        foreach ($retryCodes as $code) {
            $receipt = Receipt::factory()->create(['status' => ReceiptStatus::PROCESSING]);

            $fnsService = $this->createMock(FnsServiceInterface::class);
            $fnsService->method('checkReceipt')
                ->willReturn(['status_code' => $code, 'body' => ['message' => 'Retryable error']]);

            $job = new CheckFnsJob($receipt);

            try {
                $job->handle($fnsService);
                $this->fail("Expected exception for status code {$code} was not thrown.");
            } catch (\Exception $e) {
                $this->assertEquals("FNS service returned retryable status code: {$code}", $e->getMessage());
                // Status should still be PROCESSING because it's a retry
                $this->assertEquals(ReceiptStatus::PROCESSING, $receipt->fresh()->status);
            }
        }
    }

    public function test_check_fns_job_rejects_on_non_retryable_error(): void
    {
        $receipt = Receipt::factory()->create(['status' => ReceiptStatus::PROCESSING]);

        $fnsService = $this->createMock(FnsServiceInterface::class);
        $fnsService->method('checkReceipt')
            ->willReturn(['status_code' => 400, 'body' => ['message' => 'Bad Request']]);

        $job = new CheckFnsJob($receipt);
        $job->handle($fnsService);

        $this->assertEquals(ReceiptStatus::REJECTED, $receipt->fresh()->status);
        $this->assertStringContainsString('Не прошел ФНС', $receipt->fresh()->reason_failed);
    }
}
