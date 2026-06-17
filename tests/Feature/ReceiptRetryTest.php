<?php

namespace Tests\Feature;

use App\Jobs\CheckDuplicateJob;
use App\Jobs\ProcessReceiptJob;
use App\Models\Receipt;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class ReceiptRetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobs_have_retry_configuration(): void
    {
        $receipt = Receipt::factory()->create();

        $job = new ProcessReceiptJob($receipt);

        $this->assertEquals(5, $job->tries());
        $this->assertEquals(43200, $job->backoff());

        $dupJob = new CheckDuplicateJob($receipt);
        $this->assertEquals(5, $dupJob->tries());
        $this->assertEquals(43200, $dupJob->backoff());
    }

    public function test_submit_for_processing_dispatches_chain_with_catch(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $receipt = Receipt::factory()->create(['user_id' => $user->id]);

        $service = new ReceiptService;
        $service->submitForProcessing($receipt);

        Bus::assertDispatched(CheckDuplicateJob::class, function ($job) {
            return count($job->chained) === 4;
        });
    }
}
