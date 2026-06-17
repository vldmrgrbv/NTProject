<?php

namespace Tests\Unit;

use App\Enums\ReceiptStatus;
use App\Jobs\UpdateStatusCheckJob;
use App\Models\Receipt;
use App\Services\Contracts\NTApiServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UpdateStatusCheckJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_without_skus_is_sent_to_nt_as_to_check(): void
    {
        $receipt = Receipt::factory()->create([
            'status' => ReceiptStatus::PROCESSING,
            'inn' => '7704217370',
            'skus' => [],
            'responses' => [
                'fns' => [
                    'data' => [
                        'content' => [
                            'items' => [
                                [
                                    'name' => 'Unknown item',
                                    'quantity' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $ntService = Mockery::mock(NTApiServiceInterface::class);
        $ntService
            ->shouldReceive('updateStatusCheck')
            ->once()
            ->withArgs(function (
                Receipt $passedReceipt,
                string $status,
                array $productsUnrecognized,
                string $errorText
            ) use ($receipt): bool {
                return $passedReceipt->is($receipt)
                    && $status === 'to_check'
                    && $productsUnrecognized === [['brand' => 'Unknown item', 'quantity' => 2]]
                    && $errorText === 'Не распознан товар в чеке';
            })
            ->andReturn([
                'status' => 'success',
                'data' => ['number' => 123],
            ]);

        (new UpdateStatusCheckJob($receipt))->handle($ntService);

        $receipt->refresh();

        $this->assertSame(ReceiptStatus::REJECTED, $receipt->status);
        $this->assertSame(123, (int) $receipt->nt_number);
        $this->assertSame('Не найдены skus NT', $receipt->reason_failed);
    }
}
